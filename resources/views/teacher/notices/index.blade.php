@php
    $order   = ['newsletter', 'urgent', 'event', 'reminder', 'homework', 'assignment', 'question_paper', 'general'];
    $current = $type ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Notices</h2>
        <p>Messages from the school: whole-school, teachers only, SMT and your classes.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher.notices.index') }}" class="pill {{ $current ? '' : 'pill-active' }}">All</a>
                @foreach($order as $value)
                    <a href="{{ route('teacher.notices.index', ['type' => $value]) }}"
                       class="pill {{ $current === $value ? 'pill-active' : '' }}">{{ \App\Models\Announcement::TYPES[$value] }}</a>
                @endforeach
            </div>

            @forelse($announcements as $a)
                <article class="card note card-hover tone-{{ $a->type }} p-6">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="badge">{{ $a->typeLabel() }}</span>
                        <span class="chip">{{ $a->audienceLabel() }}</span>
                    </div>

                    <h3 class="text-lg font-bold text-ink">{{ $a->title }}</h3>
                    <p class="mt-2 text-ink-soft whitespace-pre-line">{{ $a->body }}</p>

                    <x-attachment-list :attachments="$a->attachments" class="mt-4" />

                    <div class="mt-4 flex items-center gap-2 text-xs text-ink-muted">
                        <x-avatar :name="$a->user->name ?? 'School'" size="h-6 w-6 text-[10px]" />
                        <span class="font-semibold">{{ $a->user->name ?? 'School' }}</span>
                        <span>· {{ ($a->published_at ?? $a->created_at)->format('d M Y H:i') }}</span>
                    </div>
                </article>
            @empty
                <div class="card">
                    <x-empty title="No notices yet" text="Announcements addressed to you will appear here." icon="bell" />
                </div>
            @endforelse

            <div>{{ $announcements->links() }}</div>
        </div>
    </div>
</x-app-layout>