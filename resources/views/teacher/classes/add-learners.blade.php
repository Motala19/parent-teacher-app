<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Learners to {{ $class->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if(! $class->grade)
                    <div class="p-4 bg-yellow-100 text-yellow-900 rounded text-sm">
                        This class has no grade set, so no learners can be listed. Please ask your school admin to set the grade.
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-4">
                        Showing learners in <strong>{{ $class->grade === 'R' ? 'Grade R' : 'Grade ' . $class->grade }}</strong> who are not in a class yet.
                    </p>

                    <form method="GET" action="{{ route('teacher.classes.learners.create', $class) }}" class="mb-4 flex items-center gap-3">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or admission no."
                               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm">Search</button>
                        @if(request('search'))
                            <a href="{{ route('teacher.classes.learners.create', $class) }}" class="text-sm text-gray-600 hover:underline">Clear</a>
                        @endif
                    </form>

                    @error('student_ids') <p class="mb-3 text-red-600 text-sm">{{ $message }}</p> @enderror

                    @if($students->isEmpty())
                        <p class="text-sm text-gray-500">No available learners found.</p>
                    @else
                        <form action="{{ route('teacher.classes.learners.store', $class) }}" method="POST">
                            @csrf

                            <label class="flex items-center gap-2 px-3 py-2 text-sm font-medium bg-gray-50 border border-gray-200 rounded-t-md">
                                <input type="checkbox"
                                       onchange="document.querySelectorAll('.pick').forEach(c => c.checked = this.checked)"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                Select all ({{ $students->count() }})
                            </label>

                            <div class="max-h-96 overflow-y-auto border border-t-0 border-gray-200 rounded-b-md divide-y">
                                @foreach($students as $student)
                                    <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                               class="pick rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span>{{ $student->last_name }}, {{ $student->first_name }}</span>
                                        <span class="text-xs text-gray-500">{{ $student->admission_number }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="flex gap-3 mt-4">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Add Selected Learners</button>
                                <a href="{{ route('teacher.classes.show', $class) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-2 rounded-md">Cancel</a>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>