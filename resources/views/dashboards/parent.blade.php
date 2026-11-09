@php
    $hour     = now('Africa/Johannesburg')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $first    = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $unread   = auth()->user()->unreadMessageCount();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $greeting }}, {{ $first }}</h2>
        <p>Stay up to date with your children's school.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <x-install-app />

            <div class="grid sm:grid-cols-3 gap-4 stagger">
                <a href="{{ route('parent.announcements.index') }}" wire:navigate class="tile tile-rose">
                    <span class="tile-icon"><x-icon name="megaphone" /></span>
                    <div><div class="tile-title">Announcements</div><div class="tile-sub">Homework and class notices</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('parent.announcements.index', ['type' => 'newsletter']) }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="file" /></span>
                    <div><div class="tile-title">Newsletters</div><div class="tile-sub">Read or download</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('messages.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="chat" /></span>
                    <div>
                        <div class="tile-title flex items-center gap-2">Messages @if($unread)<span class="badge-dot">{{ $unread }}</span>@endif</div>
                        <div class="tile-sub">Chat privately with teachers</div>
                    </div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
            </div>

            {{-- Due soon --}}
            @if($pending->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="panel-head">
                        <h3>Due soon</h3>
                        <a href="{{ route('parent.announcements.index', ['due' => 1]) }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">See all ({{ $pendingTotal }})</a>
                    </div>

                    @foreach($pending as $p)
                        <div class="list-row">
                            <form action="{{ route('parent.announcements.done', $p) }}" method="POST">
                                @csrf
                                <button type="submit" class="check" title="Mark as done"></button>
                            </form>

                            <a href="{{ route('parent.announcements.show', $p) }}" wire:navigate class="min-w-0 flex-1">
                                <div class="font-semibold text-ink clamp-2">{{ $p->title }}</div>
                               <div class="text-xs text-ink-muted">
            @if($names = $p->learnerNames($children))<span class="for-chip"><x-icon name="user" :size="11" /> For {{ implode(', ', $names) }}</span>@endif
            {{ $p->typeLabel() }} · {{ $p->audienceLabel() }}
                        </div>
                            </a>

                            <span class="due due-{{ $p->dueState() }} shrink-0"><x-icon name="calendar" :size="12" /> {{ $p->dueLabel() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div>
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">My children</h3>
                <div class="grid md:grid-cols-2 gap-5 stagger">
                    @forelse($children as $child)
                        @php($classTeacher = $child->classRoom?->teachers->where('pivot.is_class_teacher', true)->first())

                        <div class="card card-hover p-6">
                            <div class="flex items-center gap-4">
                                <x-avatar :name="$child->first_name . ' ' . $child->last_name" size="h-14 w-14 text-lg" />
                                <div class="min-w-0">
                                    <div class="text-lg font-bold text-ink">{{ $child->first_name }} {{ $child->last_name }}</div>
                                    <div class="text-sm text-ink-muted">
                                        {{ $child->classRoom->name ?? 'Not in a class yet' }}
                                        @if($child->grade) · Grade {{ $child->grade }} @endif
                                    </div>
                                </div>
                            </div>

                            @if($classTeacher)
                                <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-navy-50 px-4 py-3">
                                    <div class="text-sm">
                                        <div class="text-xs font-bold uppercase tracking-wider text-ink-muted">Class teacher</div>
                                        <div class="font-semibold text-ink">{{ $classTeacher->name }}</div>
                                    </div>
                                    @if($messageableIds->contains((int) $classTeacher->id))
                                        <a href="{{ route('messages.show', $classTeacher) }}" wire:navigate class="btn btn-primary btn-sm">
                                            <x-icon name="chat" :size="14" /> Message
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if($child->subjects->isNotEmpty())
                                <div class="mt-4">
                                    <div class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Subjects and teachers</div>
                                    <div class="divide-y divide-navy-100">
                                        @foreach($child->subjects->sortBy('name') as $sub)
                                            @php($teacher = $subjectTeachers->get($child->class_id . '-' . $sub->id)?->teacher)
                                            <div class="flex items-center justify-between gap-3 py-2 text-sm">
                                                <span class="font-semibold text-ink">{{ $sub->name }}</span>
                                                <span class="flex items-center gap-2">
                                                    <span class="text-ink-muted">{{ $teacher?->name ?? 'No teacher yet' }}</span>
                                                    @if($teacher && $messageableIds->contains((int) $teacher->id))
                                                        <a href="{{ route('messages.show', $teacher) }}" wire:navigate class="icon-btn" title="Message {{ $teacher->name }}">
                                                            <x-icon name="chat" :size="16" />
                                                        </a>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="card md:col-span-2">
                            <x-empty title="No children linked yet" text="Please contact your school so they can link your children to your account." icon="users" />
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- School documents --}}
            <div>
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">School documents</h3>
                <div class="grid sm:grid-cols-3 gap-4 stagger">
                    <a href="{{ route('parent.announcements.index', ['type' => 'timetable']) }}" wire:navigate class="tile">
                        <span class="tile-icon"><x-icon name="calendar" /></span>
                        <div><div class="tile-title">Timetables</div><div class="tile-sub">Tests, exams and classes</div></div>
                        <x-icon name="arrow" :size="18" class="arrow" />
                    </a>
                    <a href="{{ route('parent.announcements.index', ['type' => 'stationery']) }}" wire:navigate class="tile">
                        <span class="tile-icon"><x-icon name="file" /></span>
                        <div><div class="tile-title">Stationery lists</div><div class="tile-sub">What to buy</div></div>
                        <x-icon name="arrow" :size="18" class="arrow" />
                    </a>
                    <a href="{{ route('parent.announcements.index', ['type' => 'year_plan']) }}" wire:navigate class="tile">
                        <span class="tile-icon"><x-icon name="layers" /></span>
                        <div><div class="tile-title">Year plans</div><div class="tile-sub">Terms and key dates</div></div>
                        <x-icon name="arrow" :size="18" class="arrow" />
                    </a>
                </div>
            </div>

            {{-- Latest three --}}
            @if($announcements->isNotEmpty())
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Latest notices</h3>
                        <a href="{{ route('parent.announcements.index') }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">View all</a>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4 stagger">
                        @foreach($announcements as $a)
                            <a href="{{ route('parent.announcements.show', $a) }}" wire:navigate class="card note card-hover tone-{{ $a->type }} p-5 block">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="badge">{{ $a->typeLabel() }}</span>
                                   @if($a->dueLabel())<span class="due due-{{ $a->dueState() }}">{{ $a->dueLabel() }}</span>@endif
@if($names = $a->learnerNames($children))<span class="for-chip"><x-icon name="user" :size="11" /> For {{ implode(', ', $names) }}</span>@endif
                                </div>
                                <div class="font-bold text-ink clamp-2">{{ $a->title }}</div>
                                <p class="mt-1 text-sm text-ink-muted clamp-2">{{ $a->excerpt(120) }}</p>
                                <div class="mt-3 flex items-center gap-2 text-xs text-ink-muted">
                                    {{ ($a->published_at ?? $a->created_at)->diffForHumans(null, true, true) }}
                                    @if($a->attachments->isNotEmpty())· <x-icon name="paperclip" :size="12" /> {{ $a->attachments->count() }}@endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>