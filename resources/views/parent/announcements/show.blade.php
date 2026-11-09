@php($names = $a->learnerNames($children))

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $a->typeLabel() }}</h2>
        <p>{{ $a->audienceLabel() }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <article class="card note tone-{{ $a->type }} p-6 sm:p-8">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if($names)<span class="for-chip"><x-icon name="user" :size="11" /> For {{ implode(', ', $names) }}</span>@endif
                    <span class="badge">{{ $a->typeLabel() }}</span>
                    <span class="chip">{{ $a->audienceLabel() }}</span>
                </div>

                <h1 class="text-2xl font-extrabold text-ink">{{ $a->title }}</h1>

                @if($a->due_date)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4 due-{{ $a->done ? 'done' : $a->dueState() }}"
                         style="background: var(--due-bg); color: var(--due-fg)">
                        <div>
                            <div class="font-extrabold">{{ $a->done ? 'Done' : $a->dueLabel() }}@if($names) · {{ implode(', ', $names) }}@endif</div>
                            <div class="text-sm opacity-80">{{ $a->due_date->format('l j F Y') }}</div>
                        </div>

                        <form action="{{ $a->done ? route('parent.announcements.undone', $a) : route('parent.announcements.done', $a) }}" method="POST">
                            @csrf
                            @if($a->done) @method('DELETE') @endif
                            <button type="submit" class="btn {{ $a->done ? 'btn-ghost' : 'btn-primary' }} btn-sm">
                                <x-icon name="check" :size="14" /> {{ $a->done ? 'Undo' : 'Mark as done' }}
                            </button>
                        </form>
                    </div>
                @endif

                <p class="mt-4 text-ink-soft whitespace-pre-line leading-relaxed">{{ $a->body }}</p>

                <x-attachment-list :attachments="$a->attachments" class="mt-5" />

                <div class="mt-5 flex items-center gap-2 text-xs text-ink-muted">
                    <x-avatar :name="$a->user->name ?? 'School'" size="h-6 w-6 text-[10px]" />
                    <span class="font-semibold">{{ $a->user->name ?? 'School' }}</span>
                    <span>· {{ ($a->published_at ?? $a->created_at)->format('d M Y H:i') }}</span>
                </div>
            </article>

            <form action="{{ route('parent.announcements.dismiss', $a) }}" method="POST"
                  onsubmit="return confirm('Remove this notice from your list? It stays on record at the school.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">Clear this notice</button>
            </form>

            <a href="{{ route('parent.announcements.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold text-navy-500 hover:text-brand-red transition">
                <x-icon name="arrow" :size="16" class="rotate-180" /> Back to announcements
            </a>
        </div>
    </div>
</x-app-layout>