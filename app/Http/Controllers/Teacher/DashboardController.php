<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\TeacherSubjects;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $classes = $user->myClasses()
            ->withCount('students')
            ->orderBy('grade')->orderBy('name')
            ->get();

        $subjects = TeacherSubjects::summary($user);

        $learners = $user->myStudents()->where('students.is_active', true)->count();

        $notices = Announcement::with(['user', 'attachments'])
            ->forStaff($user)
            ->where('announcements.user_id', '!=', $user->id)
            ->latest('announcements.published_at')
            ->take(5)
            ->get();

        return view('dashboards.teacher', compact('classes', 'subjects', 'learners', 'notices'));
    }
}