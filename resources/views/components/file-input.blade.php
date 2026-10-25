@props(['name' => 'piece_jointe', 'label' => 'Pièce jointe'])
<div>
    <label for="f-{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <input id="f-{{ $name }}" name="{{ $name }}" type="file" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
    <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG, 5 Mo maximum.</p>
</div>
