<x-app-layout>
    <x-slot name="header">
        <h2>Learners &amp; Parents</h2>
        <p>Every learner's parents with phone, email and a one-click message button.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            <form method="GET" action="{{ route('directory.learners') }}" class="flex flex-wrap items-center gap-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search learner, parent, phone or email"
                       class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm">
                <select name="class_id" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm">
                    <option value="">All classes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-ghost btn-sm">Search</button>
                @if(request()->hasAny(['search', 'class_id']))
                    <a href="{{ route('directory.learners') }}" wire:navigate class="text-sm text-ink-muted hover:underline">Clear</a>
                @endif
            </form>

            @forelse($students as $student)
                <article class="card card-hover p-5">
                    <div class="flex flex-wrap items-start gap-5">

                        <div class="flex items-center gap-3 min-w-[14rem]">
                            <x-avatar :name="$student->first_name . ' ' . $student->last_name" size="h-12 w-12 text-base" />
                            <div>
                                <div class="font-bold text-ink">{{ $student->last_name }}, {{ $student->first_name }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="chip">{{ $student->classRoom->name ?? 'No class' }}</span>
                                    @if($student->admission_number)
                                        <span class="text-xs text-ink-muted">#{{ $student->admission_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid flex-1 gap-3 sm:grid-cols-2 min-w-[16rem]">
                            @forelse($student->parents as $p)
                                <div class="rounded-xl border border-navy-100 bg-navy-50 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-ink">{{ $p->name }}</div>
                                            <div class="text-xs text-ink-muted">{{ ucfirst($p->pivot->relationship ?? 'parent') }}</div>
                                        </div>
                                        <a href="{{ route('messages.show', $p) }}" wire:navigate class="btn btn-primary btn-sm shrink-0">
                                            <x-icon name="chat" :size="14" /> Message
                                        </a>
                                    </div>

                                    <div class="mt-3 space-y-1.5 text-sm">
                                        @if($p->phone)
                                            <a href="tel:{{ $p->phone }}" class="contact-line"><x-icon name="phone" :size="14" /> {{ $p->phone }}</a>
                                        @else
                                            <div class="contact-line text-ink-muted"><x-icon name="phone" :size="14" /> No phone</div>
                                        @endif

                                        @if($p->realEmail())
                                            <a href="mailto:{{ $p->realEmail() }}" class="contact-line break-all"><x-icon name="mail" :size="14" /> {{ $p->realEmail() }}</a>
                                        @else
                                            <div class="contact-line text-ink-muted"><x-icon name="mail" :size="14" /> No email</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-navy-200 p-3 text-sm text-ink-muted">
                                    No parent linked yet. Ask the school admin to link one.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </article>
            @empty
                <div class="card">
                    <x-empty title="No learners found" text="Learners from your classes will appear here with their parents' details." icon="users" />
                </div>
            @endforelse

            <div>{{ $students->links() }}</div>
        </div>
    </div>
</x-app-layout>