<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Subject;
use App\Support\Attachments;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    private function gradeName(string $grade): string
    {
        return $grade === 'R' ? 'Grade R' : 'Grade ' . $grade;
    }

    private function learners(int $n): string
    {
        return $n . ' ' . Str::plural('learner', $n);
    }

    // Active learners of one class who take a subject
    private function classSubjectCount(int $classId, int $subjectId): int
    {
        return DB::table('students')
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->whereIn('id', DB::table('student_subject')->where('subject_id', $subjectId)->select('student_id'))
            ->count();
    }

    // Active learners in a whole grade (any class) who take a subject
    private function gradeSubjectCount(int $schoolId, string $grade, int $subjectId): int
    {
        return DB::table('students')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($w) use ($grade, $schoolId) {
                $w->where('grade', $grade)
                  ->orWhereIn('class_id', DB::table('classes')->where('school_id', $schoolId)->where('grade', $grade)->select('id'));
            })
            ->whereIn('id', DB::table('student_subject')->where('subject_id', $subjectId)->select('student_id'))
            ->count();
    }

    // Everything this teacher may post to. Each option carries its own targets.
    private function audiences(): Collection
    {
        $me       = auth()->user();
        $schoolId = (int) $me->school_id;
        $options  = collect();

        // 1. Class teacher: the whole class
        $classes = $me->myClasses()
            ->withCount(['students' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('grade')->orderBy('name')
            ->get();

        foreach ($classes as $class) {
            $options->put('class:' . $class->id, [
                'group'          => 'As class teacher',
                'label'          => $class->name . ' · whole class (' . $this->learners((int) $class->students_count) . ')',
                'audience_label' => $class->name,
                'hint'           => 'Parents of every learner in ' . $class->name . '.',
                'learners'       => (int) $class->students_count,
                'targets'        => [['class_id' => $class->id, 'grade' => null, 'subject_id' => null]],
            ]);
        }

        // 2. Subject teacher: one block of options per subject
        $bySubject = $me->subjectAssignments()->with(['subject', 'classRoom'])->get()
            ->groupBy('subject_id')
            ->sortBy(fn ($rows) => $rows->first()->subject->name);

        foreach ($bySubject as $subjectId => $rows) {
            $subjectId = (int) $subjectId;
            $subject   = $rows->first()->subject;

            $rows = $rows
                ->sortBy(fn ($r) => [Subject::gradeRank($r->classRoom->grade), $r->classRoom->name])
                ->values();

            $perClass = $rows->mapWithKeys(fn ($r) => [
                $r->class_id => $this->classSubjectCount((int) $r->class_id, $subjectId),
            ]);

            // 2a. Whole grade: every learner in the grade taking this subject, in all classes
            $grades = $rows->pluck('classRoom.grade')
                ->map(fn ($g) => (string) $g)->filter()->unique()
                ->sortBy(fn ($g) => Subject::gradeRank($g))->values();

            foreach ($grades as $grade) {
                $count = $this->gradeSubjectCount($schoolId, $grade, $subjectId);

                $options->put("grade:{$grade}:{$subjectId}", [
                    'group'          => $subject->name,
                    'label'          => $this->gradeName($grade) . ' · whole grade (' . $this->learners($count) . ' taking ' . $subject->name . ')',
                    'audience_label' => $subject->name . ' · ' . $this->gradeName($grade),
                    'hint'           => 'Parents of every ' . $this->gradeName($grade) . ' learner taking ' . $subject->name
                                        . ', in all classes, including classes taught by other teachers.',
                    'learners'       => $count,
                    'targets'        => [['class_id' => null, 'grade' => $grade, 'subject_id' => $subjectId]],
                ]);
            }

            // 2b. All the classes I teach this subject in
            if ($rows->count() > 1) {
                $names = $rows->map(fn ($r) => $r->classRoom->name)->join(', ');

                $options->put("all:{$subjectId}", [
                    'group'          => $subject->name,
                    'label'          => 'All my ' . $subject->name . ' classes (' . $names . ')',
                    'audience_label' => $subject->name . ' · ' . $names,
                    'hint'           => 'Parents of learners taking ' . $subject->name . ' in the classes you teach: ' . $names . '.',
                    'learners'       => (int) $perClass->sum(),
                    'targets'        => $rows->map(fn ($r) => [
                        'class_id' => $r->class_id, 'grade' => null, 'subject_id' => $subjectId,
                    ])->values()->all(),
                ]);
            }

            // 2c. One class: only learners taking the subject
            foreach ($rows as $r) {
                $count = (int) $perClass->get($r->class_id, 0);

                $options->put('subject:' . $r->class_id . ':' . $subjectId, [
                    'group'          => $subject->name,
                    'label'          => $r->classRoom->name . ' · learners taking ' . $subject->name . ' (' . $this->learners($count) . ')',
                    'audience_label' => $subject->name . ' · ' . $r->classRoom->name,
                    'hint'           => 'Parents of ' . $r->classRoom->name . ' learners who take ' . $subject->name . '.',
                    'learners'       => $count,
                    'targets'        => [['class_id' => $r->class_id, 'grade' => null, 'subject_id' => $subjectId]],
                ]);
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
        Announcement::with('attachments')->withCount('views')
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
            'due_date' => Announcement::dueRules(null, $request->input('due_date')),
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|max:5000',
        ] + Attachments::announcementRules(), Attachments::announcementMessages() + Announcement::dueMessages() + [
            'audience.required' => 'Choose who should receive this.',
            'audience.in'       => 'You can only post to your own classes and subjects.',
        ]);

        $target = $audiences->get($data['audience']);

        if (($target['learners'] ?? 1) < 1) {
            return back()->withInput()->withErrors([
                'audience' => 'No learners are enrolled for that audience yet, so there is nobody to send this to.',
            ]);
        }

        $announcement = Announcement::create([
            'school_id'      => $user->school_id,
            'user_id'        => $user->id,
            'audience'       => 'classes',
            'audience_label' => $target['audience_label'],
            'type'           => $data['type'],
            'due_date'       => Announcement::dueValue($data['type'], $data['due_date'] ?? null),
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
                . ($announcement->due_date ? ' · ' . $announcement->dueLabel() : '')
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
            'due_date'       => Announcement::dueRules($announcement, $request->input('due_date')),
            'title'          => 'required|string|max:255',
            'body'           => 'required|string|max:5000',
            'remove_files'   => 'nullable|array',
            'remove_files.*' => 'integer',
        ] + Attachments::announcementRules(), Attachments::announcementMessages() + Announcement::dueMessages());

        $announcement->update([
            'type'     => $data['type'],
            'due_date' => Announcement::dueValue($data['type'], $data['due_date'] ?? null),
            'title'    => $data['title'],
            'body'     => $data['body'],
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