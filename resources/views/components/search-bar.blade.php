@props(['placeholder' => 'Rechercher...', 'filters' => []])
<div class="flex flex-wrap gap-3">
    <div class="relative min-w-60 flex-1">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
        <label for="recherche" class="sr-only">Rechercher</label>
        <input id="recherche" type="search" placeholder="{{ $placeholder }}" class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
    </div>
    @foreach ($filters as $i => $options)
        <label for="filtre-{{ $i }}" class="sr-only">{{ $options[0] }}</label>
        <select id="filtre-{{ $i }}" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
            @foreach ($options as $option)<option>{{ $option }}</option>@endforeach
        </select>
    @endforeach
</div>
