<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\User;

class ClassController extends Controller
{
    public function index()
    {
        $classes = auth()->user()->myClasses()
            ->withCount('students')
            ->orderBy('grade')->orderBy('name')
            ->get();

        return view('teacher.classes.index', compact('classes'));
    }

    public function show(ClassRoom $class)
    {
        $user = auth()->user();
        abort_unless($user->myClasses()->whereKey($class->id)->exists(), 404);

        $class->load('subjects');

        $students = $class->students()
            ->with('parents')
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $teachers = User::whereIn('id', $class->subjects->pluck('pivot.teacher_id')->filter()->all())
            ->get()->keyBy('id');

        $announcements = Announcement::whereHas('targets', fn ($q) => $q->where('class_id', $class->id))
            ->latest()
            ->take(5)
            ->get();

        $canManage = $user->managesClass($class);

        return view('teacher.classes.show', compact('class', 'students', 'teachers', 'announcements', 'canManage'));
    }
}