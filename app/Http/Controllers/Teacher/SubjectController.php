<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Support\TeacherSubjects;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // My subjects: one card per subject with the classes it is taught in
    public function index()
    {
        return view('teacher.subjects.index', [
            'subjects' => TeacherSubjects::summary(auth()->user()),
        ]);
    }

    // The learners taking this subject, grouped by class (optionally filtered by grade)
    public function show(Request $request, Subject $subject)
    {
        $me = auth()->user();

        abort_unless((int) $subject->school_id === (int) $me->school_id, 404);

        $assignments = $me->subjectAssignments()
            ->where('class_subject.subject_id', $subject->id)
            ->with('classRoom')
            ->get()
            ->sortBy(fn ($a) => [Subject::gradeRank($a->classRoom->grade), $a->classRoom->name])
            ->values();

        // Not assigned to this subject = not allowed
        abort_if($assignments->isEmpty(), 404);

        $grades = $assignments->pluck('classRoom.grade')->filter()->unique()->values();
        $grade  = $grades->contains($request->grade) ? $request->grade : null;

        $visible = $grade
            ? $assignments->filter(fn ($a) => (string) $a->classRoom->grade === (string) $grade)->values()
            : $assignments;

        $students = Student::with(['classRoom', 'parents'])
            ->where('students.school_id', $me->school_id)
            ->where('students.is_active', true)
            ->whereIn('students.class_id', $visible->pluck('class_id')->all())
            ->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subject->id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('students.first_name', 'like', "%{$s}%")
                      ->orWhere('students.last_name', 'like', "%{$s}%")
                      ->orWhere('students.admission_number', 'like', "%{$s}%");
                });
            })
            ->orderBy('students.last_name')->orderBy('students.first_name')
            ->get()
            ->groupBy('class_id');

        $groups = $visible->map(fn ($a) => (object) [
            'class'    => $a->classRoom,
            'students' => $students->get($a->class_id, collect()),
        ]);

        $total = $groups->sum(fn ($g) => $g->students->count());

        return view('teacher.subjects.show', compact('subject', 'groups', 'grades', 'grade', 'total'));
    }
}