@php
    $editing = isset($announcement);
    $wanted  = (string) request('type');
    $current = old('type', $announcement->type ?? (array_key_exists($wanted, $types) ? $wanted : 'general'));

    // ?audience=subject:5:12, grade:5:12, all:12, or the older ?class=5
    $preselect = old('audience', request('audience') ?: (request('class') ? 'class:' . request('class') : ''));

    $hints = [
        'newsletter'     => 'Attach your newsletter as a PDF. Parents can read it in the browser or download it.',
        'question_paper' => 'Attach the question paper (PDF or Word). Put the subject and date in the title.',
        'assignment'     => 'Attach the assignment sheet and write the instructions in the message.',
        'homework'       => 'Attach a worksheet if there is one. Otherwise just describe the homework.',
        'project'        => 'Attach the project brief or rubric if you have one.',
        'test'           => 'Attach the test information or a study guide if you have one.',
    ];
@endphp

<div x-data="{ type: @js($current), hints: @js($hints) }">

    <div class="mb-6">
        <label class="field-label">Send to</label>

        @if($editing)
            <span class="chip">{{ $announcement->audienceLabel() }}</span>
            <p class="mt-2 text-sm text-ink-muted">Who receives it can't be changed after posting. Delete it and post again to change the audience.</p>

        @elseif(isset($audiences))
            {{-- Teacher: class, subject in one class, whole grade, all my classes --}}
            <select name="audience" required class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Select who should receive this</option>
                @foreach(collect($audiences)->groupBy('group', true) as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $key => $item)
                            <option value="{{ $key }}" {{ $preselect === $key ? 'selected' : '' }}>{{ $item['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('audience') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        @elseif(isset($gradeClasses))
            {{-- School admin: whole school, grades and classes, teachers only, SMT only --}}
            <div class="space-y-3"
                 x-data="{
                    mode: @js(old('audience', 'school')),
                    picked: @js(array_map('strval', (array) old('classes', []))),
                    grades: @js($gradeClasses->map(fn ($items) => $items->pluck('id')->map(fn ($id) => (string) $id)->values())->toArray()),
                    gradeOn(g) { const ids = this.grades[g] || []; return ids.length > 0 && ids.every(id => this.picked.includes(id)); },
                    toggleGrade(g, on) {
                        const ids = this.grades[g] || [];
                        this.picked = on ? Array.from(new Set([...this.picked, ...ids])) : this.picked.filter(id => ! ids.includes(id));
                    },
                    all() { this.picked = Object.values(this.grades).flat(); }
                 }">

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="aud-opt" :class="{ 'is-on': mode === 'school' }">
                        <input type="radio" name="audience" value="school" x-model="mode" class="sr-only">
                        <span class="aud-dot"></span>
                        <span><span class="block font-bold text-ink">Whole school</span><span class="text-sm text-ink-muted">Every parent and every teacher</span></span>
                    </label>

                    <label class="aud-opt" :class="{ 'is-on': mode === 'classes' }">
                        <input type="radio" name="audience" value="classes" x-model="mode" class="sr-only">
                        <span class="aud-dot"></span>
                        <span><span class="block font-bold text-ink">Grades and classes</span><span class="text-sm text-ink-muted">One grade, several grades, or specific classes</span></span>
                    </label>

                    <label class="aud-opt" :class="{ 'is-on': mode === 'teachers' }">
                        <input type="radio" name="audience" value="teachers" x-model="mode" class="sr-only">
                        <span class="aud-dot"></span>
                        <span><span class="block font-bold text-ink">Teachers only</span><span class="text-sm text-ink-muted">{{ $teacherCount }} {{ \Illuminate\Support\Str::plural('teacher', $teacherCount) }}. Parents will not see it.</span></span>
                    </label>

                    <label class="aud-opt" :class="{ 'is-on': mode === 'smt' }">
                        <input type="radio" name="audience" value="smt" x-model="mode" class="sr-only">
                        <span class="aud-dot"></span>
                        <span><span class="block font-bold text-ink">SMT members only</span><span class="text-sm text-ink-muted">{{ $smtCount }} {{ \Illuminate\Support\Str::plural('member', $smtCount) }} of the School Management Team</span></span>
                    </label>
                </div>

                <p x-show="mode === 'smt'" style="display:none"
                   class="rounded-xl bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
                    @if($smtCount === 0)
                        No one is marked as SMT yet, so nobody would receive this. Open
                        <a href="{{ route('school.teachers.index') }}" class="font-bold underline">Teachers</a>, edit a teacher and tick <strong>SMT member</strong>.
                    @else
                        Only teachers marked as SMT members see this announcement.
                    @endif
                </p>

                <div x-show="mode === 'classes'" style="display:none" class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm text-ink-muted"><strong class="text-ink" x-text="picked.length"></strong> class(es) selected. Tick a grade to select all its classes.</span>
                        <span class="flex gap-3 text-sm font-semibold">
                            <button type="button" class="text-navy-500 hover:text-brand-red" x-on:click="all()">Select everything</button>
                            <button type="button" class="text-navy-500 hover:text-brand-red" x-on:click="picked = []">Clear</button>
                        </span>
                    </div>

                    @forelse($gradeClasses as $grade => $items)
                        @php($grade = (string) $grade)
                        <div class="grade-box">
                            <label class="grade-box-head cursor-pointer">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                       :checked="gradeOn('{{ $grade }}')"
                                       x-on:change="toggleGrade('{{ $grade }}', $event.target.checked)">
                                <span>{{ $grade === '' ? 'No grade set' : ($grade === 'R' ? 'Grade R' : 'Grade ' . $grade) }}</span>
                                <span class="text-xs font-medium text-ink-muted">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('class', $items->count()) }}</span>
                            </label>

                            <div class="flex flex-wrap gap-2 p-3">
                                @foreach($items as $c)
                                    <label class="class-check">
                                        <input type="checkbox" name="classes[]" value="{{ $c->id }}" x-model="picked" :disabled="mode !== 'classes'"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        {{ $c->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">No active classes yet. Create classes first.</p>
                    @endforelse
                </div>
            </div>

            @error('audience') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
            @error('classes') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
            @error('classes.*') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
        @endif
    </div>

    <div class="mb-6">
        <label class="field-label">Type</label>
        <div class="flex flex-wrap gap-2">
            @foreach($types as $value => $label)
                <label>
                    <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only"
                           {{ $current == $value ? 'checked' : '' }}>
                    <span class="pill">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-sm text-ink-muted" x-show="hints[type]" x-text="hints[type]" style="display:none"></p>
        @error('type') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="mb-6">
        <label class="field-label">Title</label>
        <input type="text" name="title" value="{{ old('title', $announcement->title ?? '') }}" required
               placeholder="e.g. Grade 7 Maths test paper, Term 3"
               class="w-full rounded-md border-gray-300 shadow-sm">
        @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="mb-6" x-data="{ n: {{ strlen(old('body', $announcement->body ?? '')) }} }">
        <label class="field-label">Message</label>
        <textarea name="body" rows="6" required maxlength="5000" x-on:input="n = $event.target.value.length"
                  placeholder="Write the details parents need to know..."
                  class="w-full rounded-md border-gray-300 shadow-sm">{{ old('body', $announcement->body ?? '') }}</textarea>
        <div class="mt-1 text-right text-xs text-ink-muted"><span x-text="n"></span> / 5000</div>
        @error('body') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Existing files (edit page) --}}
    @if($editing && $announcement->attachments->isNotEmpty())
        <div class="mb-6">
            <label class="field-label">Current files <span class="font-normal text-ink-muted">(tick a file to remove it)</span></label>
            <div class="space-y-2">
                @foreach($announcement->attachments as $file)
                    <label class="file-chip cursor-pointer">
                        <input type="checkbox" name="remove_files[]" value="{{ $file->id }}"
                               class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                        <span class="file-badge file-{{ $file->kind() }}">{{ $file->extLabel() }}</span>
                        <span class="min-w-0">
                            <span class="file-name block">{{ $file->original_name }}</span>
                            <span class="file-meta">{{ $file->humanSize() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upload zone --}}
    <div x-data="{
            files: [],
            over: false,
            pick(list) { this.files = Array.from(list) },
            clear() { this.$refs.input.value = ''; this.files = [] },
            size(f) { return f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB' },
            get tooBig() { return this.files.some(f => f.size > 10 * 1048576) }
         }">
        <label class="field-label">{{ $editing ? 'Add more files' : 'Attachments' }} <span class="font-normal text-ink-muted">(optional)</span></label>

        <label class="drop" :class="{ 'is-over': over }"
               x-on:dragover.prevent="over = true"
               x-on:dragleave.prevent="over = false"
               x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; pick($refs.input.files)">
            <span class="drop-icon"><x-icon name="upload" :size="20" /></span>
            <span class="text-sm font-semibold text-ink">Click to choose files or drag them here</span>
            <span class="text-xs text-ink-muted" x-text="hints[type] ?? 'PDF, Word, Excel, PowerPoint or images.'"></span>
            <span class="text-xs text-ink-muted">Up to 5 files, 10 MB each.</span>
            <input type="file" name="files[]" multiple class="sr-only" x-ref="input"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                   x-on:change="pick($event.target.files)">
        </label>

        <div class="mt-3 space-y-2" x-show="files.length" style="display:none">
            <template x-for="f in files" :key="f.name + f.size">
                <div class="file-chip">
                    <span class="file-badge" x-text="(f.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4)"></span>
                    <div class="min-w-0">
                        <div class="file-name" x-text="f.name"></div>
                        <div class="file-meta" x-text="size(f)"></div>
                    </div>
                </div>
            </template>
            <button type="button" class="text-xs font-semibold text-red-600 hover:underline" x-on:click="clear()">Clear selection</button>
        </div>

        <p class="mt-2 text-sm text-red-600" x-show="tooBig" style="display:none">One of the files is larger than 10 MB and will be rejected.</p>

        @error('files') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
        @foreach(collect($errors->get('files.*'))->flatten()->unique() as $fileError)
            <p class="text-red-600 text-sm mt-2">{{ $fileError }}</p>
        @endforeach
    </div>
</div>