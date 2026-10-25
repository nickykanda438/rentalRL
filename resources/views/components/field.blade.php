@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'value' => null, 'options' => null, 'wide' => false, 'prefix' => 'f', 'placeholderOption' => null])
@php
    $input = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600';
    $id = $prefix.'-'.$name;
    // Les anciennes valeurs (old) ne s'appliquent qu'au formulaire qui a été soumis
    $val = old('_form', $prefix) === $prefix ? old($name, $value) : $value;
@endphp
<div class="{{ $wide ? 'sm:col-span-2 lg:col-span-3' : '' }}">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @if (is_array($options))
        <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => $input]) }}>
            @if ($placeholderOption)<option value="">{{ $placeholderOption }}</option>@endif
            @foreach ($options as $option)
                <option @selected($val === $option)>{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => $input]) }}>{{ $val }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $val }}" {{ $attributes->merge(['class' => $input]) }}>
    @endif
</div>
