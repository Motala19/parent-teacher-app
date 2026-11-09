<x-app-layout>
    <x-slot name="header">
        <h2>Post announcement</h2>
        <p>Newsletters, timetables, stationery lists, year plans, notices and class work. Choose the type below.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6 sm:p-8">
                    <form action="{{ route('school.announcements.store') }}" method="POST" enctype="multipart/form-data"
                          x-data="{ sending: false }" x-on:submit="sending = true">
                        @csrf
                        @include('announcements._form')

                        <div class="flex gap-3 mt-8">
                            <button type="submit" class="btn btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-wait': sending }">
                                <x-icon name="send" :size="16" /> <span x-text="sending ? 'Posting...' : 'Post'"></span>
                            </button>
                            <a href="{{ route('school.announcements.index') }}" wire:navigate class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>