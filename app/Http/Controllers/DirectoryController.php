<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    // Learners with their parents' contact details.
    // Class teachers see the whole class. Subject teachers see the learners taking their subject.
    public function learners(Request $request)
    {
        $me      = auth()->user();
        $isAdmin = $me->hasRole('school-admin');

        $classes = $isAdmin
            ? ClassRoom::where('school_id', $me->school_id)->orderBy('grade')->orderBy('name')->get()
            : $me->involvedClasses()->orderBy('grade')->orderBy('name')->get();

        $base = $isAdmin
            ? Student::where('students.school_id', $me->school_id)
            : $me->myStudents();

        $students = $base
            ->with(['classRoom', 'parents'])
            ->where('students.is_active', true)
            ->when($request->class_id, fn ($q, $id) => $q->where('students.class_id', $id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('students.first_name', 'like', "%{$s}%")
                      ->orWhere('students.last_name', 'like', "%{$s}%")
                      ->orWhere('students.admission_number', 'like', "%{$s}%")
                      ->orWhereHas('parents', function ($p) use ($s) {
                          $p->where('name', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                      });
                });
            })
            ->orderBy('students.class_id')->orderBy('students.last_name')->orderBy('students.first_name')
            ->paginate(20)
            ->withQueryString();

        return view('directory.learners', compact('students', 'classes'));
    }

    // Colleagues: teachers and school admins at the same school
    public function staff(Request $request)
    {
        $staff = auth()->user()->messageableStaff()
            ->with(['roles', 'classes', 'teachingSubjects'])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('directory.staff', compact('staff'));
    }
}