@props(['attachments'])

@if($attachments->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach($attachments as $file)
            <div class="file-chip">
                <span class="file-badge file-{{ $file->kind() }}">{{ $file->extLabel() }}</span>

                <div class="min-w-0">
                    <div class="file-name" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                    <div class="file-meta">{{ $file->humanSize() }}</div>
                </div>

                <div class="file-actions">
                    @if($file->isViewable())
                        <a href="{{ route('attachments.show', [$file, 'view' => 1]) }}" target="_blank" rel="noopener" title="View">
                            <x-icon name="eye" :size="16" />
                        </a>
                    @endif
                    <a href="{{ route('attachments.show', $file) }}" title="Download">
                        <x-icon name="download" :size="16" />
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif