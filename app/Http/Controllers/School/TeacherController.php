<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(User $teacher): User
    {
        abort_unless(
            $teacher->school_id === $this->schoolId() && $teacher->hasRole('teacher'),
            404
        );
        return $teacher;
    }

    public function index()
    {
        $schoolId = $this->schoolId();

        $teachers = User::role('teacher')
            ->with(['classes', 'teachingSubjects'])
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->paginate(15);

        $classNames = ClassRoom::where('school_id', $schoolId)->pluck('name', 'id');

        return view('school.teachers.index', compact('teachers', 'classNames'));
    }

    public function create()
    {
        return view('school.teachers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'is_smt'   => 'nullable|boolean',
            'position' => ['nullable', Rule::in(User::SMT_POSITIONS)],
        ]);

        $data['is_smt']    = $request->boolean('is_smt');
        $data['position']  = $data['is_smt'] ? ($data['position'] ?? null) : null;
        $data['password']  = Hash::make($data['password']);
        $data['school_id'] = $this->schoolId();

        User::create($data)->assignRole('teacher');

        return redirect()->route('school.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    public function edit(User $teacher)
    {
        $this->own($teacher);

        return view('school.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, User $teacher)
    {
        $this->own($teacher);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->id)],
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            'is_smt'   => 'nullable|boolean',
            'position' => ['nullable', Rule::in(User::SMT_POSITIONS)],
        ]);

        $data['is_smt']   = $request->boolean('is_smt');
        $data['position'] = $data['is_smt'] ? ($data['position'] ?? null) : null;

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update($data);

        return redirect()->route('school.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(User $teacher)
    {
        $this->own($teacher)->delete();

        return redirect()->route('school.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }
}