@php
    $me     = auth()->user();
    $unread = $me->unreadNotifications()->count();
    $latest = $me->notifications()->reorder()->orderByDesc('updated_at')->take(6)->get();
@endphp

<div class="relative" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    <button type="button" class="bell-btn" x-on:click="open = ! open" aria-label="Notifications">
        <x-icon name="bell" :size="20" />
        @if($unread)
            <span class="bell-count">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div class="notif-panel" x-show="open" x-transition x-on:click.outside="open = false" style="display:none">
        <div class="notif-head">
            <span>Notifications</span>
            @if($unread)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="notif-list scroll-soft">
            @forelse($latest as $n)
                @php($d = $n->data)
                <a href="{{ route('notifications.open', $n->id) }}" class="notif-item {{ $n->read_at ? '' : 'is-unread' }}">
                    <span class="notif-icon"><x-icon :name="$d['icon'] ?? 'bell'" :size="17" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm text-ink {{ $n->read_at ? 'font-semibold' : 'font-extrabold' }}">{{ $d['title'] ?? 'Notification' }}</span>
                            <span class="shrink-0 text-[11px] text-ink-muted">{{ $n->updated_at->diffForHumans(null, true, true) }}</span>
                        </div>
                        <div class="truncate text-xs text-ink-muted">{{ $d['body'] ?? '' }}</div>
                        @if(($d['count'] ?? 1) > 1)
                            <div class="mt-0.5 text-xs font-bold text-brand-red">{{ $d['count'] }} new messages</div>
                        @endif
                    </div>
                    @unless($n->read_at)<span class="notif-dot"></span>@endunless
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-ink-muted">You're all caught up.</div>
            @endforelse
        </div>

        <div class="notif-foot">
            <a href="{{ route('notifications.index') }}" wire:navigate>View all</a>
            <a href="{{ route('notifications.settings') }}" wire:navigate>Email settings</a>
        </div>
    </div>
</div>