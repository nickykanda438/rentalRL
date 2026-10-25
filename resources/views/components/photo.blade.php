@props(['name' => null, 'alt' => '', 'width' => 1200])
@php
    $photo = $name ? config('rental.photos.'.$name) : null;
    $src = $photo && file_exists(public_path('images/'.$photo['file']))
        ? asset('images/'.$photo['file'])
        : ($photo ? 'https://images.unsplash.com/photo-'.$photo['id'].'?auto=format&fit=crop&w='.$width.'&q=75' : null);
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-primary-50 text-primary-600']) }}>
        <x-icon name="car" class="h-10 w-10" />
    </div>
@endif
