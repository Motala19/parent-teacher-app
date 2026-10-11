<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add New Class
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.classes.store') }}" method="POST"
      x-data="{
          schoolId: '{{ old('school_id') }}',
          selected: '{{ old('class_teacher_id') }}',
          teachers: @js($teachers->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'school_id' => $t->school_id]))
      }">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">School *</label>
                            <select name="school_id" required x-model="schoolId" @change="selected = ''"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   placeholder="e.g. Grade 7A"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                            <input type="text" name="grade" value="{{ old('grade') }}"
                                   placeholder="e.g. 7"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('grade') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <input type="number" name="year" value="{{ old('year', date('Y')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('year') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Class Teacher</label>
    <select name="class_teacher_id" x-model="selected" :disabled="!schoolId"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="" x-text="schoolId ? 'Select Class Teacher (optional)' : 'Select a school first'"></option>
        <template x-for="t in teachers.filter(t => t.school_id == schoolId)" :key="t.id">
            <option :value="t.id" x-text="t.name"></option>
        </template>
    </select>
    @error('class_teacher_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">
                                Create Class
                            </button>
                            <a href="{{ route('admin.classes.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-2 rounded-md">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>