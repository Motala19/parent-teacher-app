@php($unreadTotal = auth()->user()->unreadNotifications()->count())

<x-app-layout>
    <x-slot name="header">
        <h2>Notifications</h2>
        <p>New messages, newsletters, question papers and announcements.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2">
                    <a href="{{ route('notifications.index') }}" wire:navigate class="pill {{ $filter === 'all' ? 'pill-active' : '' }}">All</a>
                    <a href="{{ route('notifications.index', ['show' => 'unread']) }}" wire:navigate class="pill {{ $filter === 'unread' ? 'pill-active' : '' }}">
                        Unread @if($unreadTotal)({{ $unreadTotal }})@endif
                    </a>
                </div>

                <div class="flex gap-2">
                    @if($unreadTotal)
                        <form action="{{ route('notifications.read-all') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Mark all read</button>
                        </form>
                    @endif
                    <a href="{{ route('notifications.settings') }}" wire:navigate class="btn btn-ghost btn-sm">Settings</a>
                </div>
            </div>

            <div class="card overflow-hidden">
                @forelse($notifications as $n)
                    @php($d = $n->data)
                    <a href="{{ route('notifications.open', $n->id) }}" class="notif-item {{ $n->read_at ? '' : 'is-unread' }}">
                        <span class="notif-icon"><x-icon :name="$d['icon'] ?? 'bell'" :size="18" /></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-sm text-ink {{ $n->read_at ? 'font-semibold' : 'font-extrabold' }}">{{ $d['title'] ?? 'Notification' }}</span>
                                <span class="shrink-0 text-[11px] text-ink-muted">{{ $n->updated_at->diffForHumans(null, true, true) }}</span>
                            </div>
                            <div class="truncate text-sm text-ink-muted">{{ $d['body'] ?? '' }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                @if(! empty($d['where']))
                                    <span class="chip">{{ $d['where'] }}</span>
                                @endif
                                @if(($d['count'] ?? 1) > 1)
                                    <span class="text-xs font-bold text-brand-red">{{ $d['count'] }} new messages</span>
                                @endif
                            </div>
                        </div>

                        @unless($n->read_at)<span class="notif-dot"></span>@endunless
                    </a>
                @empty
                    <x-empty :title="$filter === 'unread' ? 'Nothing unread' : 'No notifications yet'"
                             text="New messages, newsletters and announcements will show up here."
                             icon="bell" />
                @endforelse
            </div>

            <div>{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>