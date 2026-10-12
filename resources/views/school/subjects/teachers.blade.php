<x-app-layout>
    <x-slot name="header">
        <h2>{{ $subject->name }}: teachers</h2>
        <p>Tick the classes that take this subject and choose who teaches it in each class.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6 sm:p-8">

                    @if($classes->isEmpty())
                        <x-empty title="No classes to show"
                                 text="Tick the grades for this subject first (Subjects, then Edit) and create classes for those grades."
                                 icon="layers" />
                    @else
                        <form action="{{ route('school.subjects.teachers.update', $subject) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Takes it</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Class</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Learners</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Subject teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($classes as $class)
                                            @php
                                                $has = $current->has($class->id);
                                            @endphp
                                            <tr x-data="{ on: {{ $has ? 'true' : 'false' }} }">
                                                <td class="px-4 py-3">
                                                    <input type="checkbox" name="classes[]" value="{{ $class->id }}" x-model="on"
                                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="font-semibold text-ink">{{ $class->name }}</div>
                                                    <div class="text-xs text-ink-muted">
                                                        {{ $class->grade ? ($class->grade === 'R' ? 'Grade R' : 'Grade ' . $class->grade) : 'No grade' }}
                                                        @unless($class->is_active) · inactive @endunless
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">{{ $counts[$class->id] ?? 0 }}</td>
                                                <td class="px-4 py-3">
                                                    <select name="teachers[{{ $class->id }}]" :disabled="! on"
                                                            class="w-full rounded-md border-gray-300 shadow-sm disabled:opacity-50">
                                                        <option value="">No teacher yet</option>
                                                        @foreach($teachers as $teacher)
                                                            <option value="{{ $teacher->id }}"
                                                                {{ (string) old('teachers.' . $class->id, $current->get($class->id)) === (string) $teacher->id ? 'selected' : '' }}>
                                                                {{ $teacher->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-4 text-xs text-ink-muted">
                                Ticking a class adds {{ $subject->name }} to its learners. Unticking removes it from them.
                                A teacher assigned here becomes a subject teacher for that class and sees only the learners taking {{ $subject->name }}.
                            </p>

                            @error('classes.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('teachers.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                            <div class="flex gap-3 mt-6">
                                <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Save</button>
                                <a href="{{ route('school.subjects.index') }}" wire:navigate class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>