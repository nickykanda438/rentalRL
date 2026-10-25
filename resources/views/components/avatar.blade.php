@props(['name', 'tone' => 0])
@php
    $initials = collect(explode(' ', $name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $tones = ['bg-primary-50 text-primary-700', 'bg-accent-50 text-accent-700', 'bg-lime-50 text-lime-700', 'bg-teal-50 text-teal-700'];
@endphp
<div class="flex items-center gap-3">
    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $tones[$tone % 4] }}">{{ mb_strtoupper($initials) }}</span>
    <span class="font-medium text-gray-900">{{ $name }}</span>
</div>
