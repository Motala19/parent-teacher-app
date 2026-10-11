@php
    $icons = ['Schools' => 'home', 'School Admins' => 'user', 'Classes' => 'layers', 'Teachers' => 'book', 'Learners' => 'users', 'Parents' => 'user'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Super Admin Dashboard</h2>
        <p>Everything across all schools at a glance.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 stagger">
                @foreach($stats as $label => $value)
                    <x-stat-card :label="$label" :value="$value" :icon="$icons[$label] ?? 'grid'" />
                @endforeach
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                <a href="{{ route('admin.schools.create') }}" wire:navigate class="tile tile-rose">
                    <span class="tile-icon"><x-icon name="plus" /></span>
                    <div><div class="tile-title">Add a school</div><div class="tile-sub">Register a new school</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('admin.school-admins.create') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="user-plus" /></span>
                    <div><div class="tile-title">Add school admin</div><div class="tile-sub">Give a school its admin</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('admin.teachers.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="book" /></span>
                    <div><div class="tile-title">Teachers</div><div class="tile-sub">All schools</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('admin.students.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="users" /></span>
                    <div><div class="tile-title">Learners</div><div class="tile-sub">Search all schools</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
            </div>
        </div>
    </div>
</x-app-layout>