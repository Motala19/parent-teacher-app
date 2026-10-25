@php
    $unreadMessages = auth()->user()->unreadMessageCount();
@endphp

<nav class="tabbar xl:hidden" aria-label="Main">
    <a href="{{ route('parent.dashboard') }}" wire:navigate class="tab {{ request()->routeIs('parent.dashboard') ? 'is-active' : '' }}">
        <x-icon name="home" :size="22" /><span>Home</span>
    </a>

    <a href="{{ route('parent.announcements.index') }}" wire:navigate class="tab {{ request()->routeIs('parent.announcements.*') ? 'is-active' : '' }}">
        <x-icon name="megaphone" :size="22" /><span>Notices</span>
    </a>

    <a href="{{ route('messages.index') }}" wire:navigate class="tab {{ request()->routeIs('messages.*') ? 'is-active' : '' }}">
        <x-icon name="chat" :size="22" /><span>Messages</span>
        @if($unreadMessages)
            <span class="badge-dot">{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>
        @endif
    </a>

    <a href="{{ route('parent.me') }}" wire:navigate class="tab {{ request()->routeIs('parent.me') ? 'is-active' : '' }}">
        <x-icon name="user" :size="22" /><span>Me</span>
    </a>
</nav>