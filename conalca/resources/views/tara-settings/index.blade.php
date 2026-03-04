@extends('layout.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-orange-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Configuración de Tara</h1>
            <p class="text-sm text-gray-500">Administre los pesos de tara para contenedores de 20 y 40 pies</p>
        </div>
    </div>

    {{-- Success message --}}
    @if(session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-green-700 text-sm font-medium">{{ session('status') }}</span>
        </div>
    @endif

    {{-- Info card --}}
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-700">
                <p class="font-medium mb-1">¿Qué es la tara?</p>
                <p>La tara es el peso del contenedor vacío que se suma automáticamente al peso de la mercancía en las cotizaciones. Estos valores se usan en todo el sistema para calcular el peso bruto.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('tara-settings.update', $setting) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            {{-- Contenedor 20 --}}
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-100 text-blue-600 font-bold text-sm rounded-lg">20'</span>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800">Contenedor de 20 pies</label>
                        <span class="text-xs text-gray-500">Standard (TEU)</span>
                    </div>
                </div>
                <div class="relative">
                    <input type="number" 
                           step="0.01" 
                           name="tara_contenedor_20" 
                           value="{{ old('tara_contenedor_20', $setting->tara_contenedor_20) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-12 text-lg font-semibold text-gray-800 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition" 
                           required
                           min="0"
                           max="99999">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium">kg</span>
                </div>
                @error('tara_contenedor_20')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contenedor 40 --}}
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-orange-100 text-orange-600 font-bold text-sm rounded-lg">40'</span>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800">Contenedor de 40 pies</label>
                        <span class="text-xs text-gray-500">Standard / High Cube / 45'</span>
                    </div>
                </div>
                <div class="relative">
                    <input type="number" 
                           step="0.01" 
                           name="tara_contenedor_40" 
                           value="{{ old('tara_contenedor_40', $setting->tara_contenedor_40) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-12 text-lg font-semibold text-gray-800 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition" 
                           required
                           min="0"
                           max="99999">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium">kg</span>
                </div>
                @error('tara_contenedor_40')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Note --}}
        <div class="mb-6 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-xs text-yellow-700">
                    <strong>Nota:</strong> El contenedor de 45 pies usa el mismo valor de tara que el de 40 pies. 
                    Al cambiar estos valores, se aplicarán automáticamente a todas las nuevas cotizaciones.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg shadow transition-colors">
                Guardar Cambios
            </button>
            <a href="{{ route('percentage-settings.index') }}" class="px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm transition-colors">
                Volver
            </a>
        </div>
    </form>
</div>
@endsection
