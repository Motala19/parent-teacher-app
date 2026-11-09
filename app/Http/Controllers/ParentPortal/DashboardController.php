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

        // Latest three only (documents live under "School documents"). "View all" opens the rest.
        $announcements = Announcement::with(['user', 'attachments', 'targets'])
            ->forParent($user)
            ->notDismissedBy($user)
            ->notDocuments()
           ->pinnedFirst()
            ->take(3)
            ->get();

        // Homework, assignments, projects and tests still to do
        $pendingBase  = Announcement::forParent($user)->notDismissedBy($user)->dueSoon()->notDoneBy($user);
        $pendingTotal = (clone $pendingBase)->count();
        $pending      = (clone $pendingBase)
            ->with(['user', 'targets'])
            ->orderBy('announcements.due_date')
            ->orderBy('announcements.id')
            ->take(4)
            ->get();

        $subjectTeachers = ClassSubject::with('teacher')
            ->whereIn('class_id', $classIds)
            ->whereNotNull('teacher_id')
            ->get()
            ->keyBy(fn ($r) => $r->class_id . '-' . $r->subject_id);

        $messageableIds = $user->messageableSchoolStaff()->pluck('users.id')->map(fn ($id) => (int) $id);

        return view('dashboards.parent', compact('children', 'announcements', 'pending', 'pendingTotal', 'subjectTeachers', 'messageableIds'));
    }
}