@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'value' => null, 'options' => null, 'wide' => false])
@php
    $input = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600';
    $id = 'f-'.$name;
@endphp
<div class="{{ $wide ? 'sm:col-span-2 lg:col-span-3' : '' }}">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @if (is_array($options))
        <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => $input]) }}>
            @foreach ($options as $option)
                <option @selected(old($name, $value) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => $input]) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => $input]) }}>
    @endif
</div>
