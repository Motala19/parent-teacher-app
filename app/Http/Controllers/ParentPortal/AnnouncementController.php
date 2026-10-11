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

        $classIds = $user->children()
            ->whereNotNull('students.class_id')
            ->pluck('students.class_id')
            ->unique();

        $announcements = Announcement::with(['user', 'classRoom', 'attachments'])
            ->where('school_id', $user->school_id)
            ->where('is_published', true)
            ->where(function ($q) use ($classIds) {
                $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
            })
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString();

        return view('parent.announcements.index', compact('announcements', 'type'));
    }
}