<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Learners</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('school.students.index') }}" class="flex flex-wrap items-center gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or admission no."
                           class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="grade" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Grades</option>
                        @foreach(\App\Models\Subject::GRADES as $g)
                            <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>{{ $g === 'R' ? 'Grade R' : 'Grade ' . $g }}</option>
                        @endforeach
                    </select>
                    <select name="class_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Classes</option>
                        <option value="none" {{ request('class_id') === 'none' ? 'selected' : '' }}>No class yet</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm">Search</button>
                    @if(request()->hasAny(['search', 'grade', 'class_id']))
                        <a href="{{ route('school.students.index') }}" class="text-sm text-gray-600 hover:underline">Clear</a>
                    @endif
                </form>
                <a href="{{ route('school.import.index') }}" class="btn btn-ghost btn-sm"><x-icon name="upload" :size="14" /> Import from Excel</a>
                <a href="{{ route('school.students.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + Add Learner
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parents</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($students as $student)
                                <tr>
                                    <td class="px-4 py-3">{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td class="px-4 py-3">{{ $student->admission_number ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $student->grade ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $student->classRoom->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
    @forelse($student->parents as $p)
        <div class="py-1 text-sm">
            <span class="font-semibold text-ink">{{ $p->name }}</span>
            <span class="text-xs text-ink-muted">({{ ucfirst($p->pivot->relationship ?? 'parent') }})</span>
            <div class="text-xs text-ink-muted">
                {{ $p->phone ?? 'no phone' }} · {{ $p->realEmail() ?? 'no email' }}
                <a href="{{ route('messages.show', $p) }}" wire:navigate class="ms-1 font-semibold text-navy-500 hover:text-brand-red">Message</a>
            </div>
        </div>
    @empty
        <span class="text-sm text-gray-400">None linked</span>
    @endforelse
</td>
                                    <td class="px-4 py-3">
                                        @if($student->is_active)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('school.students.edit', $student) }}" class="text-blue-600 hover:underline">Edit</a>
                                        <form action="{{ route('school.students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Delete this learner?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No learners found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $students->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>