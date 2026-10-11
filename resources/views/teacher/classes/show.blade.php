<x-app-layout>
    <x-slot name="header">
        <h2>
            {{ $class->name }}
            @if($class->grade)
                <span class="ms-2 text-base font-medium">{{ $class->grade === 'R' ? 'Grade R' : 'Grade ' . $class->grade }}</span>
            @endif
        </h2>
        <p>{{ $students->count() }} learners · {{ $class->subjects->count() }} subjects</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('teacher.announcements.create', ['class' => $class->id]) }}" wire:navigate class="btn btn-primary">
                    <x-icon name="megaphone" :size="16" /> Post Announcement
                </a>
                <a href="{{ route('messages.index', ['tab' => 'parents']) }}" wire:navigate class="btn btn-navy">
    <x-icon name="chat" :size="16" /> Message Parents
</a>
<a href="{{ route('directory.learners', ['class_id' => $class->id]) }}" wire:navigate class="btn btn-ghost">
    <x-icon name="users" :size="16" /> Learner Contacts
</a>
                @if($canManage)
                    <a href="{{ route('teacher.classes.learners.create', $class) }}" wire:navigate class="btn btn-ghost">
                        <x-icon name="user-plus" :size="16" /> Add Learners
                    </a>
                @endif
            </div>

            <div class="grid lg:grid-cols-3 gap-6">

                <div class="card lg:col-span-2 overflow-hidden">
                    <div class="panel-head">
                        <h3>Learners</h3>
                        <span class="badge">{{ $students->count() }}</span>
                    </div>

                    @forelse($students as $student)
                        <div class="list-row flex-wrap">
                            <x-avatar :name="$student->first_name . ' ' . $student->last_name" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-ink">{{ $student->last_name }}, {{ $student->first_name }}</div>
                                <div class="text-xs text-ink-muted">{{ $student->admission_number ?? 'No admission number' }}</div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @forelse($student->parents as $p)
                                    <a href="{{ route('messages.show', $p) }}" wire:navigate class="chip" title="Message {{ $p->name }}">
                                        <x-icon name="chat" :size="13" /> {{ $p->name }}
                                    </a>
                                @empty
                                    <span class="text-xs text-gray-400">No parent linked</span>
                                @endforelse

                                @if($canManage)
                                    <form action="{{ route('teacher.classes.learners.destroy', [$class, $student]) }}" method="POST"
                                          onsubmit="return confirm('Remove this learner from the class?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-empty title="No learners yet" text="Add learners from your grade to start this class." icon="users">
                            @if($canManage)
                                <a href="{{ route('teacher.classes.learners.create', $class) }}" wire:navigate class="btn btn-primary btn-sm mt-4">Add learners</a>
                            @endif
                        </x-empty>
                    @endforelse
                </div>

                <div class="space-y-6">
                    <div class="card overflow-hidden">
                        <div class="panel-head"><h3>Subjects</h3></div>
                        @forelse($class->subjects as $subject)
                            <div class="list-row justify-between">
                                <span class="font-semibold text-ink">{{ $subject->name }}</span>
                                <span class="text-xs text-ink-muted">{{ $teachers->get($subject->pivot->teacher_id)?->name ?? 'No teacher yet' }}</span>
                            </div>
                        @empty
                            <x-empty title="No subjects" text="The school admin sets these." icon="book" class="!py-6" />
                        @endforelse
                    </div>

                    <div class="card overflow-hidden">
                        <div class="panel-head">
                            <h3>Recent announcements</h3>
                            <a href="{{ route('teacher.announcements.create', ['class' => $class->id]) }}" wire:navigate class="icon-btn" title="Post new"><x-icon name="plus" :size="18" /></a>
                        </div>
                        @forelse($announcements as $a)
                            <div class="list-row note tone-{{ $a->type }}">
                                <div class="min-w-0 flex-1">
                                    <span class="badge">{{ $a->typeLabel() }}</span>
                                    <div class="mt-1 font-semibold text-ink truncate">{{ $a->title }}</div>
                                    <div class="text-xs text-ink-muted">{{ $a->created_at->format('d M Y H:i') }}</div>
                                </div>
                            </div>
                        @empty
                            <x-empty title="Nothing posted yet" text="Post homework or a reminder for this class." icon="megaphone" class="!py-6" />
                        @endforelse
                    </div>
                </div>
            </div>

            <a href="{{ route('teacher.classes.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold text-navy-500 hover:text-brand-red transition">
                <x-icon name="arrow" :size="16" class="rotate-180" /> Back to my classes
            </a>
        </div>
    </div>
</x-app-layout>