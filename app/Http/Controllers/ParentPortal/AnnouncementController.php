<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementCompletion;
use App\Models\AnnouncementDismissal;
use Illuminate\Http\Request;
use App\Models\AnnouncementView;

class AnnouncementController extends Controller
{
    private function doneFlag(): array
    {
        $id = auth()->id();

        return ['completions as done' => fn ($q) => $q->where('user_id', $id)];
    }

    private function children()
    {
        return auth()->user()->children()->with(['classRoom', 'subjects'])->get();
    }

    // One notice this parent is allowed to see, or 404
    private function visible(Announcement $announcement): Announcement
    {
        return Announcement::with(['user', 'attachments', 'targets'])
            ->withExists($this->doneFlag())
            ->forParent(auth()->user())
            ->whereKey($announcement->id)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $user    = auth()->user();
        $type    = array_key_exists((string) $request->type, Announcement::TYPES) ? $request->type : null;
        $dueOnly = $request->boolean('due');

        $announcements = Announcement::with(['user', 'attachments', 'targets'])
            ->withExists($this->doneFlag())
            ->forParent($user)
            ->notDismissedBy($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type), fn ($q) => $q->notDocuments())
            ->when($dueOnly, fn ($q) => $q->dueSoon()->notDoneBy($user)->orderBy('announcements.due_date'))
            ->when(! $dueOnly, fn ($q) => $q->pinnedFirst())
            ->paginate(15)
            ->withQueryString();

        return view('parent.announcements.index', [
            'announcements' => $announcements,
            'type'          => $type,
            'children'      => $this->children(),
        ]);
    }

        public function show(Announcement $announcement)
    {
        $a = $this->visible($announcement);

        AnnouncementView::firstOrCreate(['announcement_id' => $a->id, 'user_id' => auth()->id()]);

        return view('parent.announcements.show', [
            'a'        => $a,
            'children' => $this->children(),
        ]);
    }

    public function done_(Announcement $announcement)
    {
        $a = $this->visible($announcement);

        abort_unless(Announcement::takesDue($a->type), 422);

        AnnouncementCompletion::firstOrCreate(['announcement_id' => $a->id, 'user_id' => auth()->id()]);

        return back();
    }

    public function undone(Announcement $announcement)
    {
        $a = $this->visible($announcement);

        AnnouncementCompletion::where('announcement_id', $a->id)->where('user_id', auth()->id())->delete();

        return back();
    }

    public function dismiss(Announcement $announcement)
    {
        $a = $this->visible($announcement);

        AnnouncementDismissal::firstOrCreate(['announcement_id' => $a->id, 'user_id' => auth()->id()]);

        return redirect()->route('parent.announcements.index')->with('success', 'Notice cleared from your list.');
    }

    public function dismissAll(Request $request)
    {
        $user = auth()->user();

        $ids = Announcement::forParent($user)->notDismissedBy($user)->notDocuments()->pluck('announcements.id');
        $now = now();

        $ids->chunk(500)->each(function ($chunk) use ($user, $now) {
            AnnouncementDismissal::insertOrIgnore(
                $chunk->map(fn ($id) => ['announcement_id' => $id, 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now])->all()
            );
        });

        return redirect()->route('parent.announcements.index')->with('success', 'Your notices were cleared.');
    }
}