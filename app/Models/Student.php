<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'class_id',
        'grade',
        'first_name',
        'last_name',
        'admission_number',
        'date_of_birth',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active'     => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'user_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject', 'student_id', 'subject_id')
                    ->withTimestamps();
    }

    // Gives the learner the subjects of their class (or of their grade if the class has none).
    // A learner with no class ends up with no subjects.
    public function applyDefaultSubjects(): void
    {
        $ids = collect();

        if ($this->class_id) {
            $ids = DB::table('class_subject')->where('class_id', $this->class_id)->pluck('subject_id');

            if ($ids->isEmpty() && $this->classRoom?->grade) {
                $ids = Subject::forGrade($this->school_id, $this->classRoom->grade)->pluck('id');
            }
        }

        $this->subjects()->sync($ids->all());
    }
}