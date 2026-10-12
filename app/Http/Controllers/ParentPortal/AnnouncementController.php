<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $type = array_key_exists((string) $request->type, Announcement::TYPES) ? $request->type : null;

        $announcements = Announcement::with(['user', 'attachments'])
            ->forParent($user)
            ->when($type, fn ($q) => $q->where('announcements.type', $type))
            ->latest('announcements.published_at')
            ->paginate(15)
            ->withQueryString();

        return view('parent.announcements.index', compact('announcements', 'type'));
    }
}