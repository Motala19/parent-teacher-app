@php
    $hour     = now('Africa/Johannesburg')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $first    = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $unread   = auth()->user()->unreadMessageCount();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $greeting }}, {{ $first }}</h2>
        <p>Here's what's happening with your classes and subjects.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                <a href="{{ route('teacher.announcements.create') }}" wire:navigate class="tile tile-rose">
                    <span class="tile-icon"><x-icon name="megaphone" /></span>
                    <div><div class="tile-title">Post announcement</div><div class="tile-sub">Homework, projects, reminders</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('teacher.announcements.create', ['type' => 'question_paper']) }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="file" /></span>
                    <div><div class="tile-title">Send question paper</div><div class="tile-sub">Attach a PDF or Word file</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('teacher.announcements.create', ['type' => 'assignment']) }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="paperclip" /></span>
                    <div><div class="tile-title">Post assignment</div><div class="tile-sub">Share the sheet with learners</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('messages.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="chat" /></span>
                    <div>
                        <div class="tile-title flex items-center gap-2">Messages @if($unread)<span class="badge-dot">{{ $unread }}</span>@endif</div>
                        <div class="tile-sub">Chat with parents</div>
                    </div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                <x-stat-card label="Class teacher of" :value="$classes->count()" icon="layers" />
                <x-stat-card label="Subjects" :value="$subjects->count()" icon="book" />
                <x-stat-card label="Learners I teach" :value="$learners" icon="users" />
                <x-stat-card label="Unread messages" :value="$unread" icon="chat" tone="rose" />
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="space-y-6">

                    @if($subjects->isNotEmpty())
                        <div class="card overflow-hidden">
                            <div class="panel-head">
                                <h3>My subjects</h3>
                                <a href="{{ route('teacher.subjects.index') }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">View all</a>
                            </div>
                            @foreach($subjects as $row)
                                <div class="list-row flex-wrap">
                                    <span class="stat-icon"><x-icon name="book" :size="20" /></span>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('teacher.subjects.show', ['subject' => $row->subject->id]) }}" wire:navigate
                                           class="font-semibold text-ink hover:text-brand-red transition">{{ $row->subject->name }}</a>
                                        <div class="text-xs text-ink-muted">
                                            {{ $row->classes->map(fn ($c) => $c->class->name . ' (' . $c->learners . ')')->join(', ') }}
                                        </div>
                                    </div>
                                    <a href="{{ route('teacher.subjects.show', ['subject' => $row->subject->id]) }}" wire:navigate class="btn btn-navy btn-sm">Learners</a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($classes->isNotEmpty())
                        <div class="card overflow-hidden">
                            <div class="panel-head"><h3>My classes</h3></div>
                            @foreach($classes as $class)
                                <div class="list-row">
                                    <span class="stat-icon"><x-icon name="layers" :size="20" /></span>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('teacher.classes.show', $class) }}" wire:navigate class="font-semibold text-ink hover:text-brand-red transition">{{ $class->name }}</a>
                                        <div class="text-xs text-ink-muted">{{ $class->students_count }} learners</div>
                                    </div>
                                    <a href="{{ route('teacher.announcements.create', ['class' => $class->id]) }}" wire:navigate class="btn btn-primary btn-sm">Announce</a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($subjects->isEmpty() && $classes->isEmpty())
                        <div class="card">
                            <x-empty title="Nothing assigned yet" text="Your school admin will link you to a class or a subject." icon="layers" />
                        </div>
                    @endif
                </div>

                <div class="card overflow-hidden self-start">
                    <div class="panel-head">
                        <h3>Notices for you</h3>
                        <a href="{{ route('teacher.notices.index') }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">View all</a>
                    </div>
                    @forelse($notices as $a)
                        <div class="list-row note tone-{{ $a->type }} items-start">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="badge">{{ $a->typeLabel() }}</span>
                                    <span class="text-xs text-ink-muted">{{ ($a->published_at ?? $a->created_at)->format('d M Y') }}</span>
                                </div>
                                <div class="font-semibold text-ink">{{ $a->title }}</div>
                                <div class="text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit($a->body, 120) }}</div>
                                <x-attachment-list :attachments="$a->attachments" class="mt-2" />
                            </div>
                        </div>
                    @empty
                        <x-empty title="No school notices" text="Whole-school announcements and newsletters show up here." icon="bell" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>