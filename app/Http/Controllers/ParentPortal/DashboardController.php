<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSubject;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $children = $user->children()->with(['classRoom.teachers', 'subjects'])->get();
        $classIds = $children->pluck('class_id')->filter()->unique();

        // Latest three only. "View all" opens the full list.
        $announcements = Announcement::with(['user', 'attachments'])
            ->forParent($user)
            ->latest('announcements.published_at')
            ->take(3)
            ->get();

        // Homework, assignments, projects and tests still to do
        $pendingBase  = Announcement::forParent($user)->dueSoon()->notDoneBy($user);
        $pendingTotal = (clone $pendingBase)->count();
        $pending      = (clone $pendingBase)
            ->with('user')
            ->orderBy('announcements.due_date')
            ->orderBy('announcements.id')
            ->take(4)
            ->get();

        // "classId-subjectId" => the teacher who teaches that subject in that class
        $subjectTeachers = ClassSubject::with('teacher')
            ->whereIn('class_id', $classIds)
            ->whereNotNull('teacher_id')
            ->get()
            ->keyBy(fn ($r) => $r->class_id . '-' . $r->subject_id);

        $messageableIds = $user->messageableSchoolStaff()->pluck('users.id')->map(fn ($id) => (int) $id);

        return view('dashboards.parent', compact('children', 'announcements', 'pending', 'pendingTotal', 'subjectTeachers', 'messageableIds'));
    }
}