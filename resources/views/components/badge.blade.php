@props(['color' => 'green'])
@php
    $classes = [
        'green' => 'bg-primary-50 text-primary-700 ring-primary-200',
        'orange' => 'bg-accent-50 text-accent-700 ring-accent-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'lime' => 'bg-lime-50 text-lime-700 ring-lime-200',
        'gray' => 'bg-gray-100 text-gray-600 ring-gray-200',
    ][$color] ?? 'bg-gray-100 text-gray-600 ring-gray-200';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset whitespace-nowrap $classes"]) }}>{{ $slot }}</span>
