@php
    $hour     = now('Africa/Johannesburg')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $first    = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $unread   = auth()->user()->unreadMessageCount();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $greeting }}, {{ $first }}</h2>
        <p>Stay up to date with your children's school.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid sm:grid-cols-3 gap-4 stagger">
                <a href="{{ route('parent.announcements.index') }}" wire:navigate class="tile tile-rose">
                    <span class="tile-icon"><x-icon name="megaphone" /></span>
                    <div><div class="tile-title">Announcements</div><div class="tile-sub">Homework and class notices</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('parent.announcements.index', ['type' => 'newsletter']) }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="file" /></span>
                    <div><div class="tile-title">Newsletters</div><div class="tile-sub">Read or download</div></div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
                <a href="{{ route('messages.index') }}" wire:navigate class="tile">
                    <span class="tile-icon"><x-icon name="chat" /></span>
                    <div>
                        <div class="tile-title flex items-center gap-2">Messages @if($unread)<span class="badge-dot">{{ $unread }}</span>@endif</div>
                        <div class="tile-sub">Chat privately with teachers</div>
                    </div>
                    <x-icon name="arrow" :size="18" class="arrow" />
                </a>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">My children</h3>
                <div class="grid md:grid-cols-2 gap-5 stagger">
                    @forelse($children as $child)
                        @php($classTeacher = $child->classRoom?->teachers->where('pivot.is_class_teacher', true)->first())
                        <div class="card card-hover p-6">
                            <div class="flex items-center gap-4">
                                <x-avatar :name="$child->first_name . ' ' . $child->last_name" size="h-14 w-14 text-lg" />
                                <div class="min-w-0">
                                    <div class="text-lg font-bold text-ink">{{ $child->first_name }} {{ $child->last_name }}</div>
                                    <div class="text-sm text-ink-muted">
                                        {{ $child->classRoom->name ?? 'Not in a class yet' }}
                                        @if($child->grade) · Grade {{ $child->grade }} @endif
                                    </div>
                                </div>
                            </div>

                            @if($classTeacher)
                                <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-navy-50 px-4 py-3">
                                    <div class="text-sm">
                                        <div class="text-xs font-bold uppercase tracking-wider text-ink-muted">Class teacher</div>
                                        <div class="font-semibold text-ink">{{ $classTeacher->name }}</div>
                                    </div>
                                    @if(auth()->user()->canMessage($classTeacher))
                                        <a href="{{ route('messages.show', $classTeacher) }}" wire:navigate class="btn btn-primary btn-sm">
                                            <x-icon name="chat" :size="14" /> Message
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="card md:col-span-2">
                            <x-empty title="No children linked yet" text="Please contact your school so they can link your children to your account." icon="users" />
                        </div>
                    @endforelse
                </div>
            </div>

            @if($announcements->isNotEmpty())
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Latest announcements</h3>
                        <a href="{{ route('parent.announcements.index') }}" wire:navigate class="text-sm font-semibold text-navy-500 hover:text-brand-red transition">View all</a>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4 stagger">
                        @foreach($announcements as $a)
                            <div class="card note card-hover tone-{{ $a->type }} p-5">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="badge">{{ $a->typeLabel() }}</span>
                                    <span class="chip">{{ $a->classRoom->name ?? 'Whole school' }}</span>
                                </div>
                                <div class="font-bold text-ink">{{ $a->title }}</div>
                                <p class="mt-1 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit($a->body, 110) }}</p>
                                <x-attachment-list :attachments="$a->attachments" class="mt-3" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>