<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->query('show') === 'unread' ? 'unread' : 'all';

        $query = $filter === 'unread' ? $user->unreadNotifications() : $user->notifications();

        $notifications = $query->reorder()
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    // Marks it as read, then goes to where it points
    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->whereKey($id)->firstOrFail();

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return redirect(is_string($url) && str_starts_with($url, '/') ? $url : route('dashboard'));
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function settings()
    {
        return view('notifications.settings', ['user' => auth()->user()]);
    }

    public function updateSettings(Request $request)
    {
        auth()->user()
            ->forceFill(['email_notifications' => $request->boolean('email_notifications')])
            ->save();

        return redirect()->route('notifications.settings')->with('success', 'Notification settings saved.');
    }
}