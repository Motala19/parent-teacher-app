<?php

namespace App\Support;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherSubjects
{
    // One row per subject: the classes it is taught in, learners per class and the grades
    public static function summary(User $teacher): Collection
    {
        $assignments = $teacher->subjectAssignments()->with(['subject', 'classRoom'])->get();

        if ($assignments->isEmpty()) {
            return collect();
        }

        $counts = DB::table('students')
            ->join('student_subject', 'student_subject.student_id', '=', 'students.id')
            ->where('students.is_active', true)
            ->whereIn('students.class_id', $assignments->pluck('class_id')->unique()->all())
            ->whereIn('student_subject.subject_id', $assignments->pluck('subject_id')->unique()->all())
            ->selectRaw('students.class_id AS class_id, student_subject.subject_id AS subject_id, COUNT(*) AS total')
            ->groupBy('students.class_id', 'student_subject.subject_id')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->class_id . '-' . $r->subject_id => (int) $r->total]);

        return $assignments
            ->groupBy('subject_id')
            ->map(function ($rows) use ($counts) {
                $classes = $rows
                    ->sortBy(fn ($r) => [Subject::gradeRank($r->classRoom->grade), $r->classRoom->name])
                    ->map(fn ($r) => (object) [
                        'class'    => $r->classRoom,
                        'learners' => $counts->get($r->class_id . '-' . $r->subject_id, 0),
                    ])
                    ->values();

                return (object) [
                    'subject' => $rows->first()->subject,
                    'classes' => $classes,
                    'total'   => $classes->sum('learners'),
                    'grades'  => $classes->pluck('class.grade')->filter()->unique()->values(),
                ];
            })
            ->sortBy(fn ($row) => $row->subject->name)
            ->values();
    }
}