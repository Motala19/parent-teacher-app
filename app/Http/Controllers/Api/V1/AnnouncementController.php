<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementCompletion;
use App\Support\ApiFormat;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    private function doneFlag(int $userId): array
    {
        return ['completions as done' => fn ($q) => $q->where('user_id', $userId)];
    }

    // One notice this parent may see, or 404
    private function visible(Request $request, int $id): Announcement
    {
        $user = $request->user();

        return Announcement::with(['user', 'attachments'])
            ->withExists($this->doneFlag($user->id))
            ->forParent($user)
            ->whereKey($id)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $type = array_key_exists((string) $request->query('type'), Announcement::TYPES) ? $request->query('type') : null;

        $page = Announcement::with('user')
            ->withCount('attachments')
            ->withExists($this->doneFlag($user->id))
            ->forParent($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type))
            ->latest('announcements.published_at')
            ->paginate(15);

        return response()->json([
            'data'      => $page->getCollection()->map(fn ($a) => ApiFormat::announcement($a))->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

    // The whole notice
    public function show(Request $request, int $id)
    {
        return response()->json(ApiFormat::announcement($this->visible($request, $id), true));
    }

    // Everything still to do, plus what was recently completed
    public function pending(Request $request)
    {
        $user = $request->user();

        $base = fn () => Announcement::with('user')->withCount('attachments')->forParent($user);

        $pending = $base()
            ->dueSoon()
            ->notDoneBy($user)
            ->orderBy('announcements.due_date')
            ->orderBy('announcements.id')
            ->take(100)
            ->get();

        $done = $base()
            ->whereIn('announcements.type', Announcement::DUE_TYPES)
            ->whereNotNull('announcements.due_date')
            ->whereDate('announcements.due_date', '>=', today()->subDays(14)->toDateString())
            ->doneBy($user)
            ->orderByDesc('announcements.due_date')
            ->take(30)
            ->get()
            ->each(fn ($a) => $a->setAttribute('done', true));

        return response()->json([
            'pending' => $pending->map(fn ($a) => ApiFormat::announcement($a))->values(),
            'done'    => $done->map(fn ($a) => ApiFormat::announcement($a))->values(),
        ]);
    }

    public function done(Request $request, int $id)
    {
        $a = $this->visible($request, $id);

        abort_unless(Announcement::takesDue($a->type), 422, 'This notice has no due date.');

        AnnouncementCompletion::firstOrCreate(['announcement_id' => $a->id, 'user_id' => $request->user()->id]);

        return response()->json(['done' => true]);
    }

    public function undone(Request $request, int $id)
    {
        $a = $this->visible($request, $id);

        AnnouncementCompletion::where('announcement_id', $a->id)->where('user_id', $request->user()->id)->delete();

        return response()->json(['done' => false]);
    }
}