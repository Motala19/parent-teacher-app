<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Support\Attachments;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

    private function classes()
    {
        return ClassRoom::where('school_id', $this->schoolId())->orderBy('grade')->orderBy('name')->get();
    }

    private function rules(): array
    {
        return [
            'class_id' => ['nullable', Rule::in($this->classes()->pluck('id')->all())],
            'type'     => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|max:5000',
        ] + Attachments::announcementRules();
    }

    public function index(Request $request)
    {
        $announcements = Announcement::with(['user', 'classRoom', 'attachments'])
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
        return view('school.announcements.create', [
            'classes' => $this->classes(),
            'types'   => Announcement::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), Attachments::announcementMessages());

        $announcement = Announcement::create(Arr::only($data, ['class_id', 'type', 'title', 'body']) + [
            'school_id'    => $this->schoolId(),
            'user_id'      => auth()->id(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $count = Attachments::store($announcement, $request->file('files'), auth()->user());

        return redirect()->route('school.announcements.index')
            ->with('success', ($announcement->type === 'newsletter' ? 'Newsletter sent' : 'Announcement posted')
                . ($count ? " with {$count} " . Str::plural('file', $count) : '') . '.');
    }

    public function edit(Announcement $announcement)
    {
        $this->own($announcement)->load(['classRoom', 'attachments']);

        return view('school.announcements.edit', [
            'announcement' => $announcement,
            'classes'      => $this->classes(),
            'types'        => Announcement::TYPES,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->own($announcement);

        $data = $request->validate($this->rules() + [
            'remove_files'   => 'nullable|array',
            'remove_files.*' => 'integer',
        ], Attachments::announcementMessages());

        $announcement->update(Arr::only($data, ['class_id', 'type', 'title', 'body']));

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