<x-app-layout>
    <x-slot name="header">
        <h2>Edit Announcement</h2>
        <p>Update the details or change the attached files.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6 sm:p-8">
                    <form action="{{ route('school.announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data"
                          x-data="{ sending: false }" x-on:submit="sending = true">
                        @csrf
                        @method('PUT')
                        @include('announcements._form')

                        <div class="flex gap-3 mt-8">
                            <button type="submit" class="btn btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-wait': sending }">
                                <x-icon name="check" :size="16" /> <span x-text="sending ? 'Saving...' : 'Save changes'"></span>
                            </button>
                            <a href="{{ route('school.announcements.index') }}" wire:navigate class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>