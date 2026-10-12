@php($supported = \App\Support\ImportReader::spreadsheetsSupported())

<x-app-layout>
    <x-slot name="header">
        <h2>Import learners &amp; parents</h2>
        <p>Upload an Excel or CSV file. Learners are created, parents are matched by phone or email and linked for you.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            @if(! $supported)
                <div class="bg-yellow-100 text-yellow-900 p-4 text-sm">
                    Excel files need one extra package. Run <code>composer require phpoffice/phpspreadsheet:^1.29</code>, or save your file as CSV and upload that.
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">

                    <div class="card">
                        <div class="p-6 sm:p-8">
                            <h3 class="font-bold text-ink mb-4">1. Upload your file</h3>

                            <form action="{{ route('school.import.store') }}" method="POST" enctype="multipart/form-data"
                                  x-data="{ file: null, over: false, sending: false, tooBig: false,
                                            pick(list) { this.file = list[0] || null; this.tooBig = this.file ? this.file.size > 5 * 1048576 : false } }"
                                  x-on:submit="sending = true">
                                @csrf

                                <label class="drop" :class="{ 'is-over': over }"
                                       x-on:dragover.prevent="over = true"
                                       x-on:dragleave.prevent="over = false"
                                       x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; pick($refs.input.files)">
                                    <span class="drop-icon"><x-icon name="upload" :size="20" /></span>
                                    <span class="text-sm font-semibold text-ink" x-text="file ? file.name : 'Click to choose your Excel or CSV file, or drag it here'"></span>
                                    <span class="text-xs text-ink-muted">.xlsx, .xls or .csv, up to 5 MB and 3 000 learners</span>
                                    <input type="file" name="file" class="sr-only" x-ref="input" accept=".xlsx,.xls,.csv,.txt"
                                           x-on:change="pick($event.target.files)">
                                </label>

                                <p class="mt-2 text-sm text-red-600" x-show="tooBig" style="display:none">This file is larger than 5 MB.</p>
                                @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-5 flex gap-3">
                                    <button type="submit" class="btn btn-primary" :disabled="! file || tooBig || sending" :class="{ 'opacity-60 cursor-not-allowed': ! file || tooBig || sending }">
                                        <x-icon name="check" :size="16" /> <span x-text="sending ? 'Checking...' : 'Upload and preview'"></span>
                                    </button>
                                </div>
                                <p class="mt-3 text-xs text-ink-muted">Nothing is saved yet. You'll see exactly what will happen first.</p>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="p-6 sm:p-8 text-sm text-ink-soft space-y-3">
                            <h3 class="font-bold text-ink">How it works</h3>
                            <p><strong>Learners</strong> are matched by <strong>admission number</strong>. A new number creates a learner. A number that already exists updates that learner. Nobody is ever duplicated or deleted.</p>
                            <p><strong>Parents</strong> are matched by <strong>email, then phone</strong>. A parent who is already in the system (including a sibling's parent) is reused and linked to the new learner, so one parent can have several children.</p>
                            <p><strong>Phone numbers</strong> in any format work (082 123 4567, 0821234567, +27821234567). Excel often drops the leading zero, and that's handled.</p>
                            <p><strong>Classes</strong> are not imported. After the import, class teachers add the learners to their classes.</p>
                            <p><strong>Logins:</strong> new parent accounts have no password yet. After the import you can email every parent a link to set their own. Parents with only a phone number can be invited once you add an email.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card">
                        <div class="p-6">
                            <h3 class="font-bold text-ink mb-2">Template</h3>
                            <p class="text-sm text-ink-muted mb-4">Opens in Excel. Delete the three example rows and add your learners. Keep the headings in the first row.</p>
                            <a href="{{ route('school.import.template') }}" class="btn btn-navy w-full"><x-icon name="download" :size="16" /> Download template</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="p-6 text-sm">
                            <h3 class="font-bold text-ink mb-3">Columns</h3>
                            <dl class="space-y-2">
                                <div><dt class="font-semibold text-ink">admission_number <span class="text-brand-red">*</span></dt><dd class="text-ink-muted">Used to match learners. Format the column as Text in Excel if numbers start with zeros.</dd></div>
                                <div><dt class="font-semibold text-ink">learner_first_name <span class="text-brand-red">*</span></dt></div>
                                <div><dt class="font-semibold text-ink">learner_last_name <span class="text-brand-red">*</span></dt></div>
                                <div><dt class="font-semibold text-ink">gender</dt><dd class="text-ink-muted">Male, Female (M or F also fine)</dd></div>
                                <div><dt class="font-semibold text-ink">grade</dt><dd class="text-ink-muted">4, R, or "Grade 4". Teachers use this to find learners.</dd></div>
                                <div><dt class="font-semibold text-ink">date_of_birth</dt><dd class="text-ink-muted">2016-03-12 or 12/03/2016</dd></div>
                                <div><dt class="font-semibold text-ink">parent1_first_name, parent1_last_name</dt></div>
                                <div><dt class="font-semibold text-ink">parent1_relationship</dt><dd class="text-ink-muted">Mother, Father, Guardian</dd></div>
                                <div><dt class="font-semibold text-ink">parent1_phone, parent1_email</dt><dd class="text-ink-muted">At least one is needed to link a parent.</dd></div>
                                <div><dt class="font-semibold text-ink">parent2_...</dt><dd class="text-ink-muted">The same five columns for a second parent or guardian.</dd></div>
                            </dl>
                            <p class="mt-3 text-xs text-ink-muted"><span class="text-brand-red">*</span> required. Other common headings such as Surname, Cell or Mobile are also recognised.</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($imports->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="panel-head"><h3>Recent imports</h3></div>
                    <div class="p-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">File</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Learners</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Parents</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase">Date</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($imports as $i)
                                    <tr>
                                        <td class="px-4 py-3">{{ $i->original_name }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ ['completed' => 'tone-new', 'failed' => 'tone-skip', 'previewed' => 'tone-warn', 'running' => 'tone-update'][$i->status] ?? 'tone-same' }}">{{ $i->statusLabel() }}</span>
                                        </td>
                                        <td class="px-4 py-3">{{ $i->summary ? ($i->summary['learners_created'] ?? 0) . ' new, ' . ($i->summary['learners_updated'] ?? 0) . ' updated' : '-' }}</td>
                                        <td class="px-4 py-3">{{ $i->summary ? ($i->summary['parents_created'] ?? 0) . ' new' : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $i->created_at->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3"><a href="{{ route('school.import.show', $i) }}" class="text-blue-600 hover:underline">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>