@props(['label', 'value', 'tone' => 'default'])
@php
    $box = ['default' => 'bg-white border-gray-200', 'ok' => 'bg-white border-gray-200', 'warn' => 'bg-red-50 border-red-200', 'accent' => 'bg-accent-50 border-accent-200'][$tone];
    $val = ['default' => 'text-gray-900', 'ok' => 'text-primary-600', 'warn' => 'text-red-600', 'accent' => 'text-accent-700'][$tone];
@endphp
<div {{ $attributes->merge(['class' => "rounded-xl border p-4 $box"]) }}>
    <p class="text-xs font-medium {{ $tone === 'warn' ? 'text-red-700' : 'text-gray-500' }}">{{ $label }}</p>
    <p class="mt-1.5 text-xl font-bold tabular-nums {{ $val }}">{{ $value }}</p>
</div>
