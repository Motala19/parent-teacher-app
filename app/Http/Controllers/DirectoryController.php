<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    // Learners with their parents' contact details
    public function learners(Request $request)
    {
        $me      = auth()->user();
        $isAdmin = $me->hasRole('school-admin');

        $classes = $isAdmin
            ? ClassRoom::where('school_id', $me->school_id)->orderBy('grade')->orderBy('name')->get()
            : $me->myClasses()->orderBy('grade')->orderBy('name')->get();

        $students = Student::with(['classRoom', 'parents'])
            ->where('school_id', $me->school_id)
            ->where('is_active', true)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('class_id', $classes->pluck('id')))
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('admission_number', 'like', "%{$s}%")
                      ->orWhereHas('parents', function ($p) use ($s) {
                          $p->where('name', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                      });
                });
            })
            ->orderBy('class_id')->orderBy('last_name')->orderBy('first_name')
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