@extends('layout.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow p-6 rounded">
    <h1 class="text-xl font-semibold mb-6">Percentage Control Panel</h1>

    @if(session('status'))
        <div class="mb-4 text-green-600">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('percentage-settings.update', $setting) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Minimum (%)</label>
                <input type="number" step="0.01" name="min_percentage" value="{{ old('min_percentage', $setting->min_percentage) }}"
                       class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Average (%)</label>
                <input type="number" step="0.01" name="avg_percentage" value="{{ old('avg_percentage', $setting->avg_percentage) }}"
                       class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Maximum (%)</label>
                <input type="number" step="0.01" name="max_percentage" value="{{ old('max_percentage', $setting->max_percentage) }}"
                       class="w-full border rounded px-3 py-2" required>
            </div>
        </div>

        <label class="inline-flex items-center mb-6">
            <input
                type="checkbox"
                name="use_custom_percentages"
                value="1"
                class="rounded border-gray-300 text-orange-500"
                {{ $setting->use_custom_percentages ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-gray-700">Utilizar estos porcentajes en los precios</span>
        </label>

        <div>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded shadow">
                Save
            </button>
        </div>
    </form>
</div>
@endsection