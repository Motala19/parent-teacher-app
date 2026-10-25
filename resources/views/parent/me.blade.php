@php
    $me       = auth()->user();
    $children = $me->children()->with('classRoom')->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Me</h2>
        <p>Your account and app settings.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 space-y-5">

            <div class="card p-6 flex items-center gap-4">
                <x-avatar :name="$me->name" size="h-16 w-16 text-xl" />
                <div class="min-w-0">
                    <div class="text-lg font-bold text-ink">{{ $me->name }}</div>
                    <div class="truncate text-sm text-ink-muted">{{ $me->realEmail() ?? 'No email on file' }}</div>
                    <div class="text-sm text-ink-muted">{{ $me->phone ?? 'No phone number on file' }}</div>
                </div>
            </div>

            <x-install-app :always="true" />

            <div class="card overflow-hidden">
                <div class="panel-head"><h3>My children</h3></div>

                @forelse($children as $child)
                    <div class="list-row">
                        <x-avatar :name="$child->first_name . ' ' . $child->last_name" />
                        <div class="min-w-0">
                            <div class="font-semibold text-ink">{{ $child->first_name }} {{ $child->last_name }}</div>
                            <div class="text-xs text-ink-muted">
                                {{ $child->classRoom->name ?? 'Not in a class yet' }}@if($child->grade) · Grade {{ $child->grade }}@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty title="No children linked" text="Please contact your school." icon="users" class="!py-6" />
                @endforelse
            </div>

            <div class="card overflow-hidden">
                <a href="{{ route('notifications.index') }}" wire:navigate class="list-row">
                    <span class="stat-icon"><x-icon name="bell" :size="20" /></span>
                    <span class="font-semibold text-ink">Notifications</span>
                    <x-icon name="arrow" :size="16" class="ms-auto text-ink-muted" />
                </a>
                <a href="{{ route('notifications.settings') }}" wire:navigate class="list-row">
                    <span class="stat-icon"><x-icon name="mail" :size="20" /></span>
                    <span class="font-semibold text-ink">Email settings</span>
                    <x-icon name="arrow" :size="16" class="ms-auto text-ink-muted" />
                </a>
                <a href="{{ route('parent.announcements.index', ['type' => 'newsletter']) }}" wire:navigate class="list-row">
                    <span class="stat-icon"><x-icon name="file" :size="20" /></span>
                    <span class="font-semibold text-ink">Newsletters</span>
                    <x-icon name="arrow" :size="16" class="ms-auto text-ink-muted" />
                </a>
            </div>

            <p class="text-center text-xs text-ink-muted">To change your name, phone number or email, please contact your school.</p>

            <form action="{{ route('me.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost w-full">Log out</button>
            </form>
        </div>
    </div>
</x-app-layout>