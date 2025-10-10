@props(['id','label','options'=>[]])

<div>
    <label class="block text-gray-700">{{ $label }}</label>
    <select id="{{ $id }}"
            {{ $attributes->merge(['class'=>'text-gray-900 text-sm rounded-lg block w-full p-2.5']) }}>
        <option value="">Selecciona…</option>
        @foreach($options as $key=>$value)
            <option value="{{ is_string($key)?$key:$value }}">{{ $value }}</option>
        @endforeach
    </select>
</div>