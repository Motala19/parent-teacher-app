<?php


namespace App\Http\Controllers\Admin;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ClassRoomController extends Controller
{
    public function index(Request $request)
{
    $schools = School::orderBy('name')->get();

    $classes = ClassRoom::with(['school', 'teachers'])
        ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
        ->orderBy('school_id')
        ->orderBy('grade')
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    return view('admin.classes.index', compact('classes', 'schools'));
}

    public function create()
    {
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $teachers = User::role('teacher')->orderBy('name')->get();

        return view('admin.classes.create', compact('schools', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id'        => 'required|exists:schools,id',
            'name'             => 'required|string|max:255',
            'grade'            => 'nullable|string|max:50',
            'year'             => 'nullable|integer',
            'class_teacher_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', $request->school_id),
],
        ]);

        $class = ClassRoom::create(
            Arr::except($validated, 'class_teacher_id') + ['is_active' => true]
        );

        if ($request->filled('class_teacher_id')) {
            $class->teachers()->attach($request->class_teacher_id, [
                'is_class_teacher' => true,
            ]);
        }

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class created successfully.');
    }

    public function edit(ClassRoom $class)
    {
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $teachers = User::role('teacher')->orderBy('name')->get();
        $class->load('teachers');

        return view('admin.classes.edit', compact('class', 'schools', 'teachers'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $validated = $request->validate([
            'school_id'        => 'required|exists:schools,id',
            'name'             => 'required|string|max:255',
            'grade'            => 'nullable|string|max:50',
            'year'             => 'nullable|integer',
            'is_active'        => 'boolean',
            'class_teacher_id' => [
    'nullable',
    Rule::exists('users', 'id')->where('school_id', $request->school_id),
],
        ]);

        $class->update(Arr::except($validated, 'class_teacher_id'));

        $class->teachers()->sync(
            $request->filled('class_teacher_id')
                ? [$request->class_teacher_id => ['is_class_teacher' => true]]
                : []
        );

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassRoom $class)
    {
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}