<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Message extends Model
{
    protected $fillable = [
        'school_id',
        'sender_id',
        'receiver_id',
        'class_id',
        'student_id',
        'body',
        'attachment',
        'is_read',
        'read_at',
        'hidden_by_sender',
        'hidden_by_receiver',
    ];

    protected $casts = [
        'is_read'            => 'boolean',
        'read_at'            => 'datetime',
        'hidden_by_sender'   => 'boolean',
        'hidden_by_receiver' => 'boolean',
    ];

    // All messages between two users, in either direction
    public function scopeBetween($query, int $a, int $b)
    {
        return $query->where(function ($outer) use ($a, $b) {
            $outer->where(fn ($q) => $q->where('sender_id', $a)->where('receiver_id', $b))
                  ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('receiver_id', $a));
        });
    }

    // Messages this user has not hidden from their own view
    public function scopeVisibleTo($query, int $userId)
    {
        return $query->where(function ($outer) use ($userId) {
            $outer->where(fn ($q) => $q->where('sender_id', $userId)->where('hidden_by_sender', false))
                  ->orWhere(fn ($q) => $q->where('receiver_id', $userId)->where('hidden_by_receiver', false));
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}