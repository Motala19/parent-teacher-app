<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
   public function index(Request $request)
{
    $schools = School::orderBy('name')->get();

    $teachers = User::role('teacher')
        ->with(['school', 'classes'])
        ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
        ->orderBy('school_id')
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    return view('admin.teachers.index', compact('teachers', 'schools'));
}

    public function create()
    {
        $schools = School::where('is_active', true)->orderBy('name')->get();
        return view('admin.teachers.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:30',
            'password'  => 'required|string|min:8|confirmed',
            'school_id' => 'required|exists:schools,id',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data)->assignRole('teacher');

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    public function edit(User $teacher)
    {
        abort_unless($teacher->hasRole('teacher'), 404);

        $schools = School::where('is_active', true)->orderBy('name')->get();
        return view('admin.teachers.edit', compact('teacher', 'schools'));
    }

    public function update(Request $request, User $teacher)
    {
        abort_unless($teacher->hasRole('teacher'), 404);

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->id)],
            'phone'     => 'nullable|string|max:30',
            'password'  => 'nullable|string|min:8|confirmed',
            'school_id' => 'required|exists:schools,id',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update($data);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(User $teacher)
    {
        abort_unless($teacher->hasRole('teacher'), 404);

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }
}