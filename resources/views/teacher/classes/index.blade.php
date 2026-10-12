<x-app-layout>
    <x-slot name="header">
        <h2>My Classes</h2>
        <p>Open a class to announce, message parents or manage learners.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
                @forelse($classes as $class)
                    <div class="card card-hover overflow-hidden flex flex-col">
                        <div class="class-banner">
                            <span class="grade-pill">{{ $class->grade ? ($class->grade === 'R' ? 'Grade R' : 'Grade ' . $class->grade) : 'No grade set' }}</span>
                            <h3>{{ $class->name }}</h3>
                        </div>
                        <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-ink-muted">
                                <x-icon name="users" :size="16" /> {{ $class->students_count }} learners
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('teacher.classes.show', $class) }}" wire:navigate class="btn btn-navy btn-sm">Open</a>
                                <a href="{{ route('teacher.announcements.create', ['class' => $class->id]) }}" wire:navigate class="btn btn-primary btn-sm">
                                    <x-icon name="megaphone" :size="14" /> Announce
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card sm:col-span-2 lg:col-span-3">
                        <x-empty title="You aren't a class teacher" text="Open My Subjects to see the learners you teach." icon="layers" />
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>