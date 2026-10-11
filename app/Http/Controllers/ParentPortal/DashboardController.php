<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $children = $user->children()->with('classRoom.teachers')->get();
        $classIds = $children->pluck('class_id')->filter()->unique();

        $announcements = Announcement::with(['user', 'classRoom', 'attachments'])
            ->where('school_id', $user->school_id)
            ->where('is_published', true)
            ->where(function ($q) use ($classIds) {
                $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
            })
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('dashboards.parent', compact('children', 'announcements'));
    }
}