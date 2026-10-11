@php
    $main  = \Illuminate\Support\Arr::except($stats, ['Learners without parents']);
    $icons = ['Classes' => 'layers', 'Teachers' => 'book', 'Learners' => 'users', 'Parents' => 'user'];
    $links = [
        'Classes'  => route('school.classes.index'),
        'Teachers' => route('school.teachers.index'),
        'Learners' => route('school.students.index'),
        'Parents'  => route('school.parents.index'),
    ];
    $noParents = $stats['Learners without parents'] ?? 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ auth()->user()->school->name }}</h2>
        <p>School dashboard. Manage people, classes and communication.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                @foreach($main as $label => $value)
                    <x-stat-card :label="$label" :value="$value" :icon="$icons[$label] ?? 'grid'" :href="$links[$label] ?? null" />
                @endforeach
            </div>

            @if($noParents > 0)
                <a href="{{ route('school.students.index') }}" wire:navigate class="card card-hover note tone-urgent flex items-center gap-4 p-5">
                    <span class="stat-icon" style="background:#fde8ee;color:var(--rose-from)"><x-icon name="bell" :size="22" /></span>
                    <div>
                        <div class="font-bold text-ink">{{ $noParents }} {{ \Illuminate\Support\Str::plural('learner', $noParents) }} without a linked parent</div>
                        <div class="text-sm text-ink-muted">Parents can't receive announcements or messages until they are linked.</div>
                    </div>
                    <x-icon name="arrow" :size="18" class="ms-auto text-brand-red" />
                </a>
            @endif

            <div>
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">Quick actions</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger">
                    <a href="{{ route('school.announcements.create', ['type' => 'newsletter']) }}" wire:navigate class="tile tile-rose">
                        <span class="tile-icon"><x-icon name="file" /></span>
                        <div><div class="tile-title">Send a newsletter</div><div class="tile-sub">Attach a PDF for every parent</div></div>
                        <x-icon name="arrow" :size="18" class="arrow" />
                    </a>
                    <a href="{{ route('school.announcements.create') }}" wire:navigate class="tile">
                        <span class="tile-icon"><x-icon name="megaphone" /></span>
                        <div><div class="tile-title">Post to the whole school</div><div class="tile-sub">Notices, events and urgent news</div></div>
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

                    <a href="{{ route('messages.index') }}" wire:navigate class="tile">
    <span class="tile-icon"><x-icon name="chat" /></span>
    <div><div class="tile-title">Messages</div><div class="tile-sub">Chat with staff and parents</div></div>
    <x-icon name="arrow" :size="18" class="arrow" />
</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>