@extends('layout.app')

@section('title', 'Esquema de Seguridad')

@section('content')
    <div class="min-h-screen px-4 py-6" style="background-color: #fafafa;">
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-xl p-6 lg:p-8" style="border: 1px solid #ffbf69;">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                style="background: linear-gradient(to bottom right, #ff9f1c, #f79d65);">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Esquema de Seguridad</h1>
                            <p class="text-gray-600 text-lg">Configura las medidas de seguridad por tipo de mercancía y valor declarado</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="px-4 py-2 rounded-lg" style="color: #8B4513; border: 1px solid #ff9f1c;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold text-sm">{{ auth()->user()->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <div id="security-schema-panel"></div>
        </div>
    </div>

    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection
