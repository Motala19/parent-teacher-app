@php
    $editing = isset($announcement);
    $wanted  = (string) request('type');
    $current = old('type', $announcement->type ?? (array_key_exists($wanted, $types) ? $wanted : 'general'));

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

        @if(($lockClass ?? false) && $editing)
            <span class="chip">{{ $announcement->classRoom?->name ?? 'Whole school' }}</span>
        @else
            <select name="class_id" {{ $allowSchoolWide ? '' : 'required' }}
                    class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">{{ $allowSchoolWide ? 'Whole school' : 'Select class' }}</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}"
                        {{ old('class_id', $announcement->class_id ?? request('class')) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            @error('class_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
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