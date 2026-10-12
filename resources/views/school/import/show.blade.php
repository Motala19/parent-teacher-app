@php
    $c = $counts ?? [];

    $learnerTone = [
        'create' => ['New learner', 'tone-new'],
        'update' => ['Updates existing', 'tone-update'],
        'merge'  => ['Same learner as above', 'tone-same'],
        'error'  => ['Skipped', 'tone-skip'],
    ];

    $parentTone = [
        'new'      => ['New account', 'tone-new'],
        'existing' => ['Existing account', 'tone-update'],
        'again'    => ['Same parent as above', 'tone-same'],
        'skipped'  => ['Skipped', 'tone-skip'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $import->original_name }}</h2>
        <p>{{ $import->statusLabel() }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            {{-- ================= FAILED ================= --}}
            @if($mode === 'failed')
                <div class="card note tone-urgent p-6">
                    <h3 class="font-bold text-ink">The import did not finish</h3>
                    <p class="mt-1 text-ink-soft">{{ $import->error ?? 'Something went wrong.' }}</p>
                    <p class="mt-1 text-sm text-ink-muted">Nothing was saved, so it is safe to try again.</p>
                    <a href="{{ route('school.import.index') }}" wire:navigate class="btn btn-primary mt-4">Upload again</a>
                </div>
            @endif

            {{-- ================= RUNNING ================= --}}
            @if($mode === 'running')
                <div class="card p-6">
                    <h3 class="font-bold text-ink">Import in progress</h3>
                    <p class="mt-1 text-ink-soft">This import is running. Refresh in a moment to see the result.</p>
                    <a href="{{ route('school.import.show', $import) }}" class="btn btn-navy mt-4">Refresh</a>
                </div>
            @endif

            {{-- ================= PREVIEW ================= --}}
            @if($mode === 'previewed')
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                    <x-stat-card label="New learners" :value="$c['learners_created']" icon="user-plus" />
                    <x-stat-card label="Existing learners updated" :value="$c['learners_updated']" icon="users" />
                    <x-stat-card label="New parent accounts" :value="$c['parents_created']" icon="user" tone="rose" />
                    <x-stat-card label="Parent links to create" :value="$c['links_created']" icon="layers" tone="rose" />
                </div>

                <div class="card p-6 text-sm text-ink-soft space-y-2">
                    <p>
                        <strong class="text-ink">{{ $c['rows_ok'] }}</strong> of {{ $c['rows_total'] }} rows are ready to import.
                        @if($c['rows_error'])
                            <strong class="text-red-600">{{ $c['rows_error'] }}</strong> {{ \Illuminate\Support\Str::plural('row', $c['rows_error']) }} have errors and will be skipped.
                        @endif
                    </p>

                    @if($c['parents_matched'])
                        <p><strong class="text-ink">{{ $c['parents_matched'] }}</strong> {{ \Illuminate\Support\Str::plural('parent', $c['parents_matched']) }} already in the system matched by email or phone and will be reused, not duplicated.</p>
                    @endif

                    @if($c['links_existing'])
                        <p><strong class="text-ink">{{ $c['links_existing'] }}</strong> parent-learner {{ \Illuminate\Support\Str::plural('link', $c['links_existing']) }} already exist and will be left as they are.</p>
                    @endif

                    @if($c['parents_no_email'])
                        <p><strong class="text-ink">{{ $c['parents_no_email'] }}</strong> new {{ \Illuminate\Support\Str::plural('parent', $c['parents_no_email']) }} have a phone number but no email. Their accounts are created, but they can't log in until you add an email.</p>
                    @endif

                    @if($c['parents_skipped'])
                        <p class="text-yellow-800"><strong>{{ $c['parents_skipped'] }}</strong> {{ \Illuminate\Support\Str::plural('parent', $c['parents_skipped']) }} will be skipped (no valid email or phone, or the contact belongs to another school).</p>
                    @endif

                    @if($c['warnings'])
                        <p class="text-yellow-800"><strong>{{ $c['warnings'] }}</strong> {{ \Illuminate\Support\Str::plural('warning', $c['warnings']) }}. See the problems column below.</p>
                    @endif

                    @if(! empty($ignored))
                        <p class="text-ink-muted">
                            Columns that were not recognised and will be ignored:
                            @foreach($ignored as $name)<span class="chip">{{ $name }}</span>@endforeach
                        </p>
                    @endif
                </div>

                <div class="card p-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-ink-soft">
                        @if($c['rows_ok'] > 0)
                            <strong class="text-ink">Nothing has been saved yet.</strong> Review the list below, then confirm. The import is all or nothing: if anything goes wrong, nothing is saved.
                        @else
                            There is nothing to import. Fix the problems in your file and upload it again.
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($c['warnings'] || $c['rows_error'])
                            <a href="{{ route('school.import.issues', $import) }}" class="btn btn-ghost">
                                <x-icon name="download" :size="16" /> Download problems
                            </a>
                        @endif

                        <form action="{{ route('school.import.destroy', $import) }}" method="POST"
                              onsubmit="return confirm('Cancel this import and delete the uploaded file?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost">Cancel</button>
                        </form>

                        @if($c['rows_ok'] > 0)
                            <form action="{{ route('school.import.confirm', $import) }}" method="POST"
                                  x-data="{ busy: false }" x-on:submit="busy = true">
                                @csrf
                                <button type="submit" class="btn btn-primary" :disabled="busy" :class="{ 'opacity-60 cursor-wait': busy }">
                                    <x-icon name="check" :size="16" /> <span x-text="busy ? 'Importing...' : 'Confirm and import'"></span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card overflow-hidden">
                    <div class="panel-head">
                        <h3>Preview</h3>
                        <div class="flex gap-2">
                            <a href="{{ route('school.import.show', $import) }}" class="pill {{ $onlyIssues ? '' : 'pill-active' }}">All rows</a>
                            <a href="{{ route('school.import.show', [$import, 'issues' => 1]) }}" class="pill {{ $onlyIssues ? 'pill-active' : '' }}">Only problems</a>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-2">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Row</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Learner</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Parents</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Problems</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($rows as $r)
                                    @php
                                        $lt = $learnerTone[$r['learner_action']];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 align-top text-sm text-ink-muted">{{ $r['line'] }}</td>

                                        <td class="px-4 py-3 align-top">
                                            <div class="font-semibold text-ink">{{ $r['learner'] ?: 'No name' }}</div>
                                            <div class="text-xs text-ink-muted">#{{ $r['admission'] ?: 'no number' }}</div>
                                            <span class="badge {{ $lt[1] }} mt-1">{{ $lt[0] }}</span>
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            @forelse($r['parents'] as $p)
                                                @php
                                                    $pt = $parentTone[$p['action']];
                                                @endphp
                                                <div class="py-1 text-sm">
                                                    <span class="font-semibold text-ink">{{ $p['name'] }}</span>
                                                    <span class="text-xs text-ink-muted">({{ ucfirst($p['relationship']) }})</span>
                                                    <span class="badge {{ $pt[1] }}">{{ $pt[0] }}</span>
                                                    <div class="text-xs text-ink-muted">
                                                        {{ $p['contact'] ?: 'no contact details' }}
                                                        @if($p['link'] === 'exists') · already linked @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <span class="text-sm text-gray-400">No parent in this row</span>
                                            @endforelse
                                        </td>

                                        <td class="px-4 py-3 align-top text-sm">
                                            @foreach($r['errors'] as $message)
                                                <div class="text-red-600">{{ $message }}</div>
                                            @endforeach
                                            @foreach($r['warnings'] as $message)
                                                <div class="text-yellow-800">{{ $message }}</div>
                                            @endforeach
                                            @if(! $r['errors'] && ! $r['warnings'])
                                                <span class="text-gray-400">None</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No rows to show.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-navy-100 px-6 py-3 text-xs text-ink-muted">
                        Showing the first {{ $shown }} of {{ $totalShown }} {{ $onlyIssues ? 'rows with problems' : 'rows' }}.
                        Confirming imports every valid row, not only the ones shown here.
                    </div>
                </div>
            @endif

            {{-- ================= COMPLETED ================= --}}
            @if($mode === 'completed')
                <div class="card note tone-project p-6">
                    <h3 class="font-bold text-ink">Import complete</h3>
                    <p class="mt-1 text-ink-soft">
                        {{ $c['learners_created'] ?? 0 }} new learners, {{ $c['learners_updated'] ?? 0 }} updated,
                        {{ $c['parents_created'] ?? 0 }} new parent accounts and {{ $c['links_created'] ?? 0 }} parent links created.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('school.students.index', ['class_id' => 'none']) }}" class="btn btn-navy btn-sm">Learners without a class</a>
                        <a href="{{ route('school.parents.index') }}" class="btn btn-ghost btn-sm">View parents</a>
                        <a href="{{ route('school.import.index') }}" class="btn btn-ghost btn-sm">Import another file</a>
                    </div>
                    <p class="mt-3 text-xs text-ink-muted">The uploaded file has been deleted from the server. Next, class teachers add these learners to their classes.</p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger">
                    <x-stat-card label="New learners" :value="$c['learners_created'] ?? 0" icon="user-plus" />
                    <x-stat-card label="Learners updated" :value="$c['learners_updated'] ?? 0" icon="users" />
                    <x-stat-card label="New parent accounts" :value="$c['parents_created'] ?? 0" icon="user" tone="rose" />
                    <x-stat-card label="Existing parents reused" :value="$c['parents_matched'] ?? 0" icon="layers" tone="rose" />
                </div>

                <div class="card p-6">
                    <h3 class="font-bold text-ink">Invite parents</h3>
                    <p class="mt-1 text-sm text-ink-muted">New parent accounts have no password yet. Email each parent a link to choose their own, then they can log in.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
                        <div class="rounded-xl bg-navy-50 p-3"><div class="text-2xl font-extrabold text-navy-800">{{ $inviteable }}</div>waiting to be invited</div>
                        <div class="rounded-xl bg-navy-50 p-3"><div class="text-2xl font-extrabold text-navy-800">{{ $invited }}</div>already invited</div>
                        <div class="rounded-xl bg-navy-50 p-3"><div class="text-2xl font-extrabold text-navy-800">{{ $noEmail }}</div>phone only (no email)</div>
                    </div>

                    @if($inviteable > 0)
                        <form action="{{ route('school.import.invite', $import) }}" method="POST" class="mt-4"
                              x-data="{ busy: false }" x-on:submit="busy = true"
                              onsubmit="return confirm('Email a set-your-password link to {{ $inviteable }} parents?')">
                            @csrf
                            <button type="submit" class="btn btn-primary" :disabled="busy" :class="{ 'opacity-60 cursor-wait': busy }">
                                <x-icon name="send" :size="16" /> <span x-text="busy ? 'Sending...' : 'Send {{ $inviteable }} invitations'"></span>
                            </button>
                        </form>
                    @endif

                    @if($noEmail > 0)
                        <p class="mt-3 text-xs text-ink-muted">Parents with only a phone number can't log in yet. Open Parents, edit them and add an email, then use the Invite link next to their name.</p>
                    @endif
                </div>

                @if(! empty($issues))
                    <div class="card overflow-hidden">
                        <div class="panel-head">
                            <h3>Things to check ({{ count($issues) }})</h3>
                            <a href="{{ route('school.import.issues', $import) }}" class="btn btn-ghost btn-sm"><x-icon name="download" :size="14" /> Download</a>
                        </div>

                        <div class="overflow-x-auto p-2">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Row</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Learner</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Message</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach(array_slice($issues, 0, 50) as $i)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $i['line'] }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $i['learner'] ?: '-' }} <span class="text-xs text-ink-muted">#{{ $i['admission'] }}</span></td>
                                            <td class="px-4 py-3"><span class="badge {{ $i['level'] === 'Error' ? 'tone-skip' : 'tone-warn' }}">{{ $i['level'] }}</span></td>
                                            <td class="px-4 py-3 text-sm text-ink-soft">{{ $i['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if(count($issues) > 50)
                            <div class="border-t border-navy-100 px-6 py-3 text-xs text-ink-muted">Showing the first 50. Download the file to see all {{ count($issues) }}.</div>
                        @endif
                    </div>
                @endif
            @endif

            <a href="{{ route('school.import.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold text-navy-500 hover:text-brand-red transition">
                <x-icon name="arrow" :size="16" class="rotate-180" /> Back to imports
            </a>
        </div>
    </div>
</x-app-layout>