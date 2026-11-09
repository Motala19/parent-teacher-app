<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Support\ParentLinker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentParentController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Student $student): Student
    {
        abort_unless($student->school_id === $this->schoolId(), 404);
        return $student;
    }

    public function store(Request $request, Student $student)
    {
        $this->own($student);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|required_without:phone',
            'phone'        => 'nullable|string|max:30|required_without:email',
            'relationship' => ['required', Rule::in(['mother', 'father', 'guardian', 'other'])],
        ]);

        $result = ParentLinker::attach(
            $this->schoolId(),
            $data,
            collect([$student]),
            $request->only(['duplicate_choice', 'confirm_siblings'])
        );

        if ($result['status'] === 'conflict') {
            return redirect()->route('school.students.edit', $student)
                ->withInput()
                ->with('conflict', $result['conflict']);
        }

        return redirect()->route('school.students.edit', $student)
            ->with('success', $result['reused']
                ? "Existing parent {$result['parent']->name} linked to this learner."
                : 'New parent account created and linked.')
            ->with('temp_password', $result['temp'] ? "{$result['parent']->email} / {$result['temp']}" : null);
    }

    public function destroy(Student $student, User $parent)
    {
        $this->own($student);

        $student->parents()->detach($parent->id);

        return redirect()->route('school.students.edit', $student)
            ->with('success', 'Parent unlinked from this learner.');
    }
}