@props(['icon' => null, 'variant' => 'primary', 'href' => null])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold whitespace-nowrap focus:outline-none focus:ring-4 transition';
    $v = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-200',
        'light' => 'border border-gray-200 bg-white text-gray-800 hover:bg-gray-50 focus:ring-gray-100',
        'accent' => 'bg-accent-500 text-white hover:bg-accent-600 focus:ring-accent-100',
    ][$variant];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $v"]) }}>@if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => "$base $v"]) }}>@if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif{{ $slot }}</button>
@endif
