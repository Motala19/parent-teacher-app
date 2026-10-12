<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $me   = auth()->user();
        $type = array_key_exists((string) $request->type, Announcement::TYPES) ? $request->type : null;

        $announcements = Announcement::with(['user', 'attachments'])
            ->forStaff($me)
            ->where('announcements.user_id', '!=', $me->id)
            ->when($type, fn ($q) => $q->where('announcements.type', $type))
            ->latest('announcements.published_at')
            ->paginate(15)
            ->withQueryString();

        return view('teacher.notices.index', compact('announcements', 'type'));
    }
}