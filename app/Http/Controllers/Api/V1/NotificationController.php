<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->user()->notifications()->reorder()->orderByDesc('updated_at')->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($n) => [
                'id'              => $n->id,
                'title'           => $n->data['title'] ?? 'Notification',
                'body'            => $n->data['body'] ?? '',
                'icon'            => $n->data['icon'] ?? 'bell',
                'kind'            => $n->data['kind'] ?? null,
                'sender_id'       => $n->data['sender_id'] ?? null,
                'announcement_id' => $n->data['announcement_id'] ?? null,
                'count'           => $n->data['count'] ?? 1,
                'read'            => (bool) $n->read_at,
                'created_at'      => $n->updated_at->toIso8601String(),
            ])->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // Clearing a notification is just a notification: it is removed.
    // The announcements and messages behind it are NOT affected.
    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }

    public function clear(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['ok' => true]);
    }
}