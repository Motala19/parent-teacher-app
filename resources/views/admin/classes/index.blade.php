<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Classes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form method="GET" action="{{ route('admin.classes.index') }}" class="mb-4 flex items-center gap-3">
    <select name="school_id" onchange="this.form.submit()"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">All Schools</option>
        @foreach($schools as $school)
            <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                {{ $school->name }}
            </option>
        @endforeach
    </select>
    @if(request('school_id'))
        <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">Clear filter</a>
    @endif
</form>

            <div class="mb-4 flex justify-end">
                <a href="{{ route('admin.classes.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add Class
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">School</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($classes as $class)
                                <tr>
                                    <td class="px-4 py-3">{{ $class->school->name ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $class->name }}</td>
                                    <td class="px-4 py-3">{{ $class->teachers->where('pivot.is_class_teacher', true)->first()?->name ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $class->grade ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $class->year ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if($class->is_active)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('admin.classes.edit', $class) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Delete this class?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No classes found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $classes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>