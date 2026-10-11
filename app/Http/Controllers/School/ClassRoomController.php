<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Subject;

class ClassRoomController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(ClassRoom $class): ClassRoom
    {
        abort_unless($class->school_id === $this->schoolId(), 404);
        return $class;
    }

    private function teachers()
    {
        return User::role('teacher')->where('school_id', $this->schoolId())->orderBy('name')->get();
    }

    public function index()
    {
        $classes = ClassRoom::with('teachers')
            ->where('school_id', $this->schoolId())
            ->orderBy('grade')->orderBy('name')
            ->paginate(15);

        return view('school.classes.index', compact('classes'));
    }

    public function create()
    {
        return view('school.classes.create', ['teachers' => $this->teachers()]);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name'             => 'required|string|max:255',
        'grade'            => ['required', Rule::in(Subject::GRADES)],
        'year'             => 'nullable|integer',
        'class_teacher_id' => ['nullable', Rule::in($this->teachers()->pluck('id')->all())],
    ]);

    $class = ClassRoom::create([
        'school_id' => $this->schoolId(),
        'name'      => $validated['name'],
        'grade'     => $validated['grade'] ?? null,
        'year'      => $validated['year'] ?? null,
        'is_active' => true,
    ]);

    if (! empty($validated['class_teacher_id'])) {
        $class->teachers()->attach($validated['class_teacher_id'], ['is_class_teacher' => true]);
    }

    // The new class gets the subjects set up for its grade
    $gradeSubjects = Subject::forGrade($class->school_id, $class->grade)->pluck('id');
    if ($gradeSubjects->isNotEmpty()) {
        $class->subjects()->attach($gradeSubjects->all());
    }

    return redirect()->route('school.classes.index')
        ->with('success', 'Class created successfully.');
}
    public function edit(ClassRoom $class)
    {
        $this->own($class)->load('teachers');

        return view('school.classes.edit', ['class' => $class, 'teachers' => $this->teachers()]);
    }

    public function update(Request $request, ClassRoom $class)
    {
        $this->own($class);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'grade'            => ['required', Rule::in(Subject::GRADES)],
            'year'             => 'nullable|integer',
            'is_active'        => 'boolean',
            'class_teacher_id' => ['nullable', Rule::in($this->teachers()->pluck('id')->all())],
        ]);

        $class->update([
            'name'      => $validated['name'],
            'grade'     => $validated['grade'] ?? null,
            'year'      => $validated['year'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $class->teachers()->sync(
            ! empty($validated['class_teacher_id'])
                ? [$validated['class_teacher_id'] => ['is_class_teacher' => true]]
                : []
        );

        return redirect()->route('school.classes.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassRoom $class)
    {
        $this->own($class)->delete();

        return redirect()->route('school.classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}