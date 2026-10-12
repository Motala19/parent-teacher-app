<?php

namespace App\Http\Controllers;


use App\Models\ConversationClosure;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Support\Attachments;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    // Viewing needs a valid contact or existing history. Sending needs a valid contact.
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

    private function groupOf(User $contact): string
    {
        return $contact->hasRole('parent') ? 'parents' : 'staff';
    }

    // One row per person I have chatted with (only messages I have not removed)
    private function conversations(User $me): Collection
    {
        $meId = (int) $me->id;

        $rows = Message::visibleTo($meId)
            ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id, MAX(id) AS last_id', [$meId])
            ->groupBy('other_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $lasts    = Message::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $users    = User::with('roles')->whereIn('id', $rows->pluck('other_id'))->get()->keyBy('id');
        $closures = ConversationClosure::where('user_id', $meId)->get()->keyBy('contact_id');
        $unread   = Message::where('receiver_id', $meId)
            ->where('is_read', false)
            ->where('hidden_by_receiver', false)
            ->selectRaw('sender_id, COUNT(*) AS total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        return $rows->map(function ($row) use ($lasts, $users, $closures, $unread) {
            $other = $users->get($row->other_id);
            $last  = $lasts->get($row->last_id);

            if (! $other || ! $last) {
                return null;
            }

            $closure = $closures->get($other->id);

            return (object) [
                'other'  => $other,
                'last'   => $last,
                'unread' => (int) $unread->get($other->id, 0),
                // Closed only until someone writes again
                'closed' => (bool) ($closure && $closure->closed_at && $closure->closed_at->greaterThanOrEqualTo($last->created_at)),
                'group'  => $other->hasRole('parent') ? 'parents' : 'staff',
            ];
        })->filter()->sortByDesc(fn ($c) => $c->last->id)->values();
    }

        private function contactsFor(User $me, string $tab)
    {
        if ($me->hasRole('parent')) {
            return $me->messageableSchoolStaff()->with('roles')->orderBy('name')->get();
        }

        if ($tab === 'staff') {
            return $me->messageableStaff()->with('roles')->orderBy('name')->get();
        }

        $query = $me->messageableParents()->orderBy('name');

        if ($me->hasRole('school-admin')) {
            return $query->with('children.classRoom')->get();
        }

        return $query->with([
            'children' => fn ($q) => $q
                ->whereIn('students.id', $me->myStudents()->select('students.id'))
                ->with('classRoom'),
        ])->get();
    }

    private function resolveTab(User $me, ?string $requested, Collection $all): string
    {
        if ($me->hasRole('parent')) {
            return 'staff';
        }

        if (in_array($requested, ['parents', 'staff'], true)) {
            return $requested;
        }

        $open          = $all->where('closed', false);
        $parentsUnread = (int) $open->where('group', 'parents')->sum('unread');
        $staffUnread   = (int) $open->where('group', 'staff')->sum('unread');

        return ($staffUnread > 0 && $parentsUnread === 0) ? 'staff' : 'parents';
    }

    private function sidebar(User $me, Collection $all, string $tab, string $filter): array
    {
        $open = $all->where('closed', false);

        return [
            'tab'           => $tab,
            'filter'        => $filter,
            'conversations' => $all->where('group', $tab)->where('closed', $filter === 'closed')->values(),
            'counts'        => [
                'parents' => (int) $open->where('group', 'parents')->sum('unread'),
                'staff'   => (int) $open->where('group', 'staff')->sum('unread'),
                'closed'  => $all->where('group', $tab)->where('closed', true)->count(),
            ],
            'contacts'      => $this->contactsFor($me, $tab),
        ];
    }

    // Loads the visible messages and marks the other person's messages as read
    private function loadMessages(User $me, User $contact): Collection
    {
        $messages = Message::between($me->id, $contact->id)
            ->visibleTo($me->id)
            ->with(['student', 'attachments'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        Message::where('sender_id', $contact->id)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $messages->where('sender_id', $contact->id)->each(fn ($m) => $m->is_read = true);
                $me->unreadNotifications()
            ->where('type', NewMessage::class)
            ->get()
            ->filter(fn ($n) => (int) ($n->data['sender_id'] ?? 0) === (int) $contact->id)
            ->each->markAsRead();

        return $messages;
    }

    private function signature(Collection $messages): string
    {
        return $messages->count() . '-' . $messages->max('id') . '-' . $messages->where('is_read', true)->count();
    }

    private function payload(User $me, User $contact): array
    {
        $messages = $this->loadMessages($me, $contact);

        return [
            'sig'  => $this->signature($messages),
            'html' => view('messages._thread', ['messages' => $messages])->render(),
        ];
    }

    public function index(Request $request)
    {
        $me  = auth()->user();
        $all = $this->conversations($me);

        $tab    = $this->resolveTab($me, $request->query('tab'), $all);
        $filter = $request->query('show') === 'closed' ? 'closed' : 'open';

        return view('messages.index', $this->sidebar($me, $all, $tab, $filter) + ['contact' => null]);
    }

    public function show(Request $request, User $contact)
    {
        $me = auth()->user();
        $this->authorise($me, $contact, false);

        $messages = $this->loadMessages($me, $contact);

        $all    = $this->conversations($me);
        $convo  = $all->first(fn ($c) => (int) $c->other->id === (int) $contact->id);
        $closed = (bool) ($convo->closed ?? false);

        $tab    = $me->hasRole('parent') ? 'staff' : $this->groupOf($contact);
        $filter = ($closed || $request->query('show') === 'closed') ? 'closed' : 'open';

        return view('messages.index', $this->sidebar($me, $all, $tab, $filter) + [
            'contact'  => $contact,
            'messages' => $messages,
            'sig'      => $this->signature($messages),
            'canSend'  => $me->canMessage($contact),
            'students' => $me->sharedStudentsWith($contact),
            'closed'   => $closed,
        ]);
    }

    // Polled by the open chat to pick up new messages and "Seen" updates
    public function thread(User $contact)
    {
        $me = auth()->user();
        $this->authorise($me, $contact, false);

        return response()->json($this->payload($me, $contact));
    }

    public function store(Request $request, User $contact)
    {
        $me = auth()->user();
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
        Notifier::message($message->load(['sender', 'receiver', 'student']));

        // Writing in a closed chat reopens it
        ConversationClosure::where('user_id', $me->id)->where('contact_id', $contact->id)->delete();

        if ($request->expectsJson()) {
            return response()->json($this->payload($me, $contact));
        }

        return redirect()->route('messages.show', $contact);
    }

    // Close = hide from my Open list. Messages are kept. A new message reopens it.
    public function close(User $contact)
    {
        $me = auth()->user();
        $this->authorise($me, $contact, false);

        ConversationClosure::updateOrCreate(
            ['user_id' => $me->id, 'contact_id' => $contact->id],
            ['closed_at' => now()]
        );

        return redirect()
            ->route('messages.index', ['tab' => $me->hasRole('parent') ? 'staff' : $this->groupOf($contact)])
            ->with('success', 'Chat with ' . $contact->name . ' closed. Find it under Closed.');
    }

    public function reopen(User $contact)
    {
        $me = auth()->user();
        $this->authorise($me, $contact, false);

        ConversationClosure::where('user_id', $me->id)->where('contact_id', $contact->id)->delete();

        return redirect()->route('messages.show', $contact);
    }

    // Removing a single message only hides it from this person. It is kept on record.
    public function destroy(Message $message)
    {
        $meId = (int) auth()->id();

        if ((int) $message->sender_id === $meId) {
            $message->update(['hidden_by_sender' => true]);
        } elseif ((int) $message->receiver_id === $meId) {
            $message->update(['hidden_by_receiver' => true]);
        } else {
            abort(404);
        }

        return back();
    }
}