@props(['id', 'title', 'size' => 'max-w-2xl', 'formId' => null, 'submit' => 'Enregistrer', 'action' => '#', 'method' => 'POST'])
{{-- Fenêtre modale Flowbite : ouvrir avec data-modal-target / data-modal-toggle="{{ $id }}" --}}
<div id="{{ $id }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4">
    <div class="relative max-h-full w-full {{ $size }}">
        <form @if ($formId) id="{{ $formId }}" @endif method="POST" action="{{ $action }}" enctype="multipart/form-data" class="relative rounded-2xl bg-white shadow-xl">
            @csrf
            @if (strtoupper($method) !== 'POST') @method($method) @endif
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900" data-modal-hide="{{ $id }}">
                    <x-icon name="x" /><span class="sr-only">Fermer</span>
                </button>
            </div>
            <div class="space-y-5 px-6 py-5">{{ $slot }}</div>
            <div class="flex justify-end gap-2.5 border-t border-gray-200 px-6 py-4">
                <x-button variant="light" data-modal-hide="{{ $id }}">Annuler</x-button>
                <x-button type="submit">{{ $submit }}</x-button>
            </div>
        </form>
    </div>
</div>
