@php
    $order   = ['newsletter', 'timetable', 'stationery', 'year_plan', 'homework', 'assignment', 'project', 'test', 'question_paper', 'reminder', 'event', 'urgent', 'general'];
    $current = $type ?? null;
    $dueOnly = request()->boolean('due');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $dueOnly ? 'Due soon' : 'Announcements' }}</h2>
        <p>{{ $dueOnly ? 'Homework, assignments, projects and tests still to do.' : 'Newsletters, homework, timetables and notices from the school.' }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('parent.announcements.index') }}" class="pill {{ $current || $dueOnly ? '' : 'pill-active' }}">All</a>
                <a href="{{ route('parent.announcements.index', ['due' => 1]) }}" class="pill {{ $dueOnly ? 'pill-active' : '' }}">Due soon</a>
                @foreach($order as $value)
                    <a href="{{ route('parent.announcements.index', ['type' => $value]) }}"
                       class="pill {{ $current === $value ? 'pill-active' : '' }}">{{ \App\Models\Announcement::TYPES[$value] }}</a>
                @endforeach
            </div>

            @forelse($announcements as $a)
                @php
                    $canTick = \App\Models\Announcement::takesDue($a->type) && $a->due_date;
                    $names   = $a->learnerNames($children);
                @endphp

                <article class="card note card-link tone-{{ $a->type }} p-5 {{ $a->done ? 'opacity-70' : '' }}">
                    <div class="flex items-start gap-3">
                        @if($canTick)
                            <form action="{{ $a->done ? route('parent.announcements.undone', $a) : route('parent.announcements.done', $a) }}" method="POST" class="pt-0.5">
                                @csrf
                                @if($a->done) @method('DELETE') @endif
                                <button type="submit" class="check {{ $a->done ? 'is-on' : '' }}" title="{{ $a->done ? 'Mark as not done' : 'Mark as done' }}">
                                    @if($a->done)<x-icon name="check" :size="14" />@endif
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('parent.announcements.show', $a) }}" class="block min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                @if($names)<span class="for-chip"><x-icon name="user" :size="11" /> For {{ implode(', ', $names) }}</span>@endif
                                @if($a->is_pinned)<span class="badge" style="--tone-bg:#fff1d6;--tone-fg:#8a5d05">Pinned</span>@endif
                                <span class="badge">{{ $a->typeLabel() }}</span>
                                <span class="chip">{{ $a->audienceLabel() }}</span>
                                @if($a->dueLabel())
                                    <span class="due due-{{ $a->done ? 'done' : $a->dueState() }}"><x-icon name="calendar" :size="12" /> {{ $a->done ? 'Done' : $a->dueLabel() }}</span>
                                @endif
                            </div>

                            <h3 class="font-bold text-ink clamp-2">{{ $a->title }}</h3>
                            <p class="mt-1 text-sm text-ink-muted clamp-2">{{ $a->excerpt(180) }}</p>

                            <div class="mt-3 flex items-center gap-3 text-xs text-ink-muted">
                                <span>{{ $a->user->name ?? 'School' }} · {{ ($a->published_at ?? $a->created_at)->diffForHumans(null, true, true) }}</span>
                                @if($a->attachments->isNotEmpty())
                                    <span class="inline-flex items-center gap-1"><x-icon name="paperclip" :size="13" /> {{ $a->attachments->count() }}</span>
                                @endif
                                <span class="ms-auto font-semibold text-navy-500">Read <x-icon name="arrow" :size="12" class="inline" /></span>
                            </div>
                        </a>
                    </div>
                </article>
            @empty
                <div class="card">
                    <x-empty :title="$dueOnly ? 'Nothing due' : ($current ? 'Nothing in this category yet' : 'No announcements yet')"
                             :text="$dueOnly ? 'You are all caught up.' : 'New notices and files from the school will appear here.'" icon="megaphone" />
                </div>
            @endforelse

            <div>{{ $announcements->links() }}</div>
        </div>
    </div>
</x-app-layout>