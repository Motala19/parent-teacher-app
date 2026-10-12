@php
    $label = fn ($g) => $g === 'R' ? 'Grade R' : 'Grade ' . $g;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $subject->name }}</h2>
        <p>
            {{ $total }} {{ \Illuminate\Support\Str::plural('learner', $total) }} taking {{ $subject->name }}
            in {{ $groups->count() }} {{ \Illuminate\Support\Str::plural('class', $groups->count()) }}{{ $grade ? ' · ' . $label($grade) : '' }}
        </p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    @if($grades->count() > 1)
                        <a href="{{ route('teacher.subjects.show', ['subject' => $subject->id]) }}" wire:navigate
                           class="pill {{ $grade ? '' : 'pill-active' }}">All grades</a>
                        @foreach($grades as $g)
                            <a href="{{ route('teacher.subjects.show', ['subject' => $subject->id, 'grade' => $g]) }}" wire:navigate
                               class="pill {{ (string) $grade === (string) $g ? 'pill-active' : '' }}">{{ $label($g) }}</a>
                        @endforeach
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('teacher.subjects.show', ['subject' => $subject->id]) }}" class="flex items-center gap-2">
                        @if($grade)<input type="hidden" name="grade" value="{{ $grade }}">@endif
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search learner"
                               class="rounded-md border-gray-300 shadow-sm text-sm">
                        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
                    </form>
                    @php
    $oneGrade = $grades->count() === 1 ? $grades->first() : null;
    $target   = $grade ?: $oneGrade;
@endphp

@if($groups->count() > 1)
    <a href="{{ route('teacher.announcements.create', ['audience' => $target ? 'grade:' . $target . ':' . $subject->id : 'all:' . $subject->id]) }}"
       wire:navigate class="btn btn-primary btn-sm">
        <x-icon name="megaphone" :size="14" /> {{ $target ? 'Announce to ' . $label($target) : 'Announce to all my classes' }}
    </a>
@endif
                    <a href="{{ route('messages.index', ['tab' => 'parents']) }}" wire:navigate class="btn btn-navy btn-sm">
                        <x-icon name="chat" :size="14" /> Message Parents
                    </a>
                </div>
            </div>

            @foreach($groups as $g)
                <div class="card overflow-hidden">
                    <div class="panel-head">
                        <div>
                            <h3>{{ $g->class->name }}</h3>
                            <div class="text-xs text-ink-muted">
                                {{ $g->class->grade ? $label($g->class->grade) . ' · ' : '' }}{{ $g->students->count() }}
                                {{ \Illuminate\Support\Str::plural('learner', $g->students->count()) }} taking {{ $subject->name }}
                            </div>
                        </div>
                        <a href="{{ route('teacher.announcements.create', ['audience' => 'subject:' . $g->class->id . ':' . $subject->id]) }}"
                           wire:navigate class="btn btn-primary btn-sm">
                            <x-icon name="megaphone" :size="14" /> Announce
                        </a>
                    </div>

                    @forelse($g->students as $s)
                        <div class="list-row flex-wrap items-start">
                            <x-avatar :name="$s->first_name . ' ' . $s->last_name" />

                            <div class="min-w-0 w-52">
                                <div class="font-semibold text-ink">{{ $s->last_name }}, {{ $s->first_name }}</div>
                                <div class="text-xs text-ink-muted">{{ $s->admission_number ?? 'No admission number' }}</div>
                            </div>

                            <div class="grid flex-1 gap-2 sm:grid-cols-2 min-w-[16rem]">
                                @forelse($s->parents as $p)
                                    <div class="rounded-xl border border-navy-100 bg-navy-50 p-2.5 text-sm">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold text-ink">{{ $p->name }}</div>
                                                <div class="text-xs text-ink-muted">{{ ucfirst($p->pivot->relationship ?? 'parent') }}</div>
                                            </div>
                                            <a href="{{ route('messages.show', $p) }}" wire:navigate class="btn btn-primary btn-sm shrink-0">
                                                <x-icon name="chat" :size="13" /> Message
                                            </a>
                                        </div>

                                        <div class="mt-2 space-y-1">
                                            @if($p->phone)
                                                <a href="tel:{{ $p->phone }}" class="contact-line"><x-icon name="phone" :size="13" /> {{ $p->phone }}</a>
                                            @endif
                                            @if($p->realEmail())
                                                <a href="mailto:{{ $p->realEmail() }}" class="contact-line break-all"><x-icon name="mail" :size="13" /> {{ $p->realEmail() }}</a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-navy-200 p-2.5 text-sm text-ink-muted">No parent linked yet.</div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <x-empty title="No learners yet"
                                 :text="'Learners appear here once they are placed in ' . $g->class->name . ' and take ' . $subject->name . '.'"
                                 icon="users" class="!py-6" />
                    @endforelse
                </div>
            @endforeach

            <a href="{{ route('teacher.subjects.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold text-navy-500 hover:text-brand-red transition">
                <x-icon name="arrow" :size="16" class="rotate-180" /> Back to my subjects
            </a>
        </div>
    </div>
</x-app-layout>