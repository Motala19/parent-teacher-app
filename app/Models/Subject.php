<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    public const GRADES = ['R', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

    protected $fillable = ['school_id', 'name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_subject', 'subject_id', 'class_id')
                    ->withPivot('teacher_id')->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subject', 'subject_id', 'student_id')
                    ->withTimestamps();
    }

    public function gradeList(): array
    {
        return DB::table('grade_subject')->where('subject_id', $this->id)->pluck('grade')
            ->sortBy(fn ($g) => array_search($g, self::GRADES))->values()->all();
    }

    public function syncGrades(array $grades): void
    {
        DB::table('grade_subject')->where('subject_id', $this->id)->delete();

        foreach (array_unique($grades) as $grade) {
            DB::table('grade_subject')->insert(['subject_id' => $this->id, 'grade' => $grade]);
        }
    }

    public static function forGrade(int $schoolId, ?string $grade)
    {
        if (! $grade) {
            return collect();
        }

        return static::where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('id', DB::table('grade_subject')->where('grade', trim($grade))->select('subject_id'))
            ->get();
    }
}