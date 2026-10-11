<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');

        $stats = [
            'Classes'                  => ClassRoom::where('school_id', $id)->count(),
            'Teachers'                 => User::role('teacher')->where('school_id', $id)->count(),
            'Learners'                 => Student::where('school_id', $id)->count(),
            'Parents'                  => User::role('parent')->where('school_id', $id)->count(),
            'Learners without parents' => Student::where('school_id', $id)->doesntHave('parents')->count(),
        ];

        return view('dashboards.school', compact('stats'));
    }
}