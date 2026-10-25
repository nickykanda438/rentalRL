@props(['variant' => 'dark', 'mark' => false])
@php
    $file = $mark ? 'logo-mark.svg' : ($variant === 'light' ? 'logo-blanc.svg' : 'logo.svg');
@endphp
<img src="{{ asset('images/'.$file) }}" alt="rental" {{ $attributes->merge(['class' => $mark ? 'h-10 w-10' : 'h-9 w-auto']) }}>
