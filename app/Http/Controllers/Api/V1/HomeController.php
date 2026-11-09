<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSubject;
use App\Models\User;
use App\Support\ApiFormat;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Loaded once: used for the child cards and to say which learner each notice is for
        $children = $user->children()->with(['classRoom.teachers', 'subjects'])->get();
        $classIds = $children->pluck('class_id')->filter()->unique();

        $subjectTeachers = ClassSubject::with('teacher')
            ->whereIn('class_id', $classIds)
            ->whereNotNull('teacher_id')
            ->get()
            ->keyBy(fn ($r) => $r->class_id . '-' . $r->subject_id);

        $messageable = $user->messageableSchoolStaff()->pluck('users.id')->map(fn ($id) => (int) $id);

        $person = fn (?User $t) => $t ? [
            'id'          => $t->id,
            'name'        => $t->name,
            'can_message' => $messageable->contains((int) $t->id),
        ] : null;

        $doneFlag = ['completions as done' => fn ($q) => $q->where('user_id', $user->id)];

        // The three latest notices (documents live in the School tab)
        $latest = Announcement::with(['user', 'targets'])
            ->withCount('attachments')
            ->withExists($doneFlag)
            ->forParent($user)
            ->notDismissedBy($user)
            ->notDocuments()
            ->pinnedFirst()
            ->take(3)
            ->get();

        // Homework, assignments, projects and tests still to do
        $pendingBase  = Announcement::forParent($user)->notDismissedBy($user)->dueSoon()->notDoneBy($user);
        $pendingTotal = (clone $pendingBase)->count();
        $pending      = (clone $pendingBase)
            ->with(['user', 'targets'])
            ->withCount('attachments')
            ->orderBy('announcements.due_date')
            ->orderBy('announcements.id')
            ->take(4)
            ->get();

        return response()->json([
            'user'   => ApiFormat::user($user),
            'unread' => [
                'messages'      => $user->unreadMessageCount(),
                'notifications' => $user->unreadNotifications()->count(),
            ],
            'children' => $children->map(function ($c) use ($person, $subjectTeachers) {
                $classTeacher = $c->classRoom?->teachers->where('pivot.is_class_teacher', true)->first();

                return [
                    'id'            => $c->id,
                    'name'          => $c->first_name . ' ' . $c->last_name,
                    'class'         => $c->classRoom?->name,
                    'grade'         => $c->grade ?? $c->classRoom?->grade,
                    'class_teacher' => $person($classTeacher),
                    'subjects'      => $c->subjects->sortBy('name')->map(fn ($s) => [
                        'id'      => $s->id,
                        'name'    => $s->name,
                        'teacher' => $person($subjectTeachers->get($c->class_id . '-' . $s->id)?->teacher),
                    ])->values(),
                ];
            })->values(),
            'announcements' => $latest->map(fn ($a) => ApiFormat::announcement($a, false, $children))->values(),
            'pending'       => $pending->map(fn ($a) => ApiFormat::announcement($a, false, $children))->values(),
            'pending_total' => $pendingTotal,
        ]);
    }
}