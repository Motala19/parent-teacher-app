<x-app-layout>
    <x-slot name="header">
        <h2>Post Announcement</h2>
        <p>Share homework, assignments, question papers and reminders with a class.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6 sm:p-8">
                    @if($classes->isEmpty())
                        <x-empty title="No classes yet" text="You aren't linked to a class. Please ask your school admin." icon="layers" />
                    @else
                        <form action="{{ route('teacher.announcements.store') }}" method="POST" enctype="multipart/form-data"
                              x-data="{ sending: false }" x-on:submit="sending = true">
                            @csrf
                            @include('announcements._form', ['allowSchoolWide' => false, 'lockClass' => false])

                            <div class="flex gap-3 mt-8">
                                <button type="submit" class="btn btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-wait': sending }">
                                    <x-icon name="send" :size="16" /> <span x-text="sending ? 'Posting...' : 'Post'"></span>
                                </button>
                                <a href="{{ route('teacher.announcements.index') }}" wire:navigate class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>