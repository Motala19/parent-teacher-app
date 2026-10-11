<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'attachable_type',
        'attachable_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected static function booted(): void
    {
        // Deleting the record also deletes the stored file
        static::deleting(function (Attachment $attachment) {
            Storage::disk('local')->delete($attachment->path);
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function kind(): string
    {
        return match ($this->extension()) {
            'pdf'           => 'pdf',
            'doc', 'docx'   => 'doc',
            'xls', 'xlsx'   => 'xls',
            'ppt', 'pptx'   => 'ppt',
            'jpg', 'jpeg', 'png' => 'img',
            default         => 'file',
        };
    }

    public function extLabel(): string
    {
        return strtoupper(substr($this->extension() ?: 'file', 0, 4));
    }

    // Only these can be opened in the browser. Everything else is a download.
    public function isViewable(): bool
    {
        return in_array($this->mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';

        return $bytes . ' B';
    }

    public function canBeSeenBy(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ((int) $user->school_id !== (int) $this->school_id) {
            return false;
        }

        $owner = $this->attachable;

        if ($owner instanceof Announcement) {
            return $owner->isVisibleTo($user);
        }

        if ($owner instanceof Message) {
            return in_array((int) $user->id, [(int) $owner->sender_id, (int) $owner->receiver_id], true);
        }

        return false;
    }
}