<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Parents (All Schools)</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('admin.parents.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email or phone"
                       class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="school_id" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Schools</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm">Search</button>
                @if(request()->hasAny(['search', 'school_id']))
                    <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-600 hover:underline">Clear</a>
                @endif
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">School</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Children</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3">{{ $parent->name }}</td>
                                    <td class="px-4 py-3">{{ $parent->email }}</td>
                                    <td class="px-4 py-3">{{ $parent->phone ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $parent->school->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @foreach($parent->children as $child)
                                            <div>{{ $child->first_name }} {{ $child->last_name }}
                                                <span class="text-xs text-gray-500">{{ $child->classRoom->name ?? '' }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No parents found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $parents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>