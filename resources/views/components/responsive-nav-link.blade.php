@props(['active'])

@php
    $classes = ($active ?? false) ? 'resp-item resp-item-active' : 'resp-item';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>