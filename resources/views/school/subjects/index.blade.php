<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subjects</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="mb-4 flex justify-end">
                <a href="{{ route('school.subjects.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add Subject
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grades</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Classes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($subjects as $subject)
                                <tr>
                                    <td class="px-4 py-3">{{ $subject->name }}</td>
                                    <td class="px-4 py-3">{{ $subject->code ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ implode(', ', $subject->gradeList()) ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $subject->classes_count }}</td>
                                    <td class="px-4 py-3">
                                        @if($subject->is_active)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('school.subjects.teachers.edit', $subject) }}" class="text-green-600 hover:underline">Teachers</a>
                                        <a href="{{ route('school.subjects.edit', $subject) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('school.subjects.destroy', $subject) }}" method="POST" class="inline" onsubmit="return confirm('Delete this subject? It is removed from all classes and learners.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No subjects yet. Add your first subject.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $subjects->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>