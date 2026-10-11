<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

class Announcement extends Model
{
    public const TYPES = [
        'general'        => 'General notice',
        'urgent'         => 'Urgent',
        'event'          => 'Event',
        'newsletter'     => 'Newsletter',
        'reminder'       => 'Reminder',
        'homework'       => 'Homework',
        'assignment'     => 'Assignment',
        'project'        => 'Project',
        'test'           => 'Test',
        'question_paper' => 'Question paper',
    ];

    protected $fillable = [
        'school_id',
        'user_id',
        'class_id',
        'title',
        'body',
        'type',
        'attachment',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Deleting an announcement removes its files too
        static::deleting(function (Announcement $announcement) {
            $announcement->attachments()->get()->each->delete();
        });
    }

    // Teachers cannot send newsletters, urgent notices or events (admin only)
    public static function teacherTypes(): array
    {
        return Arr::only(self::TYPES, [
            'homework', 'assignment', 'project', 'test', 'question_paper', 'reminder', 'general',
        ]);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function badgeClass(): string
    {
        return match ($this->type) {
            'urgent'                            => 'bg-red-100 text-red-800',
            'homework', 'assignment', 'project' => 'bg-blue-100 text-blue-800',
            'test', 'question_paper', 'reminder' => 'bg-yellow-100 text-yellow-800',
            'newsletter'                        => 'bg-purple-100 text-purple-800',
            default                             => 'bg-gray-100 text-gray-800',
        };
    }

    // Used to decide who may open this announcement's files
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ((int) $user->school_id !== (int) $this->school_id) {
            return false;
        }

        if ($user->hasRole('school-admin') || (int) $user->id === (int) $this->user_id) {
            return true;
        }

        if (! $this->is_published) {
            return false;
        }

        // Whole-school posts (newsletters and so on) are visible to everyone at the school
        if (is_null($this->class_id)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $user->myClasses()->whereKey($this->class_id)->exists();
        }

        if ($user->hasRole('parent')) {
            return $user->children()->where('students.class_id', $this->class_id)->exists();
        }

        return false;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}