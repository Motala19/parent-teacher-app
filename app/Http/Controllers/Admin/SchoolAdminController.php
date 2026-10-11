<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SchoolAdminController extends Controller
{
    public function index(Request $request)
    {
        $schools = School::orderBy('name')->get();

        $admins = User::role('school-admin')
            ->with('school')
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->orderBy('school_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.school-admins.index', compact('admins', 'schools'));
    }

    public function create()
    {
        $schools = School::where('is_active', true)->orderBy('name')->get();
        return view('admin.school-admins.create', compact('schools'));
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

        User::create($data)->assignRole('school-admin');

        return redirect()->route('admin.school-admins.index')
            ->with('success', 'School admin created successfully.');
    }

    public function edit(User $schoolAdmin)
    {
        abort_unless($schoolAdmin->hasRole('school-admin'), 404);

        $schools = School::where('is_active', true)->orderBy('name')->get();
        return view('admin.school-admins.edit', ['admin' => $schoolAdmin, 'schools' => $schools]);
    }

    public function update(Request $request, User $schoolAdmin)
    {
        abort_unless($schoolAdmin->hasRole('school-admin'), 404);

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($schoolAdmin->id)],
            'phone'     => 'nullable|string|max:30',
            'password'  => 'nullable|string|min:8|confirmed',
            'school_id' => 'required|exists:schools,id',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $schoolAdmin->update($data);

        return redirect()->route('admin.school-admins.index')
            ->with('success', 'School admin updated successfully.');
    }

    public function destroy(User $schoolAdmin)
    {
        abort_unless($schoolAdmin->hasRole('school-admin'), 404);

        $schoolAdmin->delete();

        return redirect()->route('admin.school-admins.index')
            ->with('success', 'School admin deleted successfully.');
    }
}