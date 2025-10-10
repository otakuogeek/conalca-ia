@extends('layout.app')

@section('title', 'Gestión de Metas')

@section('content')
    <div class="min-h-screen px-4 py-6" style="background-color: #fafafa;">
        <!-- Header Section -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-xl p-6 lg:p-8" style="border: 1px solid #ffbf69;">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(to bottom right, #ff9f1c, #f79d65);">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Metas</h1>
                            <p class="text-gray-600 text-lg">Monitorea el progreso y administra las metas de tu equipo comercial</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="px-4 py-2 rounded-lg" style="color: #8B4513; border: 1px solid #ff9f1c;">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold text-sm">{{ date('F Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Main Dashboard Column -->
                <div class="xl:col-span-2">
                    <div id="goal-dashboard" class="w-full"></div>
                </div>
                
                <!-- Sidebar Column -->
                <div class="xl:col-span-1 space-y-6">
                    <!-- Mi Progreso -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden" style="border: 1px solid #ffbf69;">
                        <div class="text-white p-4" style="background: linear-gradient(to right, #ff9f1c, #f79d65);">
                            <h3 class="text-lg font-semibold flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Mi Progreso
                            </h3>
                        </div>
                        <div id="my-goal-progress" class="p-4"></div>
                    </div>
                    
                    <!-- Notificaciones -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden" style="border: 1px solid #ffbf69;">
                        <div class="text-white p-4" style="background: linear-gradient(to right, #f79d65, #ff9f1c);">
                            <h3 class="text-lg font-semibold flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2L3 7v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7l-7-5z"></path>
                                </svg>
                                Notificaciones
                            </h3>
                        </div>
                        <div id="notification-list" class="p-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="max-w-7xl mx-auto mt-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-300" style="border: 1px solid #ffbf69;">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #ff9f1c;">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Equipo Comercial</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ auth()->user()->hasRole('SUPER ADMIN') ? 'Todos' : 'Mi Equipo' }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-300" style="border: 1px solid #ffbf69;">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #f79d65;">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Período Actual</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ date('M Y') }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-300" style="border: 1px solid #ffbf69;">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #ffbf69;">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Estado Sistema</p>
                            <p class="text-2xl font-semibold" style="color: #ff9f1c;">Activo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection






