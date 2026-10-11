<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">School Admins</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="mb-4 flex items-center justify-between">
                <form method="GET" action="{{ route('admin.school-admins.index') }}" class="flex items-center gap-3">
                    <select name="school_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Schools</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    @if(request('school_id'))
                        <a href="{{ route('admin.school-admins.index') }}" class="text-sm text-gray-600 hover:underline">Clear filter</a>
                    @endif
                </form>

                <a href="{{ route('admin.school-admins.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add School Admin
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">School</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($admins as $admin)
                                <tr>
                                    <td class="px-4 py-3">{{ $admin->name }}</td>
                                    <td class="px-4 py-3">{{ $admin->email }}</td>
                                    <td class="px-4 py-3">{{ $admin->phone ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $admin->school->name ?? '-' }}</td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('admin.school-admins.edit', $admin) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('admin.school-admins.destroy', $admin) }}" method="POST" class="inline" onsubmit="return confirm('Delete this school admin?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No school admins found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $admins->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>