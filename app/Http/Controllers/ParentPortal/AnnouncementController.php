<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementCompletion;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    private function done()
    {
        $id = auth()->id();

        return ['completions as done' => fn ($q) => $q->where('user_id', $id)];
    }

    // One notice this parent is allowed to see, or 404
    private function visible(Announcement $announcement): Announcement
    {
        return Announcement::with(['user', 'attachments'])
            ->withExists($this->done())
            ->forParent(auth()->user())
            ->whereKey($announcement->id)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $user    = auth()->user();
        $type    = array_key_exists((string) $request->type, Announcement::TYPES) ? $request->type : null;
        $dueOnly = $request->boolean('due');

        $announcements = Announcement::with(['user', 'attachments'])
            ->withExists($this->done())
            ->forParent($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type))
            ->when($dueOnly, fn ($q) => $q->dueSoon()->notDoneBy($user)->orderBy('announcements.due_date'))
            ->when(! $dueOnly, fn ($q) => $q->latest('announcements.published_at'))
            ->paginate(15)
            ->withQueryString();

        return view('parent.announcements.index', compact('announcements', 'type'));
    }

    public function show(Announcement $announcement)
    {
        return view('parent.announcements.show', ['a' => $this->visible($announcement)]);
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
}