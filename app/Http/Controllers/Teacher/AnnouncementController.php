<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    private function classes()
    {
        return auth()->user()->myClasses()->orderBy('grade')->orderBy('name')->get();
    }

    private function own(Announcement $announcement): Announcement
    {
        abort_unless((int) $announcement->user_id === (int) auth()->id(), 404);
        return $announcement;
    }

    public function index(Request $request)
    {
        $announcements = Announcement::with(['classRoom', 'attachments'])
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
            'classes' => $this->classes(),
            'types'   => Announcement::teacherTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'class_id' => ['required', Rule::in($this->classes()->pluck('id')->all())],
            'type'     => ['required', Rule::in(array_keys(Announcement::teacherTypes()))],
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|max:5000',
        ] + Attachments::announcementRules(), Attachments::announcementMessages());

        $announcement = Announcement::create(Arr::only($data, ['class_id', 'type', 'title', 'body']) + [
            'school_id'    => $user->school_id,
            'user_id'      => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $count = Attachments::store($announcement, $request->file('files'), $user);

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'Announcement posted'
                . ($count ? " with {$count} " . Str::plural('file', $count) : '')
                . '. Parents of the class can now see it.');
    }

    public function edit(Announcement $announcement)
    {
        $this->own($announcement)->load(['classRoom', 'attachments']);

        return view('teacher.announcements.edit', [
            'announcement' => $announcement,
            'classes'      => $this->classes(),
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

        $announcement->update(Arr::only($data, ['type', 'title', 'body']));

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