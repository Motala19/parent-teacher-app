<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Attachment;
use App\Models\Message;
use App\Models\User;

class ApiFormat
{
    public static function user(User $u): array
    {
        return [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->realEmail(),
            'phone' => $u->phone,
            'role'  => $u->roleLabel(),
        ];
    }

    public static function contact(User $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'role' => $u->roleLabel()];
    }

    public static function attachment(Attachment $f): array
    {
        return [
            'id'       => $f->id,
            'name'     => $f->original_name,
            'size'     => $f->humanSize(),
            'kind'     => $f->kind(),
            'viewable' => $f->isViewable(),
        ];
    }

    // $full = the whole notice (for the open screen). Otherwise a short version for lists.
    public static function announcement(Announcement $a, bool $full = false): array
    {
        $row = [
            'id'                => $a->id,
            'type'              => $a->type,
            'type_label'        => $a->typeLabel(),
            'title'             => $a->title,
            'excerpt'           => $a->excerpt(),
            'audience'          => $a->audienceLabel(),
            'author'            => $a->user?->name,
            'published_at'      => ($a->published_at ?? $a->created_at)->toIso8601String(),
            'due_date'          => $a->due_date?->toDateString(),
            'due_label'         => $a->dueLabel(),
            'due_state'         => $a->dueState(),
            'done'              => (bool) ($a->done ?? false),
            'attachments_count' => (int) ($a->attachments_count ?? $a->attachments->count()),
        ];

        if ($full) {
            $row['body']        = (string) $a->body;
            $row['attachments'] = $a->attachments->map(fn ($f) => self::attachment($f))->values();
        }

        return $row;
    }

    public static function message(Message $m, int $meId): array
    {
        return [
            'id'          => $m->id,
            'mine'        => (int) $m->sender_id === $meId,
            'body'        => (string) $m->body,
            'about'       => $m->student ? $m->student->first_name . ' ' . $m->student->last_name : null,
            'seen'        => (bool) $m->is_read,
            'created_at'  => $m->created_at->toIso8601String(),
            'attachments' => $m->attachments->map(fn ($f) => self::attachment($f))->values(),
        ];
    }
}