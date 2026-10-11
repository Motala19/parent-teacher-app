<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $me = auth()->user();
    $unread = $me->hasAnyRole(['teacher', 'parent', 'school-admin']) ? $me->unreadMessageCount() : 0;
    $dashRoutes = ['dashboard', 'admin.dashboard', 'school.dashboard', 'teacher.dashboard', 'parent.dashboard'];
@endphp

<nav x-data="{ open: false }" class="nav-shell">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="nav-brand">
                    <x-application-logo class="h-9 w-9" />
                    <span class="hidden 2xl:inline text-base">{{ config('app.name', 'School') }}</span>
                </a>

                <div class="hidden xl:flex items-center gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs(...$dashRoutes)" wire:navigate>{{ __('Dashboard') }}</x-nav-link>

                    @role('super-admin')
                        <x-nav-link :href="route('admin.schools.index')" :active="request()->routeIs('admin.schools.*')" wire:navigate>{{ __('Schools') }}</x-nav-link>
                        <x-nav-link :href="route('admin.school-admins.index')" :active="request()->routeIs('admin.school-admins.*')" wire:navigate>{{ __('School Admins') }}</x-nav-link>
                        <x-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')" wire:navigate>{{ __('Classes') }}</x-nav-link>
                        <x-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')" wire:navigate>{{ __('Teachers') }}</x-nav-link>
                        <x-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')" wire:navigate>{{ __('Learners') }}</x-nav-link>
                        <x-nav-link :href="route('admin.parents.index')" :active="request()->routeIs('admin.parents.*')" wire:navigate>{{ __('Parents') }}</x-nav-link>
                    @endrole

                    @role('school-admin')
                        <x-nav-link :href="route('school.classes.index')" :active="request()->routeIs('school.classes.*')" wire:navigate>{{ __('Classes') }}</x-nav-link>
                        <x-nav-link :href="route('school.subjects.index')" :active="request()->routeIs('school.subjects.*')" wire:navigate>{{ __('Subjects') }}</x-nav-link>
                        <x-nav-link :href="route('school.teachers.index')" :active="request()->routeIs('school.teachers.*')" wire:navigate>{{ __('Teachers') }}</x-nav-link>
                        <x-nav-link :href="route('school.students.index')" :active="request()->routeIs('school.students.*')" wire:navigate>{{ __('Learners') }}</x-nav-link>
                        <x-nav-link :href="route('school.parents.index')" :active="request()->routeIs('school.parents.*')" wire:navigate>{{ __('Parents') }}</x-nav-link>
                        <x-nav-link :href="route('school.announcements.index')" :active="request()->routeIs('school.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-nav-link>
                        <x-nav-link :href="route('directory.staff')" :active="request()->routeIs('directory.staff')" wire:navigate>{{ __('Staff') }}</x-nav-link>
                        <x-nav-link :href="route('school.my-school.edit')" :active="request()->routeIs('school.my-school.*')" wire:navigate>{{ __('My School') }}</x-nav-link>
                    @endrole

                    @role('teacher')
                        <x-nav-link :href="route('teacher.classes.index')" :active="request()->routeIs('teacher.classes.*')" wire:navigate>{{ __('My Classes') }}</x-nav-link>
                        <x-nav-link :href="route('directory.learners')" :active="request()->routeIs('directory.learners')" wire:navigate>{{ __('Learners') }}</x-nav-link>
                        <x-nav-link :href="route('directory.staff')" :active="request()->routeIs('directory.staff')" wire:navigate>{{ __('Staff') }}</x-nav-link>
                        <x-nav-link :href="route('teacher.announcements.index')" :active="request()->routeIs('teacher.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-nav-link>
                    @endrole

                    @role('parent')
                        <x-nav-link :href="route('parent.announcements.index')" :active="request()->routeIs('parent.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-nav-link>
                    @endrole

                    @hasanyrole('teacher|parent|school-admin')
                        <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" wire:navigate>
                            {{ __('Messages') }}
                            @if($unread)
                                <span class="badge-dot">{{ $unread }}</span>
                            @endif
                        </x-nav-link>
                    @endhasanyrole
                </div>
            </div>

            <div class="hidden xl:flex xl:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition focus:outline-none">
                            <x-avatar :name="$me->name" size="h-8 w-8 text-xs" />
                            <span class="hidden 2xl:inline">{{ $me->name }}</span>
                            <x-icon name="chevron" :size="16" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="text-sm font-bold text-ink">{{ $me->name }}</div>
                            <div class="text-xs text-ink-muted">{{ $me->email }}</div>
                            <div class="mt-1 text-xs font-semibold text-navy-600">{{ $me->roleLabel() }}</div>
                        </div>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-white/80 hover:text-white hover:bg-white/10 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" style="display:none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-transition.opacity.duration.200ms style="display:none" class="xl:hidden border-t border-white/10">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs(...$dashRoutes)" wire:navigate>{{ __('Dashboard') }}</x-responsive-nav-link>

            @role('super-admin')
                <x-responsive-nav-link :href="route('admin.schools.index')" :active="request()->routeIs('admin.schools.*')" wire:navigate>{{ __('Schools') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.school-admins.index')" :active="request()->routeIs('admin.school-admins.*')" wire:navigate>{{ __('School Admins') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')" wire:navigate>{{ __('Classes') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')" wire:navigate>{{ __('Teachers') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')" wire:navigate>{{ __('Learners') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.parents.index')" :active="request()->routeIs('admin.parents.*')" wire:navigate>{{ __('Parents') }}</x-responsive-nav-link>
            @endrole

            @role('school-admin')
                <x-responsive-nav-link :href="route('school.classes.index')" :active="request()->routeIs('school.classes.*')" wire:navigate>{{ __('Classes') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.subjects.index')" :active="request()->routeIs('school.subjects.*')" wire:navigate>{{ __('Subjects') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.teachers.index')" :active="request()->routeIs('school.teachers.*')" wire:navigate>{{ __('Teachers') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.students.index')" :active="request()->routeIs('school.students.*')" wire:navigate>{{ __('Learners') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.parents.index')" :active="request()->routeIs('school.parents.*')" wire:navigate>{{ __('Parents') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.announcements.index')" :active="request()->routeIs('school.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('directory.staff')" :active="request()->routeIs('directory.staff')" wire:navigate>{{ __('Staff') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school.my-school.edit')" :active="request()->routeIs('school.my-school.*')" wire:navigate>{{ __('My School') }}</x-responsive-nav-link>
            @endrole

            @role('teacher')
                <x-responsive-nav-link :href="route('teacher.classes.index')" :active="request()->routeIs('teacher.classes.*')" wire:navigate>{{ __('My Classes') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('directory.learners')" :active="request()->routeIs('directory.learners')" wire:navigate>{{ __('Learners') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('directory.staff')" :active="request()->routeIs('directory.staff')" wire:navigate>{{ __('Staff') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('teacher.announcements.index')" :active="request()->routeIs('teacher.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-responsive-nav-link>
            @endrole

            @role('parent')
                <x-responsive-nav-link :href="route('parent.announcements.index')" :active="request()->routeIs('parent.announcements.*')" wire:navigate>{{ __('Announcements') }}</x-responsive-nav-link>
            @endrole

            @hasanyrole('teacher|parent|school-admin')
                <x-responsive-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" wire:navigate>
                    <span class="inline-flex items-center gap-2">
                        {{ __('Messages') }}
                        @if($unread)
                            <span class="badge-dot">{{ $unread }}</span>
                        @endif
                    </span>
                </x-responsive-nav-link>
            @endhasanyrole
        </div>

        <div class="px-4 py-4 border-t border-white/10">
            <div class="flex items-center gap-3 mb-3">
                <x-avatar :name="$me->name" />
                <div>
                    <div class="font-semibold text-white">{{ $me->name }}</div>
                    <div class="text-sm text-white/60">{{ $me->email }}</div>
                </div>
            </div>

            <button wire:click="logout" class="w-full text-start">
                <x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link>
            </button>
        </div>
    </div>
</nav>