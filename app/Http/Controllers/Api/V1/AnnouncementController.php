<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementCompletion;
use App\Models\AnnouncementDismissal;
use App\Models\User;
use App\Support\ApiFormat;
use Illuminate\Http\Request;
use App\Models\AnnouncementView;

class AnnouncementController extends Controller
{
    private function doneFlag(int $userId): array
    {
        return ['completions as done' => fn ($q) => $q->where('user_id', $userId)];
    }

    private function children(User $user)
    {
        return $user->children()->with(['classRoom', 'subjects'])->get();
    }

    // One notice this parent may see, or 404
    private function visible(Request $request, int $id): Announcement
    {
        $user = $request->user();

        return Announcement::with(['user', 'attachments', 'targets'])
            ->withExists($this->doneFlag($user->id))
            ->forParent($user)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function typeFrom(Request $request, string $key = 'type'): ?string
    {
        $type = (string) $request->input($key);

        return array_key_exists($type, Announcement::TYPES) ? $type : null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $type = $this->typeFrom($request);

        // With no filter, school documents are left out. The School tab asks for them by type.
        $page = Announcement::with(['user', 'targets'])
            ->withCount('attachments')
            ->withExists($this->doneFlag($user->id))
            ->forParent($user)
            ->notDismissedBy($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type), fn ($q) => $q->notDocuments())
            ->pinnedFirst()
            ->paginate(15);

        $children = $this->children($user);

        return response()->json([
            'data'      => $page->getCollection()->map(fn ($a) => ApiFormat::announcement($a, false, $children))->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

        // The whole notice. Opening it counts as "seen".
    public function show(Request $request, int $id)
    {
        $a = $this->visible($request, $id);

        AnnouncementView::firstOrCreate(['announcement_id' => $a->id, 'user_id' => $request->user()->id]);

        return response()->json(ApiFormat::announcement($a, true, $this->children($request->user())));
    }

    // Everything still to do, plus what was recently completed
    public function pending(Request $request)
    {
        $user     = $request->user();
        $children = $this->children($user);

        $base = fn () => Announcement::with(['user', 'targets'])->withCount('attachments')->forParent($user)->notDismissedBy($user);

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
            'pending' => $pending->map(fn ($a) => ApiFormat::announcement($a, false, $children))->values(),
            'done'    => $done->map(fn ($a) => ApiFormat::announcement($a, false, $children))->values(),
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

    // Clear one notice from this parent's list. It stays in the database.
    public function dismiss(Request $request, int $id)
    {
        $a = $this->visible($request, $id);

        AnnouncementDismissal::firstOrCreate(['announcement_id' => $a->id, 'user_id' => $request->user()->id]);

        return response()->json(['ok' => true]);
    }

    // Clear everything in the current list (optionally just one type). Documents are only cleared by type.
    public function dismissAll(Request $request)
    {
        $user = $request->user();
        $type = $this->typeFrom($request);

        $ids = Announcement::forParent($user)
            ->notDismissedBy($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type), fn ($q) => $q->notDocuments())
            ->pluck('announcements.id');

        $now = now();

        $ids->chunk(500)->each(function ($chunk) use ($user, $now) {
            AnnouncementDismissal::insertOrIgnore(
                $chunk->map(fn ($id) => ['announcement_id' => $id, 'user_id' => $user->id, 'created_at' => $now, 'updated_at' => $now])->all()
            );
        });

        return response()->json(['cleared' => $ids->count()]);
    }
}