<p class="mb-4 text-sm text-gray-500">School: <span class="font-medium text-gray-700">{{ auth()->user()->school->name }}</span></p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Subject Name *</label>
        <input type="text" name="name" value="{{ old('name', $subject->name ?? '') }}" required placeholder="e.g. Mathematics"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
        <input type="text" name="code" value="{{ old('code', $subject->code ?? '') }}" placeholder="e.g. MATH"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('code') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Grades that take this subject</label>
    <div class="flex flex-wrap gap-3">
        @foreach(\App\Models\Subject::GRADES as $grade)
            <label class="flex items-center text-sm">
                <input type="checkbox" name="grades[]" value="{{ $grade }}"
                       {{ in_array($grade, old('grades', $selectedGrades ?? [])) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="ms-1">{{ $grade === 'R' ? 'Grade R' : 'Gr ' . $grade }}</span>
            </label>
        @endforeach
    </div>
    <p class="text-xs text-gray-500 mt-2">New classes in these grades get this subject automatically. A class's Grade field must match, for example "7".</p>
    @error('grades.*') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>