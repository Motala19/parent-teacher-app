<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $classes = $user->myClasses()
            ->withCount('students')
            ->orderBy('grade')->orderBy('name')
            ->get();

        $notices = Announcement::with(['user', 'attachments'])
            ->where('school_id', $user->school_id)
            ->whereNull('class_id')
            ->where('is_published', true)
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('dashboards.teacher', [
            'classes'  => $classes,
            'learners' => $classes->sum('students_count'),
            'notices'  => $notices,
        ]);
    }
}