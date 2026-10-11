<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class Attachments
{
    // Announcements: up to 5 files, 10 MB each
    public static function announcementRules(): array
    {
        return [
            'files'   => 'nullable|array|max:5',
            'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
        ];
    }

    // Messages: up to 3 files, 5 MB each
    public static function messageRules(): array
    {
        return [
            'files'   => 'nullable|array|max:3',
            'files.*' => 'file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
        ];
    }

    public static function announcementMessages(): array
    {
        return self::messagesFor(10, 'PDF, Word, Excel, PowerPoint and image (JPG, PNG) files');
    }

    public static function messageMessages(): array
    {
        return self::messagesFor(5, 'PDF, Word and image (JPG, PNG) files') + [
            'body.required_without' => 'Type a message or attach a file.',
        ];
    }

    private static function messagesFor(int $mb, string $allowed): array
    {
        return [
            'files.max'        => 'You can attach at most :max files at a time.',
            'files.*.max'      => "Each file must be {$mb} MB or smaller.",
            'files.*.mimes'    => "Only {$allowed} are allowed.",
            'files.*.file'     => 'One of the files could not be read. Please try again.',
            'files.*.uploaded' => "A file failed to upload. It may be larger than {$mb} MB.",
        ];
    }

    // Saves the uploaded files privately and links them to the announcement or message
    public static function store(Model $owner, ?array $files, User $user): int
    {
        $count = 0;

        foreach ($files ?? [] as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            // Read these before storing
            $name = $file->getClientOriginalName();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = $file->getSize();

            $path = $file->store('attachments/' . $user->school_id, 'local');

            $owner->attachments()->create([
                'school_id'     => $user->school_id,
                'user_id'       => $user->id,
                'path'          => $path,
                'original_name' => mb_substr($name, 0, 240),
                'mime'          => $mime,
                'size'          => $size,
            ]);

            $count++;
        }

        return $count;
    }

    // Removes some of an owner's files (only ones that belong to that owner)
    public static function remove(Model $owner, ?array $ids): void
    {
        $ids = array_map('intval', $ids ?? []);

        if (empty($ids)) {
            return;
        }

        $owner->attachments()->whereIn('id', $ids)->get()->each->delete();
    }
}