<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subjects for {{ $class->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    @if($usingGradeDefaults)
                        <div class="mb-4 p-3 bg-yellow-100 text-yellow-900 rounded text-sm">
                            This class has no subjects yet. The subjects for Grade {{ $class->grade }} are ticked below. Click <strong>Save</strong> to apply them to the class and its learners.
                        </div>
                    @endif

                    @if($subjects->isEmpty())
                        <p class="text-gray-600">No subjects yet. <a href="{{ route('school.subjects.create') }}" class="text-blue-600 hover:underline">Add subjects first</a>.</p>
                    @else
                        <form action="{{ route('school.classes.subjects.update', $class) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <table class="min-w-full divide-y divide-gray-200 mb-4">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taught</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($subjects as $subject)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                                       {{ in_array($subject->id, array_map('intval', old('subjects', $selected))) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-3">{{ $subject->name }}</td>
                                            <td class="px-4 py-3">
                                                <select name="teachers[{{ $subject->id }}]"
                                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">No teacher yet</option>
                                                    @foreach($teachers as $teacher)
                                                        <option value="{{ $teacher->id }}"
                                                            {{ old('teachers.' . $subject->id, $assigned->get($subject->id)) == $teacher->id ? 'selected' : '' }}>
                                                            {{ $teacher->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p class="text-xs text-gray-500 mb-4">Learners in this class are updated to match when you save.</p>

                            <div class="flex gap-3">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md">Save</button>
                                <a href="{{ route('school.classes.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-2 rounded-md">Cancel</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>