@php
    $gradeName = fn ($g) => ($g === null || $g === '') ? 'No grade' : ($g === 'R' ? 'Grade R' : 'Grade ' . $g);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ auth()->user()->school->name }}</h2>
        <p>School dashboard. Manage people, classes and communication.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Numbers --}}
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 stagger">
                <x-stat-card label="Classes" :value="$stats['classes']" icon="layers" :href="route('school.classes.index')" />
                <x-stat-card label="Teachers" :value="$stats['teachers']" icon="book" :href="route('school.teachers.index')" />
                <x-stat-card label="Learners" :value="$stats['learners']" icon="users" :href="route('school.students.index')" />
                <x-stat-card label="Parents" :value="$stats['parents']" icon="user" :href="route('school.parents.index')" />
                <x-stat-card label="Notices this week" :value="$stats['notices']" icon="megaphone" tone="rose" :href="route('school.announcements.index')" />
                <x-stat-card label="Messages this week" :value="$stats['messages']" icon="chat" tone="rose" />
            </div>

            {{-- One place to post everything --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger">
                <a href="{{ route('school.announcements.create') }}" wire:navigate class="tile tile-rose">
                    <span class="tile-icon"><x-icon name="megaphone" /></span>
                    <div><div class="tile-title">Post announcement</div><div class="tile-sub">Newsletters, timetables, year plans, notices and more</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('school.import.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="upload" /></span>
                    <div><div class="tile-title">Import learners &amp; parents</div><div class="tile-sub">From Excel or CSV, parents linked for you</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('school.students.create') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="user-plus" /></span>
                    <div><div class="tile-title">Add a learner</div><div class="tile-sub">Then link their parents</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('school.parents.create') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="users" /></span>
                    <div><div class="tile-title">Add a parent</div><div class="tile-sub">Link to one or more learners</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('school.classes.create') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="layers" /></span>
                    <div><div class="tile-title">Create a class</div><div class="tile-sub">Set the grade and class teacher</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('school.subjects.create') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="book" /></span>
                    <div><div class="tile-title">Add a subject</div><div class="tile-sub">Choose the grades that take it</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
            </div>

            {{-- Needs attention --}}
            <div class="card overflow-hidden">
                <div class="panel-head">
                    <h3>Needs your attention</h3>
                    @if($attention->isNotEmpty())<span class="badge-dot">{{ $attention->count() }}</span>@endif
                </div>

                @forelse($attention as $item)
                    <a href="{{ $item['url'] }}" wire:navigate class="list-row">
                        <span class="stat-icon" style="background:#fde8ee;color:var(--rose-from)"><x-icon name="bell" :size="20" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-ink">{{ $item['count'] }} {{ $item['label'] }}</div>
                            <div class="text-xs text-ink-muted">{{ $item['hint'] }}</div>
                        </div>
                        <x-icon name="arrow" :size="16" class="text-ink-muted" />
                    </a>
                @empty
                    <x-empty title="Everything is in order" text="Every learner has a class and a parent, and every class and subject has a teacher." icon="check" />
                @endforelse
            </div>

            <div class="grid lg:grid-cols-2 gap-6">

                {{-- Learners per grade --}}
                <div class="card overflow-hidden">
                    <div class="panel-head"><h3>Learners per grade</h3></div>

                    <div class="p-5 space-y-3">
                        @forelse($byGrade as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-ink">{{ $gradeName($row->grade) }}</span>
                                    <span class="text-ink-muted">{{ $row->total }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-navy-100">
                                    <div class="h-2.5 rounded-full"
                                         style="width: {{ max(4, round($row->total / $maxGrade * 100)) }}%; background-image: linear-gradient(90deg, var(--navy-600), var(--navy-500));"></div>
                                </div>
                            </div>
                        @empty
                            <x-empty title="No learners yet" text="Import your learners or add them one by one." icon="users" class="!py-6" />
                        @endforelse
                    </div>
                </div>

                {{-- Coming up --}}
                <div class="card overflow-hidden">
                    <div class="panel-head"><h3>Coming up in the next 2 weeks</h3></div>

                    @forelse($upcoming as $u)
                        <div class="list-row note tone-{{ $u->type }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="badge">{{ $u->typeLabel() }}</span>
                                    <span class="due due-{{ $u->dueState() }}"><x-icon name="calendar" :size="12" /> {{ $u->dueLabel() }}</span>
                                </div>
                                <div class="font-semibold text-ink truncate">{{ $u->title }}</div>
                                <div class="text-xs text-ink-muted">{{ $u->audienceLabel() }} · {{ $u->user->name ?? '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <x-empty title="Nothing due" text="Tests, assignments and projects with a date show up here." icon="calendar" class="!py-6" />
                    @endforelse
                </div>
            </div>

            {{-- Recent posts --}}
            <div class="card overflow-hidden">
                <div class="panel-head">
                    <h3>Recent posts</h3>
                    <a href="{{ route('school.announcements.index') }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">View all</a>
                </div>

                @forelse($recent as $a)
                    <a href="{{ route('school.announcements.edit', $a) }}" wire:navigate class="list-row note tone-{{ $a->type }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="badge">{{ $a->typeLabel() }}</span>
                                <span class="chip">{{ $a->audienceLabel() }}</span>
                                @if($a->attachments_count)<span class="text-xs text-ink-muted inline-flex items-center gap-1"><x-icon name="paperclip" :size="12" /> {{ $a->attachments_count }}</span>@endif
                            </div>
                            <div class="font-semibold text-ink truncate">{{ $a->title }}</div>
                            <div class="text-xs text-ink-muted">{{ $a->user->name ?? '-' }} · {{ $a->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <x-empty title="Nothing posted yet" text="Post your first newsletter, timetable or notice." icon="megaphone" />
                @endforelse
            </div>

            @if($lastImport)
                <p class="text-center text-xs text-ink-muted">
                    Last import: {{ $lastImport->original_name }} · {{ $lastImport->statusLabel() }} · {{ $lastImport->created_at->diffForHumans() }}
                    · <a href="{{ route('school.import.show', $lastImport) }}" class="font-semibold text-navy-500 hover:text-brand-red">Open</a>
                </p>
            @endif
        </div>
    </div>
</x-app-layout>