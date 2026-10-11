<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $schools = School::orderBy('name')->get();

        $classes = ClassRoom::when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->orderBy('grade')->orderBy('name')->get();

        $students = Student::with(['school', 'classRoom'])
            ->withCount('parents')
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('admission_number', 'like', "%{$s}%");
                });
            })
            ->orderBy('school_id')->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'schools', 'classes'));
    }
}