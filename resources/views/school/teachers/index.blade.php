<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teachers</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="mb-4 flex justify-end">
                <a href="{{ route('school.teachers.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add Teacher
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Classes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subjects</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($teachers as $teacher)
                                <tr>
                                    <td class="px-4 py-3">{{ $teacher->name }}</td>
                                    <td class="px-4 py-3">{{ $teacher->email }}</td>
                                    <td class="px-4 py-3">{{ $teacher->phone ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $teacher->classes->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $teacher->teachingSubjects->pluck('name')->unique()->join(', ') ?: '-' }}</td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('school.teachers.edit', $teacher) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('school.teachers.destroy', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('Delete this teacher?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No teachers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $teachers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>