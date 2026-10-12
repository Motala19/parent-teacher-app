<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectTeacherController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Subject $subject): Subject
    {
        abort_unless((int) $subject->school_id === $this->schoolId(), 404);
        return $subject;
    }

    // Classes in the grades that take this subject, plus any class that already has it
    private function classes(Subject $subject)
    {
        $assigned = DB::table('class_subject')->where('subject_id', $subject->id)->pluck('class_id');
        $grades   = $subject->gradeList();

        return ClassRoom::where('school_id', $this->schoolId())
            ->where(function ($q) use ($assigned, $grades) {
                $q->whereIn('id', $assigned)
                  ->orWhere(fn ($x) => $x->where('is_active', true)->whereIn('grade', $grades));
            })
            ->get()
            ->sortBy(fn ($c) => [Subject::gradeRank($c->grade), $c->name])
            ->values();
    }

    public function edit(Subject $subject)
    {
        $this->own($subject);

        $classes = $this->classes($subject);

        // class_id => teacher_id (a class is listed here if it takes the subject)
        $current = DB::table('class_subject')->where('subject_id', $subject->id)->pluck('teacher_id', 'class_id');

        $teachers = User::role('teacher')->where('school_id', $this->schoolId())->orderBy('name')->get();

        $counts = Student::where('school_id', $this->schoolId())
            ->where('is_active', true)
            ->whereIn('class_id', $classes->pluck('id'))
            ->selectRaw('class_id, COUNT(*) AS total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        return view('school.subjects.teachers', compact('subject', 'classes', 'current', 'teachers', 'counts'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->own($subject);

        $shown      = $this->classes($subject)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $teacherIds = User::role('teacher')->where('school_id', $this->schoolId())->pluck('id')->all();

        $data = $request->validate([
            'classes'    => 'nullable|array',
            'classes.*'  => [Rule::in($shown)],
            'teachers'   => 'nullable|array',
            'teachers.*' => ['nullable', Rule::in($teacherIds)],
        ]);

        $selected = array_map('intval', $data['classes'] ?? []);
        $before   = DB::table('class_subject')->where('subject_id', $subject->id)->pluck('class_id')->map(fn ($id) => (int) $id)->all();

        // Add the ticked classes and set their teacher
        $sync = [];
        foreach ($selected as $classId) {
            $sync[$classId] = ['teacher_id' => $data['teachers'][$classId] ?? null];
        }
        $subject->classes()->syncWithoutDetaching($sync);

        // Classes that were listed here and are now unticked lose the subject
        $removed = array_values(array_diff(array_intersect($shown, $before), $selected));
        if ($removed) {
            $subject->classes()->detach($removed);

            DB::table('student_subject')
                ->where('subject_id', $subject->id)
                ->whereIn('student_id', Student::whereIn('class_id', $removed)->select('id'))
                ->delete();
        }

        // Learners in newly added classes get the subject
        foreach (array_diff($selected, $before) as $classId) {
            Student::where('class_id', $classId)->get()
                ->each(fn ($s) => $s->subjects()->syncWithoutDetaching([$subject->id]));
        }

        return redirect()->route('school.subjects.index')
            ->with('success', $subject->name . ' teachers updated.');
    }
}