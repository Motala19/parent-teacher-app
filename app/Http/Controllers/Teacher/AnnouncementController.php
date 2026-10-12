<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Subject;
use App\Support\Attachments;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    private function gradeName(string $grade): string
    {
        return $grade === 'R' ? 'Grade R' : 'Grade ' . $grade;
    }

    // Everything this teacher may post to. Each option knows its own targets.
    private function audiences(): Collection
    {
        $me      = auth()->user();
        $options = collect();

        // Class teacher: the whole class
        foreach ($me->myClasses()->orderBy('grade')->orderBy('name')->get() as $class) {
            $options->put('class:' . $class->id, [
                'group'          => 'As class teacher',
                'label'          => $class->name . ' · whole class',
                'audience_label' => $class->name,
                'targets'        => [['class_id' => $class->id, 'subject_id' => null]],
            ]);
        }

        // Subject teacher: per subject
        $bySubject = $me->subjectAssignments()->with(['subject', 'classRoom'])->get()
            ->groupBy('subject_id')
            ->sortBy(fn ($rows) => $rows->first()->subject->name);

        foreach ($bySubject as $subjectId => $rows) {
            $subject = $rows->first()->subject;
            $rows    = $rows->sortBy(fn ($r) => [Subject::gradeRank($r->classRoom->grade), $r->classRoom->name])->values();

            // Whole grade: all my classes in that grade that take this subject
            foreach ($rows->groupBy(fn ($r) => (string) $r->classRoom->grade) as $grade => $inGrade) {
                $grade = (string) $grade;

                if ($grade === '' || $inGrade->count() < 2) {
                    continue;
                }

                $options->put("grade:{$grade}:{$subjectId}", [
                    'group'          => $subject->name,
                    'label'          => $this->gradeName($grade) . ' · whole grade (' . $inGrade->map(fn ($r) => $r->classRoom->name)->join(', ') . ')',
                    'audience_label' => $subject->name . ' · ' . $this->gradeName($grade),
                    'targets'        => $inGrade->map(fn ($r) => ['class_id' => $r->class_id, 'subject_id' => (int) $subjectId])->values()->all(),
                ]);
            }

            // All my classes for this subject
            $gradesTaught = $rows->pluck('classRoom.grade')->map(fn ($g) => (string) $g)->unique();

            if ($rows->count() > 1 && ($gradesTaught->count() > 1 || $gradesTaught->first() === '')) {

            // One class: only learners taking the subject
            foreach ($rows as $r) {
                $options->put('subject:' . $r->class_id . ':' . $subjectId, [
                    'group'          => $subject->name,
                    'label'          => $r->classRoom->name . ' · learners taking ' . $subject->name,
                    'audience_label' => $subject->name . ' · ' . $r->classRoom->name,
                    'targets'        => [['class_id' => $r->class_id, 'subject_id' => (int) $subjectId]],
                ]);
            }
             }
        }

        return $options;
    }
    

    private function own(Announcement $announcement): Announcement
    {
        abort_unless((int) $announcement->user_id === (int) auth()->id(), 404);
        return $announcement;
    }

    public function index(Request $request)
    {
        $announcements = Announcement::with('attachments')
            ->where('user_id', auth()->id())
            ->when(
                array_key_exists((string) $request->type, Announcement::teacherTypes()),
                fn ($q) => $q->where('type', $request->type)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('teacher.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('teacher.announcements.create', [
            'audiences' => $this->audiences(),
            'types'     => Announcement::teacherTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $audiences = $this->audiences();

        $data = $request->validate([
            'audience' => ['required', Rule::in($audiences->keys()->all())],
            'type'     => ['required', Rule::in(array_keys(Announcement::teacherTypes()))],
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|max:5000',
        ] + Attachments::announcementRules(), Attachments::announcementMessages() + [
            'audience.required' => 'Choose who should receive this.',
            'audience.in'       => 'You can only post to your own classes and subjects.',
        ]);

        $target = $audiences->get($data['audience']);

        $announcement = Announcement::create([
            'school_id'      => $user->school_id,
            'user_id'        => $user->id,
            'audience'       => 'classes',
            'audience_label' => $target['audience_label'],
            'type'           => $data['type'],
            'title'          => $data['title'],
            'body'           => $data['body'],
            'is_published'   => true,
            'published_at'   => now(),
        ]);

        $announcement->targets()->createMany($target['targets']);

        $count    = Attachments::store($announcement, $request->file('files'), $user);
        $notified = Notifier::announcement($announcement);

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'Announcement posted to ' . $target['audience_label']
                . ($count ? " with {$count} " . Str::plural('file', $count) : '')
                . ($notified ? ". {$notified} " . Str::plural('person', $notified) . ' notified.' : '.'));
    }

    public function edit(Announcement $announcement)
    {
        $this->own($announcement)->load('attachments');

        return view('teacher.announcements.edit', [
            'announcement' => $announcement,
            'types'        => Announcement::teacherTypes(),
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->own($announcement);

        $data = $request->validate([
            'type'           => ['required', Rule::in(array_keys(Announcement::teacherTypes()))],
            'title'          => 'required|string|max:255',
            'body'           => 'required|string|max:5000',
            'remove_files'   => 'nullable|array',
            'remove_files.*' => 'integer',
        ] + Attachments::announcementRules(), Attachments::announcementMessages());

        $announcement->update([
            'type'  => $data['type'],
            'title' => $data['title'],
            'body'  => $data['body'],
        ]);

        Attachments::remove($announcement, $data['remove_files'] ?? []);
        Attachments::store($announcement, $request->file('files'), auth()->user());

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->own($announcement)->delete();

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'Announcement and its files deleted.');
    }
}