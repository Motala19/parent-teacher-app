<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ClassRoom extends Model
{
    protected $table = 'classes'; // Important because the table is named "classes"

    protected $fillable = [
        'school_id',
        'name',
        'grade',
        'year',
        'is_active',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_user', 'class_id', 'user_id')
                    ->withPivot('is_class_teacher')
                    ->withTimestamps();
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'class_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'class_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'class_id');
    }

    public function getRouteKeyName()
{
    return 'id';
}

public function subjects(): BelongsToMany
{
    return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
                ->withPivot('teacher_id')->withTimestamps();
}
}