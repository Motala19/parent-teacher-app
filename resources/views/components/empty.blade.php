@props(['title', 'text' => null, 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'empty']) }}>
    <span class="empty-icon"><x-icon :name="$icon" :size="26" /></span>
    <div class="font-bold text-ink">{{ $title }}</div>
    @if($text)
        <p class="text-sm text-ink-muted mt-1">{{ $text }}</p>
    @endif
    {{ $slot }}
</div>