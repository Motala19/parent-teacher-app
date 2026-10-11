<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentSubjectController extends Controller
{
    public function update(Request $request, Student $student)
    {
        $schoolId = auth()->user()->school_id;
        abort_if(is_null($schoolId), 403, 'Your account is not linked to a school.');
        abort_unless($student->school_id === $schoolId, 404);

        $allowed = Subject::where('school_id', $schoolId)->pluck('id')->all();

        $data = $request->validate([
            'subjects'   => 'nullable|array',
            'subjects.*' => [Rule::in($allowed)],
        ]);

        $student->subjects()->sync($data['subjects'] ?? []);

        return redirect()->route('school.students.edit', $student)
            ->with('success', 'Learner subjects updated.');
    }
}