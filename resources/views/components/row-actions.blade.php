@props(['show' => null, 'modal' => null, 'delete' => null, 'confirm' => 'Supprimer cet élément ?'])
<div class="flex justify-end gap-1">
    @if ($show)
        <a href="{{ $show }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" title="Voir la fiche"><x-icon name="eye" class="h-4 w-4" /><span class="sr-only">Voir la fiche</span></a>
    @endif
    <button type="button" @if ($modal) data-modal-target="{{ $modal }}" data-modal-toggle="{{ $modal }}" @endif class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" title="Modifier"><x-icon name="edit" class="h-4 w-4" /><span class="sr-only">Modifier</span></button>
    @if ($delete)
        <form method="POST" action="{{ $delete }}" onsubmit="return confirm(@js($confirm))">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600" title="Supprimer"><x-icon name="trash" class="h-4 w-4" /><span class="sr-only">Supprimer</span></button>
        </form>
    @else
    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600" title="Supprimer"><x-icon name="trash" class="h-4 w-4" /><span class="sr-only">Supprimer</span></button>
    @endif
</div>
