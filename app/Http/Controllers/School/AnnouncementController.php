<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use App\Support\Attachments;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Announcement $announcement): Announcement
    {
        abort_unless((int) $announcement->school_id === $this->schoolId(), 404);
        return $announcement;
    }

    private function activeClasses()
    {
        return ClassRoom::where('school_id', $this->schoolId())->where('is_active', true)->get();
    }

    public function index(Request $request)
    {
        $announcements = Announcement::with(['user', 'attachments'])
            ->where('school_id', $this->schoolId())
            ->when(
                array_key_exists((string) $request->type, Announcement::TYPES),
                fn ($q) => $q->where('type', $request->type)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('school.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $sid = $this->schoolId();

        $gradeClasses = $this->activeClasses()
            ->sortBy(fn ($c) => [Subject::gradeRank($c->grade), $c->name])
            ->groupBy(fn ($c) => (string) ($c->grade ?: ''));

        return view('school.announcements.create', [
            'gradeClasses' => $gradeClasses,
            'types'        => Announcement::TYPES,
            'teacherCount' => User::role('teacher')->where('school_id', $sid)->count(),
            'smtCount'     => User::smtMembers()->where('school_id', $sid)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $classes = $this->activeClasses();

        $data = $request->validate([
            'audience'  => ['required', Rule::in(Announcement::AUDIENCES)],
            'classes'   => ['required_if:audience,classes', 'nullable', 'array'],
            'classes.*' => ['integer', Rule::in($classes->pluck('id')->all())],
            'type'      => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'title'     => 'required|string|max:255',
            'body'      => 'required|string|max:5000',
        ] + Attachments::announcementRules(), Attachments::announcementMessages() + [
            'audience.required'    => 'Choose who should receive this.',
            'classes.required_if'  => 'Tick at least one grade or class.',
        ]);

        $selected = collect();

        if ($data['audience'] === 'classes') {
            $selected = $classes->whereIn('id', array_map('intval', $data['classes'] ?? []))->values();
            abort_if($selected->isEmpty(), 422);
        }

        $label = match ($data['audience']) {
            'school'   => 'Whole school',
            'teachers' => 'Teachers only',
            'smt'      => 'SMT only',
            default    => Announcement::labelForClasses($selected, $classes),
        };

        $announcement = Announcement::create([
            'school_id'      => $this->schoolId(),
            'user_id'        => auth()->id(),
            'audience'       => $data['audience'],
            'audience_label' => $label,
            'type'           => $data['type'],
            'title'          => $data['title'],
            'body'           => $data['body'],
            'is_published'   => true,
            'published_at'   => now(),
        ]);

        if ($selected->isNotEmpty()) {
            $announcement->targets()->createMany(
                $selected->map(fn ($c) => ['class_id' => $c->id, 'subject_id' => null])->all()
            );
        }

        $count    = Attachments::store($announcement, $request->file('files'), auth()->user());
        $notified = Notifier::announcement($announcement);

        return redirect()->route('school.announcements.index')
            ->with('success', ($announcement->type === 'newsletter' ? 'Newsletter sent' : 'Announcement posted') . " to {$label}"
                . ($count ? " with {$count} " . Str::plural('file', $count) : '')
                . ($notified ? ". {$notified} " . Str::plural('person', $notified) . ' notified.' : '.'));
    }

    public function edit(Announcement $announcement)
    {
        $this->own($announcement)->load('attachments');

        return view('school.announcements.edit', [
            'announcement' => $announcement,
            'types'        => Announcement::TYPES,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->own($announcement);

        $data = $request->validate([
            'type'           => ['required', Rule::in(array_keys(Announcement::TYPES))],
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

        return redirect()->route('school.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->own($announcement)->delete();

        return redirect()->route('school.announcements.index')
            ->with('success', 'Announcement and its files deleted.');
    }
}