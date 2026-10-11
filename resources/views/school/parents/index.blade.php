<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Parents</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            @if(session('temp_password'))
                <div class="mb-4 p-4 bg-yellow-100 text-yellow-900 rounded text-sm">
                    <strong>Login details (shown once, copy them now):</strong><br>
                    {{ session('temp_password') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('school.parents.index') }}" class="flex items-center gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email or phone"
                           class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm">Search</button>
                    @if(request('search'))
                        <a href="{{ route('school.parents.index') }}" class="text-sm text-gray-600 hover:underline">Clear</a>
                    @endif
                </form>

                <a href="{{ route('school.parents.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add Parent
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Children</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3">{{ $parent->name }}</td>
                                    <td class="px-4 py-3">{{ str_ends_with($parent->email, '@noemail.invalid') ? '-' : $parent->email }}</td>
                                    <td class="px-4 py-3">{{ $parent->phone ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @forelse($parent->children as $child)
                                            <div>
                                                <a href="{{ route('school.students.edit', $child) }}" class="text-blue-600 hover:underline">
                                                    {{ $child->first_name }} {{ $child->last_name }}
                                                </a>
                                                <span class="text-xs text-gray-500">{{ $child->classRoom->name ?? '' }}</span>
                                            </div>
                                        @empty
                                            <span class="text-gray-400">None linked</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('school.parents.edit', $parent) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('school.parents.destroy', $parent) }}" method="POST" class="inline" onsubmit="return confirm('Delete this parent account?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No parents yet. Click + Add Parent.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $parents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>