{{-- Photo d'un véhicule (storage) ou pictogramme voiture si aucune photo --}}
@props(['vehicule'])
@if ($vehicule->photo)
    <img src="{{ asset('storage/'.$vehicule->photo) }}" alt="{{ $vehicule->marque }} {{ $vehicule->modele }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-primary-50 text-primary-600']) }}>
        <x-icon name="car" class="h-10 w-10" />
    </div>
@endif
