<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'Schools'       => School::count(),
            'School Admins' => User::role('school-admin')->count(),
            'Classes'       => ClassRoom::count(),
            'Teachers'      => User::role('teacher')->count(),
            'Learners'      => Student::count(),
            'Parents'       => User::role('parent')->count(),
        ];

        return view('dashboards.admin', compact('stats'));
    }
}