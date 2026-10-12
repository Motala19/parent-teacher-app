@php
    $label = fn ($g) => $g === 'R' ? 'Grade R' : 'Grade ' . $g;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>My Subjects</h2>
        <p>Click a subject to see the learners taking it, grade by grade.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
                @forelse($subjects as $row)
                    <div class="card card-hover overflow-hidden flex flex-col">
                        <a href="{{ route('teacher.subjects.show', ['subject' => $row->subject->id]) }}" wire:navigate class="class-banner block">
                            <span class="grade-pill">{{ $row->grades->map($label)->join(', ') ?: 'No grade set' }}</span>
                            <h3>{{ $row->subject->name }}</h3>
                        </a>

                        <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                            <div>
                                <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-muted">
                                    <x-icon name="users" :size="16" />
                                    {{ $row->total }} {{ \Illuminate\Support\Str::plural('learner', $row->total) }}
                                    in {{ $row->classes->count() }} {{ \Illuminate\Support\Str::plural('class', $row->classes->count()) }}
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach($row->classes as $c)
                                        <a href="{{ route('teacher.subjects.show', ['subject' => $row->subject->id, 'grade' => $c->class->grade]) }}"
                                           wire:navigate class="chip" title="Show {{ $c->class->name }}'s grade">
                                            {{ $c->class->name }} · {{ $c->learners }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('teacher.subjects.show', ['subject' => $row->subject->id]) }}" wire:navigate class="btn btn-navy btn-sm">
                                    <x-icon name="users" :size="14" /> View learners
                                </a>
                                @if($row->classes->count() === 1)
                                    <a href="{{ route('teacher.announcements.create', ['audience' => 'subject:' . $row->classes->first()->class->id . ':' . $row->subject->id]) }}"
                                       wire:navigate class="btn btn-primary btn-sm">
                                        <x-icon name="megaphone" :size="14" /> Announce
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card sm:col-span-2 lg:col-span-3">
                        <x-empty title="No subjects yet" text="You haven't been assigned to teach a subject. Please ask your school admin." icon="book" />
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>