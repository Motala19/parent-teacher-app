<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewAnnouncement;
use App\Notifications\NewMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Notifier
{
    // One bell item per sender (it counts up) and at most one email per sender every 30 minutes
    public static function message(Message $message): void
    {
        $receiver = $message->receiver;
        $sender   = $message->sender;

        if (! $receiver || ! $sender) {
            return;
        }

        $channels = [];

        $existing = $receiver->unreadNotifications()
            ->where('type', NewMessage::class)
            ->get()
            ->first(fn ($n) => (int) ($n->data['sender_id'] ?? 0) === (int) $sender->id);

        if ($existing) {
            $data          = $existing->data;
            $data['count'] = (int) ($data['count'] ?? 1) + 1;
            $data['body']  = Str::limit($message->body ?: 'Sent an attachment', 90);

            $existing->data = $data;
            $existing->save();
        } else {
            $channels[] = 'database';
        }

        if ($receiver->wantsEmail() && Cache::add("msg-mail:{$receiver->id}:{$sender->id}", 1, now()->addMinutes(30))) {
            $channels[] = 'mail';
        }

        if ($channels) {
            $receiver->notify(new NewMessage($message, $channels));
        }
    }

    // Notifies everyone who should see the announcement. Returns how many people were notified.
    public static function announcement(Announcement $announcement): int
    {
        if (! $announcement->is_published) {
            return 0;
        }

        $announcement->loadMissing(['user', 'targets']);

        $recipients = static::recipients($announcement);

        foreach ($recipients as $user) {
            $channels = $user->wantsEmail() ? ['database', 'mail'] : ['database'];

            $user->notify(new NewAnnouncement($announcement, $channels));
        }

        return $recipients->count();
    }

    public static function recipients(Announcement $a): Collection
    {
        $schoolId = $a->school_id;
        $a->loadMissing('targets');

        switch ($a->audience) {
            case 'teachers':
                $people = User::role('teacher')->where('users.school_id', $schoolId)->get();
                break;

            case 'smt':
                $people = User::smtMembers()->where('users.school_id', $schoolId)->get();
                break;

            case 'classes':
                // Every learner the post is aimed at (a subject target = only learners taking it)
                $studentIds = collect();

                foreach ($a->targets as $t) {
                    $q = DB::table('students')->where('class_id', $t->class_id);

                    if ($t->subject_id) {
                        $q->whereIn('id', DB::table('student_subject')->where('subject_id', $t->subject_id)->select('student_id'));
                    }

                    $studentIds = $studentIds->merge($q->pluck('id'));
                }

                $people = User::role('parent')
                    ->where('users.school_id', $schoolId)
                    ->whereHas('children', fn ($q) => $q->whereIn('students.id', $studentIds->unique()->values()->all()))
                    ->get();

                // When the school admin posts to classes, those classes' teachers hear about it too
                if ($a->user && $a->user->hasRole('school-admin')) {
                    $teacherIds = collect();

                    foreach ($a->targets as $t) {
                        $teacherIds = $teacherIds->merge(DB::table('class_user')->where('class_id', $t->class_id)->pluck('user_id'));

                        $cs = DB::table('class_subject')->where('class_id', $t->class_id)->whereNotNull('teacher_id');
                        if ($t->subject_id) {
                            $cs->where('subject_id', $t->subject_id);
                        }
                        $teacherIds = $teacherIds->merge($cs->pluck('teacher_id'));
                    }

                    $people = $people->concat(
                        User::role('teacher')
                            ->where('users.school_id', $schoolId)
                            ->whereIn('users.id', $teacherIds->unique()->values()->all())
                            ->get()
                    );
                }
                break;

            default: // whole school
                $people = User::role(['parent', 'teacher'])->where('users.school_id', $schoolId)->get();
        }

        return $people
            ->unique('id')
            ->reject(fn ($u) => (int) $u->id === (int) $a->user_id)
            ->values();
    }
}