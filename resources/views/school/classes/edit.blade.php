<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('school.classes.update', $class) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <p class="mb-4 text-sm text-gray-500">School: <span class="font-medium text-gray-700">{{ auth()->user()->school->name }}</span></p>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                            <input type="text" name="name" value="{{ old('name', $class->name) }}" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Grade *</label>
    <select name="grade" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">Select grade</option>
        @foreach(\App\Models\Subject::GRADES as $g)
            <option value="{{ $g }}" {{ old('grade', $class->grade ?? '') == $g ? 'selected' : '' }}>
                {{ $g === 'R' ? 'Grade R' : 'Grade ' . $g }}
            </option>
        @endforeach
    </select>
    @error('grade') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <input type="number" name="year" value="{{ old('year', $class->year) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('year') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Teacher</label>
                            <select name="class_teacher_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Class Teacher (optional)</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}"
                                        {{ old('class_teacher_id', $class->teachers->where('pivot.is_class_teacher', true)->first()?->id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_teacher_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $class->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Update Class</button>
                            <a href="{{ route('school.classes.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-2 rounded-md">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>