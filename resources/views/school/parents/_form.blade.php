@php($editing = isset($parent))

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
        <input type="text" name="name" value="{{ old('name', $parent->name ?? '') }}" required
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship to new learners *</label>
        <select name="relationship" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach(['mother' => 'Mother', 'father' => 'Father', 'guardian' => 'Guardian', 'other' => 'Other'] as $v => $l)
                <option value="{{ $v }}" {{ old('relationship') == $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email"
               value="{{ old('email', isset($parent) && ! str_ends_with($parent->email, '@noemail.invalid') ? $parent->email : '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $parent->phone ?? '') }}" placeholder="082 123 4567"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
</div>
<p class="text-xs text-gray-500 mb-4">Email or phone is required. If a parent with the same email or phone already exists at your school, they are used instead of creating a duplicate.</p>

<div class="mb-4" x-data="{ q: '' }">
    <label class="block text-sm font-medium text-gray-700 mb-1">Link learners</label>
    <input type="text" x-model="q" placeholder="Search learner name or admission no."
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">

    <div class="mt-2 max-h-64 overflow-y-auto border border-gray-200 rounded-md divide-y">
        @forelse($students as $student)
            <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"
                   data-name="{{ strtolower($student->first_name . ' ' . $student->last_name . ' ' . $student->admission_number) }}"
                   x-show="$el.dataset.name.includes(q.toLowerCase())">
                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                       {{ in_array($student->id, array_map('intval', old('student_ids', $linkedIds ?? []))) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span>{{ $student->last_name }}, {{ $student->first_name }}</span>
                <span class="text-xs text-gray-500">{{ $student->classRoom->name ?? 'No class' }}{{ $student->admission_number ? ' · ' . $student->admission_number : '' }}</span>
            </label>
        @empty
            <p class="px-3 py-2 text-sm text-gray-500">No learners yet. Add learners first.</p>
        @endforelse
    </div>
    @error('student_ids.*') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>