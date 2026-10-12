@php
    $groups = [
        'School administration' => $staff->filter(fn ($u) => $u->hasRole('school-admin')),
        'Teachers'              => $staff->reject(fn ($u) => $u->hasRole('school-admin')),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Staff Directory</h2>
        <p>Your colleagues at {{ auth()->user()->school->name ?? 'your school' }}. Message them in one click.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <form method="GET" action="{{ route('directory.staff') }}" class="flex flex-wrap items-center gap-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, phone or email"
                       class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm">
                <button type="submit" class="btn btn-ghost btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('directory.staff') }}" wire:navigate class="text-sm text-ink-muted hover:underline">Clear</a>
                @endif
            </form>

            @foreach($groups as $title => $members)
                @if($members->isNotEmpty())
                    <div>
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">{{ $title }}</h3>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 stagger">
                            @foreach($members as $u)
                                @php
                                    $classTeacherOf = $u->classes->where('pivot.is_class_teacher', true)->pluck('name');
                                    $subjects       = $u->teachingSubjects->pluck('name')->unique()->take(6);
                                @endphp

                                <article class="card card-hover flex flex-col p-5">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$u->name" size="h-12 w-12 text-base" />
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-ink">{{ $u->name }}</div>
                                            <span class="badge">{{ $u->roleLabel() }}</span>
                                            @if($u->is_smt)<span class="badge ms-1">SMT{{ $u->position ? ' · ' . $u->position : '' }}</span>@endif
                                        </div>
                                    </div>

                                    @if($classTeacherOf->isNotEmpty())
                                        <div class="mt-3 text-sm text-ink-soft">Class teacher of <strong>{{ $classTeacherOf->join(', ') }}</strong></div>
                                    @endif

                                    @if($subjects->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($subjects as $name)
                                                <span class="chip">{{ $name }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-3 space-y-1.5 text-sm">
                                        @if($u->phone)
                                            <a href="tel:{{ $u->phone }}" class="contact-line"><x-icon name="phone" :size="14" /> {{ $u->phone }}</a>
                                        @endif
                                        @if($u->realEmail())
                                            <a href="mailto:{{ $u->realEmail() }}" class="contact-line break-all"><x-icon name="mail" :size="14" /> {{ $u->realEmail() }}</a>
                                        @endif
                                    </div>

                                    <div class="mt-4 pt-1">
                                        <a href="{{ route('messages.show', $u) }}" wire:navigate class="btn btn-primary btn-sm w-full">
                                            <x-icon name="chat" :size="14" /> Message
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if($staff->isEmpty())
                <div class="card">
                    <x-empty title="No colleagues found" text="Other teachers and admins at your school will appear here." icon="users" />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>