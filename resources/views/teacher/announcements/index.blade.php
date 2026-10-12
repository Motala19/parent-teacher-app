@php
    $types   = \App\Models\Announcement::teacherTypes();
    $current = request('type');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>My Announcements</h2>
        <p>Everything you've posted to your classes, including the files you shared.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.announcements.index') }}" class="pill {{ $current ? '' : 'pill-active' }}">All</a>
                    @foreach($types as $value => $label)
                        <a href="{{ route('teacher.announcements.index', ['type' => $value]) }}"
                           class="pill {{ $current === $value ? 'pill-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>

                <a href="{{ route('teacher.announcements.create') }}" wire:navigate class="btn btn-primary">
                    <x-icon name="plus" :size="16" /> Post
                </a>
            </div>

            @forelse($announcements as $a)
                <article class="card note card-hover tone-{{ $a->type }} p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="badge">{{ $a->typeLabel() }}</span>
                                <span class="chip">{{ $a->audienceLabel() }}</span>
                                <span class="text-xs text-ink-muted">{{ $a->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <h3 class="font-bold text-ink">{{ $a->title }}</h3>
                            <p class="mt-1 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit($a->body, 140) }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.announcements.edit', $a) }}" wire:navigate class="btn btn-ghost btn-sm">Edit</a>
                            <form action="{{ route('teacher.announcements.destroy', $a) }}" method="POST"
                                  onsubmit="return confirm('Delete this announcement and its files?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--rose-from)">Delete</button>
                            </form>
                        </div>
                    </div>

                    <x-attachment-list :attachments="$a->attachments" class="mt-4" />
                </article>
            @empty
                <div class="card">
                    <x-empty title="Nothing here yet" text="Post homework, an assignment or a question paper for your class." icon="megaphone" />
                </div>
            @endforelse

            <div>{{ $announcements->links() }}</div>
        </div>
    </div>
</x-app-layout>