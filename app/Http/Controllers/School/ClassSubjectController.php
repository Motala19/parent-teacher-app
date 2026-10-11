<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
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

    public function edit(ClassRoom $class)
    {
        $this->own($class)->load('subjects');
        $sid = $this->schoolId();

        $subjects = Subject::where('school_id', $sid)->where('is_active', true)->orderBy('name')->get();
        $teachers = User::role('teacher')->where('school_id', $sid)->orderBy('name')->get();

        $assigned = $class->subjects->mapWithKeys(fn ($s) => [$s->id => $s->pivot->teacher_id]);
        $selected = $class->subjects->pluck('id')->all();

        // A class with no subjects yet is pre-ticked with its grade's subjects
        $usingGradeDefaults = false;
        if (empty($selected)) {
            $selected = Subject::forGrade($sid, $class->grade)->pluck('id')->all();
            $usingGradeDefaults = ! empty($selected);
        }

        return view('school.classes.subjects', compact('class', 'subjects', 'teachers', 'assigned', 'selected', 'usingGradeDefaults'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $this->own($class);
        $sid = $this->schoolId();

        $allowed    = Subject::where('school_id', $sid)->pluck('id')->all();
        $teacherIds = User::role('teacher')->where('school_id', $sid)->pluck('id')->all();

        $data = $request->validate([
            'subjects'   => 'nullable|array',
            'subjects.*' => [Rule::in($allowed)],
            'teachers'   => 'nullable|array',
            'teachers.*' => ['nullable', Rule::in($teacherIds)],
        ]);

        $selected = array_map('intval', $data['subjects'] ?? []);
        $before   = $class->subjects()->pluck('subjects.id')->all();

        $sync = [];
        foreach ($selected as $id) {
            $sync[$id] = ['teacher_id' => $data['teachers'][$id] ?? null];
        }
        $class->subjects()->sync($sync);

        // Keep the learners in this class in step with the class
        $added   = array_diff($selected, $before);
        $removed = array_diff($before, $selected);

        if ($added || $removed) {
            foreach ($class->students as $student) {
                if ($added)   $student->subjects()->syncWithoutDetaching($added);
                if ($removed) $student->subjects()->detach($removed);
            }
        }

        return redirect()->route('school.classes.index')
            ->with('success', 'Class subjects updated.');
    }
}