@props(['a'])

@php($s = $a->seenSummary())

@if($s)
    <div {{ $attributes->merge(['class' => 'seen']) }} title="Parents who have opened this">
        <x-icon name="eye" :size="14" />
        <span>Seen by <strong>{{ $s['seen'] }}</strong> of {{ $s['total'] }} {{ \Illuminate\Support\Str::plural('parent', $s['total']) }}</span>
        <span class="seen-bar"><span style="width: {{ $s['percent'] }}%"></span></span>
    </div>
@endif