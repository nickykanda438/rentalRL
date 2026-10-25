@props(['text'])
<span>{{ $text }}</span>
<nav aria-label="Pagination">
    <ul class="inline-flex -space-x-px text-sm">
        <li><a href="#" class="flex h-8 items-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50">Précédent</a></li>
        <li><a href="#" aria-current="page" class="flex h-8 items-center border border-primary-600 bg-primary-600 px-3 font-semibold text-white">1</a></li>
        <li><a href="#" class="flex h-8 items-center border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50">2</a></li>
        <li><a href="#" class="flex h-8 items-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50">Suivant</a></li>
    </ul>
</nav>
