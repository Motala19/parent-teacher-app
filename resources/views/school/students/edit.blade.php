<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Learner</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('school.students.update', $student) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('school.students._form')

                        <div class="mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $student->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Update Learner</button>
                            <a href="{{ route('school.students.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-2 rounded-md">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Subjects panel --}}
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Subjects</h3>
                    <p class="text-xs text-gray-500 mb-4">Assigned from the class (or grade). Tick or untick to adjust, for example electives. Changing the class resets them to the new class's subjects.</p>

                    @if($allSubjects->isEmpty())
                        <p class="text-sm text-gray-600">No subjects yet. <a href="{{ route('school.subjects.create') }}" class="text-blue-600 hover:underline">Add subjects</a>.</p>
                    @else
                        @php($mine = $student->subjects->pluck('id')->all())
                        <form action="{{ route('school.students.subjects.update', $student) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                                @foreach($allSubjects as $subject)
                                    <label class="flex items-center text-sm">
                                        <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                               {{ in_array($subject->id, $mine) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span class="ms-2">{{ $subject->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Save Subjects</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Parents panel --}}
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Parents / Guardians</h3>

                    @if(session('temp_password'))
                        <div class="mb-4 p-4 bg-yellow-100 text-yellow-900 rounded text-sm">
                            <strong>Login details (shown once, copy them now):</strong><br>
                            {{ session('temp_password') }}
                        </div>
                    @endif

                    @forelse($student->parents as $parent)
                        <div class="flex items-center justify-between border-b py-2">
                            <div>
                                <div class="font-medium">{{ $parent->name }}
                                    <span class="text-xs text-gray-500">({{ ucfirst($parent->pivot->relationship ?? 'parent') }})</span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ str_ends_with($parent->email, '@noemail.invalid') ? 'no email' : $parent->email }} · {{ $parent->phone ?? 'no phone' }}
                                </div>
                            </div>
                            <form action="{{ route('school.students.parents.destroy', [$student, $parent]) }}" method="POST"
                                  onsubmit="return confirm('Unlink this parent?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline text-sm">Unlink</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 mb-2">No parents linked yet.</p>
                    @endforelse

                    <form action="{{ route('school.students.parents.store', $student) }}" method="POST" class="mt-6">
                        @csrf
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Link a parent</h4>
                        <p class="text-xs text-gray-500 mb-3">Email or phone is required. If a parent with the same email or phone exists at your school, they are linked. Otherwise a new account is created.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Relationship *</label>
                                <select name="relationship" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach(['mother' => 'Mother', 'father' => 'Father', 'guardian' => 'Guardian', 'other' => 'Other'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('relationship') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="082 123 4567"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Link Parent</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>