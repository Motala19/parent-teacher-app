<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConversationClosure;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Support\ApiFormat;
use App\Support\Attachments;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    private function authorise(User $me, User $contact, bool $sending): void
    {
        abort_unless(
            (int) $contact->school_id === (int) $me->school_id && (int) $contact->id !== (int) $me->id,
            404
        );

        $allowed = $me->canMessage($contact);

        if (! $allowed && ! $sending) {
            $allowed = Message::between($me->id, $contact->id)->exists();
        }

        abort_unless($allowed, 404);
    }

    // Teachers and the school office this parent may message
    public function contacts(Request $request)
    {
        $staff = $request->user()->messageableSchoolStaff()->with('roles')->orderBy('name')->get();

        return response()->json(['data' => $staff->map(fn ($u) => ApiFormat::contact($u))->values()]);
    }

    // One row per person I have chatted with
    public function index(Request $request)
    {
        $meId = (int) $request->user()->id;

        $rows = Message::visibleTo($meId)
            ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id, MAX(id) AS last_id', [$meId])
            ->groupBy('other_id')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $lasts  = Message::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $users  = User::with('roles')->whereIn('id', $rows->pluck('other_id'))->get()->keyBy('id');
        $unread = Message::where('receiver_id', $meId)
            ->where('is_read', false)
            ->where('hidden_by_receiver', false)
            ->selectRaw('sender_id, COUNT(*) AS total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        $data = $rows->map(function ($row) use ($lasts, $users, $unread, $meId) {
            $other = $users->get($row->other_id);
            $last  = $lasts->get($row->last_id);

            if (! $other || ! $last) {
                return null;
            }

            return [
                'sort'    => $last->id,
                'contact' => ApiFormat::contact($other),
                'last'    => [
                    'body'       => $last->body ?: 'Sent an attachment',
                    'mine'       => (int) $last->sender_id === $meId,
                    'created_at' => $last->created_at->toIso8601String(),
                ],
                'unread'  => (int) $unread->get($other->id, 0),
            ];
        })->filter()->sortByDesc('sort')->values()->map(function ($r) {
            unset($r['sort']);

            return $r;
        });

        return response()->json(['data' => $data]);
    }

    // The chat with one person. Opening it marks their messages as read.
    public function show(Request $request, User $contact)
    {
        $me = $request->user();
        $this->authorise($me, $contact, false);

        $messages = Message::between($me->id, $contact->id)
            ->visibleTo($me->id)
            ->with(['student', 'attachments'])
            ->orderByDesc('id')
            ->take(200)
            ->get()
            ->reverse()
            ->values();

        Message::where('sender_id', $contact->id)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $me->unreadNotifications()
            ->where('type', NewMessage::class)
            ->get()
            ->filter(fn ($n) => (int) ($n->data['sender_id'] ?? 0) === (int) $contact->id)
            ->each->markAsRead();

        return response()->json([
            'contact'  => ApiFormat::contact($contact),
            'can_send' => $me->canMessage($contact),
            'students' => $me->sharedStudentsWith($contact)
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->first_name . ' ' . $s->last_name])
                ->values(),
            'messages' => $messages->map(fn ($m) => ApiFormat::message($m, (int) $me->id))->values(),
        ]);
    }

    public function store(Request $request, User $contact)
    {
        $me = $request->user();
        $this->authorise($me, $contact, true);

        $students = $me->sharedStudentsWith($contact);

        $data = $request->validate([
            'body'       => 'nullable|string|max:2000|required_without:files',
            'student_id' => ['nullable', Rule::in($students->pluck('id')->all())],
        ] + Attachments::messageRules(), Attachments::messageMessages());

        $message = Message::create([
            'school_id'          => $me->school_id,
            'sender_id'          => $me->id,
            'receiver_id'        => $contact->id,
            'student_id'         => $data['student_id'] ?? null,
            'body'               => $data['body'] ?? '',
            'is_read'            => false,
            'hidden_by_sender'   => false,
            'hidden_by_receiver' => false,
        ]);

        Attachments::store($message, $request->file('files'), $me);

        ConversationClosure::where('user_id', $me->id)->where('contact_id', $contact->id)->delete();

        Notifier::message($message->load(['sender', 'receiver', 'student']));

        return response()->json(ApiFormat::message($message->load(['student', 'attachments']), (int) $me->id), 201);
    }

        // Hide one message from this parent's view. The other person still sees it.
    public function hideMessage(Request $request, int $id)
    {
        $me = (int) $request->user()->id;

        $message = Message::findOrFail($id);

        if ((int) $message->sender_id === $me) {
            $message->update(['hidden_by_sender' => true]);
        } elseif ((int) $message->receiver_id === $me) {
            $message->update(['hidden_by_receiver' => true]);
        } else {
            abort(404);
        }

        return response()->json(['ok' => true]);
    }

    // Delete the whole conversation for this parent only. Every message is kept in the database.
    // If the other person writes again, the chat returns with just the new messages.
    public function hideConversation(Request $request, User $contact)
    {
        $me = (int) $request->user()->id;

        abort_unless((int) $contact->school_id === (int) $request->user()->school_id, 404);

        Message::where('sender_id', $me)->where('receiver_id', $contact->id)->update(['hidden_by_sender' => true]);
        Message::where('sender_id', $contact->id)->where('receiver_id', $me)->update(['hidden_by_receiver' => true, 'is_read' => true]);

        return response()->json(['ok' => true]);
    }
}