@props(['active'])

@php
    $classes = ($active ?? false) ? 'nav-item nav-item-active' : 'nav-item';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>