@props(['modal' => null])
<div class="flex justify-end gap-1">
    <button type="button" @if ($modal) data-modal-target="{{ $modal }}" data-modal-toggle="{{ $modal }}" @endif class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" title="Modifier"><x-icon name="edit" class="h-4 w-4" /><span class="sr-only">Modifier</span></button>
    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600" title="Supprimer"><x-icon name="trash" class="h-4 w-4" /><span class="sr-only">Supprimer</span></button>
</div>
