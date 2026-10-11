<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class ClassLearnerController extends Controller
{
    private function managed(ClassRoom $class): ClassRoom
    {
        $user = auth()->user();

        abort_unless(
            $class->school_id === $user->school_id && $user->managesClass($class),
            404
        );

        return $class;
    }

    // Active learners of this school, in the class's grade, not yet in any class
    private function available(ClassRoom $class)
    {
        return Student::where('school_id', $class->school_id)
            ->where('is_active', true)
            ->whereNull('class_id')
            ->where('grade', $class->grade);
    }

    public function create(Request $request, ClassRoom $class)
    {
        $this->managed($class);

        $students = collect();

        // Without a grade, nothing is listed (a null grade would match every ungraded learner)
        if ($class->grade) {
            $students = $this->available($class)
                ->when($request->search, function ($q, $s) {
                    $q->where(function ($q) use ($s) {
                        $q->where('first_name', 'like', "%{$s}%")
                          ->orWhere('last_name', 'like', "%{$s}%")
                          ->orWhere('admission_number', 'like', "%{$s}%");
                    });
                })
                ->orderBy('last_name')->orderBy('first_name')
                ->get();
        }

        return view('teacher.classes.add-learners', compact('class', 'students'));
    }

    public function store(Request $request, ClassRoom $class)
    {
        $this->managed($class);

        if (! $class->grade) {
            return back()->withErrors(['student_ids' => 'This class has no grade set. Ask the school admin to set it.']);
        }

        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ], ['student_ids.required' => 'Select at least one learner.']);

        // Only learners that are still available can be added (blocks double assignment)
        $students = $this->available($class)->whereIn('id', $data['student_ids'])->get();

        foreach ($students as $student) {
            $student->update(['class_id' => $class->id]);
            $student->refresh()->applyDefaultSubjects();
        }

        $skipped = count($data['student_ids']) - $students->count();

        return redirect()->route('teacher.classes.show', $class)
            ->with('success', $students->count() . ' learner(s) added to ' . $class->name . '.'
                . ($skipped ? " {$skipped} skipped (already in a class or not in this grade)." : ''));
    }

    public function destroy(ClassRoom $class, Student $student)
    {
        $this->managed($class);

        abort_unless((int) $student->class_id === (int) $class->id, 404);

        $student->update(['class_id' => null]);
        $student->refresh()->applyDefaultSubjects(); // clears the class's subjects

        return redirect()->route('teacher.classes.show', $class)
            ->with('success', $student->first_name . ' ' . $student->last_name . ' removed from the class.');
    }
}