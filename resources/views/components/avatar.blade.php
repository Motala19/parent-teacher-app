@props(['name' => '', 'size' => 'h-10 w-10 text-sm'])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');
@endphp

<span {{ $attributes->merge(['class' => 'avatar ' . $size]) }}>{{ $initials ?: '?' }}</span>