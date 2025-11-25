@props(['id','label','type'=>'text'])

<div>
    <label class="block text-gray-700">{{ $label }}</label>
    <input id="{{ $id }}"
           type="{{ $type }}"
           {{ $attributes->merge(['class'=>'text-gray-900 text-sm rounded-lg block w-full p-2.5']) }}>
</div>