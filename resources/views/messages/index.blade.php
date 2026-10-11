@php
    $me        = auth()->user();
    $isParent  = $me->hasRole('parent');
    $who       = $isParent ? 'teachers and the school office' : ($tab === 'parents' ? 'parents' : 'staff');
    $threadUrl = $contact ? route('messages.thread', $contact) : null;
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="messenger card overflow-hidden">

                {{-- ================= CHAT LIST ================= --}}
                <aside class="msg-side {{ $contact ? 'hidden lg:flex' : 'flex' }}" x-data="{ picker: false, q: '', pq: '' }">

                    <div class="msg-side-head">
                        <h2 class="text-xl font-extrabold text-ink">{{ $isParent ? 'Messages' : 'Chats' }}</h2>
                        <button type="button" class="btn btn-primary btn-sm" x-on:click="picker = ! picker">
                            <x-icon name="plus" :size="14" /> New
                        </button>
                    </div>

                    @if(session('success'))
                        <div class="mx-4 mb-2 rounded-xl bg-green-50 px-3 py-2 text-xs font-semibold text-green-800">{{ session('success') }}</div>
                    @endif

                    @unless($isParent)
                        <div class="msg-tabs">
                            <a href="{{ route('messages.index', ['tab' => 'parents']) }}" wire:navigate class="msg-tab {{ $tab === 'parents' ? 'is-active' : '' }}">
                                <x-icon name="users" :size="15" /> Parents
                                @if($counts['parents'])<span class="badge-dot">{{ $counts['parents'] }}</span>@endif
                            </a>
                            <a href="{{ route('messages.index', ['tab' => 'staff']) }}" wire:navigate class="msg-tab {{ $tab === 'staff' ? 'is-active' : '' }}">
                                <x-icon name="book" :size="15" /> Staff
                                @if($counts['staff'])<span class="badge-dot">{{ $counts['staff'] }}</span>@endif
                            </a>
                        </div>
                    @endunless

                    <div class="msg-filter">
                        <a href="{{ route('messages.index', ['tab' => $tab]) }}" wire:navigate class="{{ $filter === 'open' ? 'is-active' : '' }}">Open</a>
                        <a href="{{ route('messages.index', ['tab' => $tab, 'show' => 'closed']) }}" wire:navigate class="{{ $filter === 'closed' ? 'is-active' : '' }}">
                            Closed @if($counts['closed'])({{ $counts['closed'] }})@endif
                        </a>
                    </div>

                    <div class="msg-search">
                        <input type="search" x-model="q" placeholder="Search chats"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    {{-- New chat picker --}}
                    <div class="msg-picker" x-show="picker" x-transition style="display:none">
                        <div class="p-3 pb-2">
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Start a chat with {{ $who }}</div>
                            <input type="search" x-model="pq" placeholder="Search by name"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>

                        @forelse($contacts as $p)
                            <a href="{{ route('messages.show', $p) }}" wire:navigate class="msg-row"
                               data-name="{{ strtolower($p->name) }}" x-show="$el.dataset.name.includes(pq.toLowerCase())">
                                <x-avatar :name="$p->name" />
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-ink">{{ $p->name }}</div>
                                    <div class="truncate text-xs text-ink-muted">
                                        @if($p->relationLoaded('children') && $p->children->isNotEmpty())
                                            Parent of {{ $p->children->map(fn ($s) => $s->first_name . ' (' . ($s->classRoom->name ?? 'no class') . ')')->join(', ') }}
                                        @else
                                            {{ $p->roleLabel() }}
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="px-4 py-3 text-sm text-ink-muted">
                                @if($isParent)
                                    You can message your child's teachers once your child has been placed in a class.
                                @elseif($tab === 'parents')
                                    No parents yet. Add learners to your class and link their parents first.
                                @else
                                    No other staff at your school yet.
                                @endif
                            </p>
                        @endforelse
                    </div>

                    {{-- Chats --}}
                    <div class="msg-list scroll-soft">
                        @forelse($conversations as $c)
                            <a href="{{ route('messages.show', $c->other) }}" wire:navigate
                               class="msg-row {{ $contact && (int) $contact->id === (int) $c->other->id ? 'is-open' : '' }}"
                               data-name="{{ strtolower($c->other->name) }}" x-show="$el.dataset.name.includes(q.toLowerCase())">
                                <x-avatar :name="$c->other->name" size="h-11 w-11 text-sm" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-ink {{ $c->unread ? 'font-extrabold' : 'font-semibold' }}">{{ $c->other->name }}</span>
                                        <span class="shrink-0 text-[11px] text-ink-muted">{{ $c->last->created_at->diffForHumans(null, true, true) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm {{ $c->unread ? 'font-semibold text-ink' : 'text-ink-muted' }}">
                                            {{ ((int) $c->last->sender_id === (int) $me->id ? 'You: ' : '') . \Illuminate\Support\Str::limit($c->last->body ?: 'Sent an attachment', 60) }}
                                        </span>
                                        @if($c->unread)<span class="badge-dot">{{ $c->unread }}</span>@endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <x-empty :title="$filter === 'closed' ? 'No closed chats' : 'No chats yet'"
                                     :text="$filter === 'closed' ? 'Chats you close are kept here.' : 'Click New to start a chat.'"
                                     icon="chat" />
                        @endforelse
                    </div>
                </aside>

                {{-- ================= OPEN CHAT ================= --}}
                @if($contact)
                    <section class="msg-main flex" x-data="{
                            sig: @js($sig),
                            closed: @js($closed),
                            sending: false,
                            error: '',
                            files: [],
                            timer: null,
                            init() {
                                this.toBottom();
                                this.timer = setInterval(() => this.poll(), 8000);
                            },
                            destroy() { clearInterval(this.timer); },
                            toBottom() {
                                this.$nextTick(() => { const t = this.$refs.thread; if (t) t.scrollTop = t.scrollHeight; });
                            },
                            nearBottom() {
                                const t = this.$refs.thread;
                                return ! t || (t.scrollHeight - t.scrollTop - t.clientHeight) < 90;
                            },
                            apply(data, force) {
                                if (! this.$refs.thread || (data.sig === this.sig && ! force)) return;
                                const stick = force || this.nearBottom();
                                this.$refs.thread.innerHTML = data.html;
                                this.sig = data.sig;
                                if (stick) this.toBottom();
                            },
                            async poll() {
                                if (! this.$el.isConnected) { clearInterval(this.timer); return; }
                                if (document.hidden || this.sending) return;
                                try {
                                    const res = await fetch(@js($threadUrl), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                                    if (res.ok) this.apply(await res.json(), false);
                                } catch (e) {}
                            },
                            async send(form) {
                                if (this.sending) return;
                                const box = form.querySelector('textarea[name=body]');
                                if (! box.value.trim() && ! this.files.length) return;
                                this.sending = true;
                                this.error = '';
                                try {
                                    const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                                    const data = await res.json();
                                    if (res.ok) {
                                        box.value = '';
                                        box.style.height = 'auto';
                                        if (this.$refs.picker) this.$refs.picker.value = '';
                                        this.files = [];
                                        this.closed = false;
                                        this.apply(data, true);
                                    } else {
                                        this.error = data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not send. Please try again.');
                                    }
                                } catch (e) {
                                    this.error = 'Could not send. Please try again.';
                                }
                                this.sending = false;
                            }
                        }">

                        {{-- Header --}}
                        <div class="chat-head">
                            <div class="lg:hidden">
                                <a href="{{ route('messages.index', ['tab' => $tab]) }}" wire:navigate class="icon-btn" title="Back to chats">
                                    <x-icon name="arrow" :size="18" class="rotate-180" />
                                </a>
                            </div>

                            <x-avatar :name="$contact->name" size="h-11 w-11 text-sm" />

                            <div class="min-w-0">
                                <div class="truncate font-bold leading-tight text-ink">{{ $contact->name }}</div>
                                <div class="truncate text-xs text-ink-muted">
                                    {{ $contact->roleLabel() }}
                                    @if($contact->hasRole('parent') && $students->isNotEmpty())
                                        · Parent of {{ $students->map(fn ($s) => $s->first_name . ' ' . $s->last_name . ($s->classRoom ? ' (' . $s->classRoom->name . ')' : ''))->join(', ') }}
                                    @endif
                                </div>
                            </div>

                            <div class="ms-auto flex items-center gap-2">
                                @unless($isParent)
                                    @if($contact->phone)
                                        <a href="tel:{{ $contact->phone }}" class="icon-btn" title="Call {{ $contact->phone }}"><x-icon name="phone" :size="17" /></a>
                                    @endif
                                    @if($contact->realEmail())
                                        <a href="mailto:{{ $contact->realEmail() }}" class="icon-btn" title="Email {{ $contact->realEmail() }}"><x-icon name="mail" :size="17" /></a>
                                    @endif
                                @endunless

                                <form action="{{ route('messages.close', $contact) }}" method="POST" x-show="! closed">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Hide this chat from your Open list. Nothing is deleted.">
                                        <x-icon name="x" :size="14" /> Close
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="chat-scroll scroll-soft" x-ref="thread">
                            @include('messages._thread', ['messages' => $messages])
                        </div>

                        {{-- Closed notice --}}
                        <div x-show="closed" style="display:none"
                             class="flex items-center justify-between gap-3 border-t border-yellow-200 bg-yellow-50 px-4 py-2 text-sm text-yellow-900">
                            <span>This chat is closed and hidden from your Open list. Send a message to reopen it.</span>
                            <form action="{{ route('messages.reopen', $contact) }}" method="POST">
                                @csrf
                                <button type="submit" class="font-bold underline">Reopen</button>
                            </form>
                        </div>

                        {{-- Composer --}}
                        @if($canSend)
                            <form action="{{ route('messages.store', $contact) }}" method="POST" enctype="multipart/form-data"
                                  class="chat-foot space-y-2" x-on:submit.prevent="send($el)">
                                @csrf

                                @if($students->isNotEmpty())
                                    <select name="student_id" class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm text-sm">
                                        <option value="">General (not about a specific learner)</option>
                                        @foreach($students as $s)
                                            <option value="{{ $s->id }}">About {{ $s->first_name }} {{ $s->last_name }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                <div class="flex items-end gap-2">
                                    <label class="icon-btn cursor-pointer shrink-0" title="Attach files (PDF, Word or images. Up to 3 files, 5 MB each)">
                                        <x-icon name="paperclip" :size="18" />
                                        <input type="file" name="files[]" multiple class="sr-only" x-ref="picker"
                                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                               x-on:change="files = Array.from($event.target.files)">
                                    </label>

                                    <textarea name="body" rows="1" placeholder="Type a message..."
                                              x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send($el.form) }"
                                              x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 140) + 'px'"
                                              class="flex-1 resize-none rounded-md border-gray-300 shadow-sm"></textarea>

                                    <button type="submit" class="btn btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-wait': sending }">
                                        <x-icon name="send" :size="16" /> <span class="hidden sm:inline" x-text="sending ? 'Sending...' : 'Send'"></span>
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2" x-show="files.length" style="display:none">
                                    <template x-for="f in files" :key="f.name + f.size">
                                        <span class="chip" x-text="f.name"></span>
                                    </template>
                                </div>

                                <p class="text-sm text-red-600" x-show="error" x-text="error" style="display:none"></p>
                            </form>
                        @else
                            <div class="chat-foot">
                                <div class="rounded-xl bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
                                    You can no longer send messages to {{ $contact->name }} because a learner's class has changed. You can still read the history.
                                </div>
                            </div>
                        @endif
                    </section>
                @else
                    <section class="msg-main hidden lg:flex items-center justify-center">
                        <x-empty title="Select a chat" text="Pick a conversation on the left, or click New to start one." icon="chat" />
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>