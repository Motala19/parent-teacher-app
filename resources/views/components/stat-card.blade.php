@props(['label', 'value', 'icon' => 'grid', 'href' => null, 'tone' => 'navy'])

@if($href)
<a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => 'stat stat-' . $tone]) }}>
@else
<div {{ $attributes->merge(['class' => 'stat stat-' . $tone]) }}>
@endif
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="stat-value">{{ $value }}</div>
            <div class="stat-label">{{ $label }}</div>
        </div>
        <span class="stat-icon"><x-icon :name="$icon" :size="22" /></span>
    </div>
@if($href)
</a>
@else
</div>
@endif