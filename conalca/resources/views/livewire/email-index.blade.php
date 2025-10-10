<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Title -->
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] rounded-2xl shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26c.24.13.52.13.76 0L19 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] bg-clip-text text-transparent">
                        Correo Electrónico
                    </h1>
                </div>

                <!-- Search and Controls -->
                <div class="flex flex-col lg:flex-row gap-4 lg:items-center flex-1 lg:max-w-3xl">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="search" wire:model.live="filterByText" placeholder="Buscar correos..."
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent bg-gray-50 hover:bg-white transition-all duration-200 text-gray-700 placeholder-gray-400" />
                    </div>

                    <!-- Filter Dropdown -->
                    <div class="relative">
                        <select class="appearance-none bg-white border border-gray-200 rounded-2xl px-6 py-3 pr-12 focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent text-gray-700 hover:bg-gray-50 transition-all duration-200 cursor-pointer min-w-[140px]" wire:model.live="filterBy">
                            <option value="">Todos</option>
                            <option value="no-read">Sin leer</option>
                            <option value="today">Hoy</option>
                            <option value="week">Esta semana</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <!-- New Email Button -->
                    <button id="newEmail" wire:click="openModal2"
                        class="group bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] hover:from-[#e66c29] hover:to-[#ff8c42] text-white font-semibold py-3 px-8 rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2 min-w-[160px] justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo correo
                    </button>
                </div>
            </div>
        </div>
        <!-- Success Message -->
        @if ($successMessage)
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 p-6 rounded-2xl shadow-lg mb-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-16 w-16 bg-green-100 rounded-full opacity-20"></div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-800 font-semibold">{{ $successMessage }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 mb-8">
            <button
                class="group relative px-8 py-3 rounded-2xl font-semibold transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:scale-105 @if (!$filterBySend) bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] text-white @else bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 @endif"
                wire:click="setNormal()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m0 0v6m0-6c0 .742.4 1.39 1 1.732m0 0v.268m0-.268c.6-.342 1-.99 1-1.732m-2 0h.01" />
                </svg>
                Principal
                <span class="bg-white text-[#ff7c32] text-xs font-bold px-3 py-1 rounded-full shadow-md group-hover:scale-110 transition-transform duration-200">
                    {{ $this->getCount() }}
                </span>
            </button>
            <button
                class="group px-8 py-3 rounded-2xl font-semibold transition-all duration-300 flex items-center gap-3 shadow-lg hover:shadow-xl transform hover:scale-105 @if ($filterBySend) bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] text-white @else bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 @endif"
                wire:click="setFilterSend()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Enviados
            </button>
        </div>

        <!-- Emails Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            <!-- Emails Header -->
            <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b border-gray-100">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-[#ff7c32] bg-opacity-10 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#ff7c32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Bandeja de entrada</h3>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm font-medium">{{ count($emails) }} correos</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-500">
                        <span class="text-sm font-medium">1 de {{ count($emails) }}</span>
                        <div class="flex gap-2">
                            <button class="p-2 hover:bg-gray-100 rounded-xl transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button class="p-2 hover:bg-gray-100 rounded-xl transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Emails List -->
            <div class="divide-y divide-gray-100">
                @foreach ($emails as $email)
                    <div wire:click="openEmailModal({{ $email->id }})"
                        class="group relative p-6 hover:bg-gradient-to-r hover:from-gray-50 hover:to-orange-50 transition-all duration-300 cursor-pointer border-l-4 @if ($email->status == 'no-read' && $filterBySend == false) border-l-[#ff7c32] bg-gradient-to-r from-orange-50 to-white @else border-l-transparent @endif">
                        
                        <!-- Email Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-4">
                                <!-- Avatar -->
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gradient-to-br from-[#ff7c32] to-[#ff9f5c] rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        {{ substr($email->from_user_relation->name, 0, 2) }}
                                    </div>
                                    @if ($email->status == 'no-read' && $filterBySend == false)
                                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-[#ff7c32] rounded-full border-2 border-white"></div>
                                    @endif
                                </div>
                                
                                <!-- Email Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-bold text-gray-900 truncate">{{ $email->from_user_relation->name }}</h4>
                                        @if ($email->status == 'no-read' && $filterBySend == false)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-[#ff7c32] text-white">
                                                Nuevo
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $email->subject }}</p>
                                </div>
                            </div>
                            
                            <!-- Time and Status -->
                            <div class="flex flex-col items-end gap-2">
                                <span class="text-sm text-gray-500 font-medium">
                                    {{ $email->created_at->format('h:i A') }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if ($email->files && json_decode($email->files, true))
                                        <div class="p-1 bg-gray-100 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                        </div>
                                    @endif
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-[#ff7c32] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Preview -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 flex-shrink-0"></div> <!-- Spacer for alignment -->
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-600 line-clamp-2 leading-relaxed">
                                    {{ Str::limit($email->description, 120) }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Hover Effect -->
                        <div class="absolute inset-0 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none bg-gradient-to-r from-transparent via-orange-50 to-transparent"></div>
                    </div>
                @endforeach
                
                @if (count($emails) === 0)
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay correos</h3>
                        <p class="text-gray-500">No se encontraron correos electrónicos en esta carpeta.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Email Detail Modal -->
        @if ($isModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl w-[95%] max-w-4xl max-h-[95vh] overflow-hidden border border-gray-200">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] px-8 py-6 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26c.24.13.52.13.76 0L19 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold">Detalles del Correo</h2>
                            </div>
                            <button wire:click="closeModal" type="button"
                                class="p-2 hover:bg-white hover:bg-opacity-20 rounded-xl transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-8 overflow-y-auto max-h-[calc(95vh-120px)]">
                        <div class="space-y-6">
                            <!-- Email Thread -->
                            @if ($selectedEmail->parent)
                                @php
                                    $parentEmail = $selectedEmail->parent;
                                @endphp

                                <!-- Parent Email -->
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-start gap-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                {{ substr($parentEmail->from_user_relation->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900">{{ $parentEmail->from_user_relation->name }}</h4>
                                                <p class="text-sm text-gray-600">{{ $parentEmail->subject }}</p>
                                                <p class="text-xs text-gray-500">{{ $parentEmail->created_at->format('d/m/Y h:i A') }}</p>
                                            </div>
                                        </div>
                                        <button wire:click="toggleVisibility({{ $parentEmail->id }})"
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                                            {{ isset($isVisible[$parentEmail->id]) && $isVisible[$parentEmail->id] ? 'Ocultar' : 'Mostrar' }}
                                        </button>
                                    </div>

                                    @if (isset($isVisible[$parentEmail->id]) && $isVisible[$parentEmail->id])
                                        <div class="bg-white rounded-xl p-4 mb-4">
                                            <p class="text-gray-700 leading-relaxed">{{ $parentEmail->description }}</p>
                                        </div>
                                        
                                        @if ($parentEmail->files && json_decode($parentEmail->files, true))
                                            <div class="space-y-2">
                                                <h5 class="font-semibold text-gray-700">Archivos adjuntos:</h5>
                                                <div class="grid gap-2">
                                                    @foreach (json_decode($parentEmail->files, true) as $file)
                                                        <div class="flex items-center justify-between bg-white rounded-xl p-3 border border-gray-200">
                                                            <div class="flex items-center gap-3">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                                </svg>
                                                                <span class="text-sm font-medium text-gray-700">{{ basename($file) }}</span>
                                                            </div>
                                                            <a href="{{ url('uploads/' . basename($file)) }}" target="_blank"
                                                                class="text-[#ff7c32] hover:text-[#e66c29] font-medium text-sm">
                                                                Descargar
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <!-- Reply Emails -->
                                @foreach ($parentEmail->replies as $reply)
                                    <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-start gap-4">
                                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                    {{ substr($reply->from_user_relation->name, 0, 2) }}
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-900">{{ $reply->from_user_relation->name }}</h4>
                                                    <p class="text-sm text-gray-600">{{ $reply->subject }}</p>
                                                    <p class="text-xs text-gray-500">{{ $reply->created_at->format('d/m/Y h:i A') }}</p>
                                                </div>
                                            </div>
                                            <button wire:click="toggleVisibility({{ $reply->id }})"
                                                class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                                                {{ isset($isVisible[$reply->id]) && $isVisible[$reply->id] ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </div>

                                        @if (isset($isVisible[$reply->id]) && $isVisible[$reply->id])
                                            <div class="bg-white rounded-xl p-4 mb-4">
                                                <p class="text-gray-700 leading-relaxed">{{ $reply->description }}</p>
                                            </div>
                                            
                                            @if ($reply->files && json_decode($reply->files, true))
                                                <div class="space-y-2">
                                                    <h5 class="font-semibold text-gray-700">Archivos adjuntos:</h5>
                                                    <div class="grid gap-2">
                                                        @foreach (json_decode($reply->files, true) as $file)
                                                            <div class="flex items-center justify-between bg-white rounded-xl p-3 border border-gray-200">
                                                                <div class="flex items-center gap-3">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                                    </svg>
                                                                    <span class="text-sm font-medium text-gray-700">{{ basename($file) }}</span>
                                                                </div>
                                                                <a href="{{ url('uploads/' . basename($file)) }}" target="_blank"
                                                                    class="text-[#ff7c32] hover:text-[#e66c29] font-medium text-sm">
                                                                    Descargar
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            <!-- Success Message for Forwarding -->
                            @if ($emailReSend)
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 p-4 rounded-xl">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-400 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-green-800 font-semibold">Correo reenviado exitosamente</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Current Email -->
                            <div class="bg-orange-50 rounded-2xl p-6 border border-orange-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-[#ff7c32] to-[#ff9f5c] rounded-full flex items-center justify-center text-white font-bold text-lg">
                                            {{ substr($selectedEmail->from_user_relation->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900">{{ $selectedEmail->from_user_relation->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $selectedEmail->subject }}</p>
                                            <p class="text-xs text-gray-500">{{ $selectedEmail->created_at->format('d/m/Y h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-xl p-4 mb-4">
                                    <p class="text-gray-700 leading-relaxed">{{ $selectedEmail->description }}</p>
                                </div>

                                <!-- Forward Button -->
                                <div class="flex justify-end mb-4">
                                    @if (!$forwarding)
                                        <button wire:click="showForwarding"
                                            class="px-6 py-2 bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] hover:from-[#e66c29] hover:to-[#ff8c42] text-white font-semibold rounded-xl transition-all duration-200 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            Reenviar
                                        </button>
                                    @else
                                        <div class="w-full space-y-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Buscar Usuario</label>
                                                <input type="text" wire:model.live="searchTerm"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent"
                                                    placeholder="Ingrese el nombre de usuario...">
                                            </div>

                                            @if ($searchTerm && $users->isNotEmpty())
                                                <div class="bg-white border rounded-xl shadow-lg max-h-40 overflow-auto">
                                                    @foreach ($users as $user)
                                                        <div wire:click="selectUser('{{ $user->id }}', '{{ $user->name }}')"
                                                            class="p-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                                            {{ $user->name }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($searchTerm && $users->isEmpty())
                                                <p class="text-sm text-gray-500">No se encontraron usuarios.</p>
                                            @endif

                                            @if ($selectedUsers)
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Usuarios Seleccionados</label>
                                                    <div class="space-y-2">
                                                        @foreach ($selectedUsers as $user)
                                                            <div class="flex items-center justify-between bg-white rounded-xl p-3 border border-gray-200">
                                                                <span class="font-medium">{{ $user['name'] }}</span>
                                                                <button type="button" wire:click="removeUser('{{ $user['id'] }}')"
                                                                    class="text-red-500 hover:text-red-700 font-medium">
                                                                    Quitar
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="flex justify-end gap-3">
                                                <button wire:click="showForwarding"
                                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors duration-200">
                                                    Cancelar
                                                </button>
                                                <button wire:click="forwardEmail"
                                                    class="px-6 py-2 bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] hover:from-[#e66c29] hover:to-[#ff8c42] text-white font-semibold rounded-xl transition-all duration-200">
                                                    Enviar
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Files -->
                                @if ($selectedEmail->files && json_decode($selectedEmail->files, true))
                                    <div class="space-y-2">
                                        <h5 class="font-semibold text-gray-700">Archivos adjuntos:</h5>
                                        <div class="grid gap-2">
                                            @foreach (json_decode($selectedEmail->files, true) as $file)
                                                <div class="flex items-center justify-between bg-white rounded-xl p-3 border border-gray-200">
                                                    <div class="flex items-center gap-3">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                        </svg>
                                                        <span class="text-sm font-medium text-gray-700">{{ basename($file) }}</span>
                                                    </div>
                                                    <a href="{{ url('uploads/' . basename($file)) }}" target="_blank"
                                                        class="text-[#ff7c32] hover:text-[#e66c29] font-medium text-sm">
                                                        Descargar
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Reply Form -->
                                <div class="mt-6 pt-6 border-t border-orange-200">
                                    <form action="{{ route('emails.reply') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <h5 class="font-semibold text-gray-700 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#ff7c32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Responder
                                        </h5>
                                        
                                        <input type="hidden" name="main_email_id"
                                            value="@if (is_null($selectedEmail->parent)){{ $selectedEmail->id }}@else{{ $selectedEmail->parent->id }}@endif">
                                        <input type="hidden" name="to_user" value="{{ $selectedEmail->from_user }}">
                                        
                                        <textarea name="description" rows="4" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent bg-white resize-none"
                                            placeholder="Escribe tu respuesta..."></textarea>
                                        
                                        <input type="file" name="files[]" multiple
                                            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-[#ff7c32] bg-gray-50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#ff7c32] file:text-white file:cursor-pointer hover:file:bg-[#e66c29]">
                                        
                                        <button type="submit"
                                            class="w-full bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] hover:from-[#e66c29] hover:to-[#ff8c42] text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            Enviar Respuesta
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- New Email Modal -->
        @if ($isOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[95vh] overflow-hidden border border-gray-200">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] px-8 py-6 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold">Crear Nuevo Correo</h2>
                            </div>
                            <button wire:click="closeModal2" type="button"
                                class="p-2 hover:bg-white hover:bg-opacity-20 rounded-xl transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-8 overflow-y-auto max-h-[calc(95vh-120px)]">
                        <form action="{{ route('emails.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            <!-- User Search Component -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                                <livewire:user-search />
                            </div>

                            <!-- Subject Field -->
                            <div class="space-y-2">
                                <label for="subject" class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#ff7c32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Asunto
                                </label>
                                <input type="text" name="subject" id="subject" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent bg-white hover:border-gray-400 transition-colors duration-200"
                                    placeholder="Ingrese el asunto del correo...">
                            </div>

                            <!-- Description Field -->
                            <div class="space-y-2">
                                <label for="description" class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#ff7c32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    Descripción
                                </label>
                                <textarea name="description" id="description" rows="6" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-transparent bg-white hover:border-gray-400 transition-colors duration-200 resize-none"
                                    placeholder="Escriba el contenido de su mensaje..."></textarea>
                            </div>

                            <!-- Files Field -->
                            <div class="space-y-2">
                                <label for="files" class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#ff7c32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Archivos adjuntos
                                </label>
                                <div class="relative">
                                    <input type="file" name="files[]" id="files" multiple
                                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl focus:ring-2 focus:ring-[#ff7c32] focus:border-[#ff7c32] bg-gray-50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#ff7c32] file:text-white file:cursor-pointer hover:file:bg-[#e66c29]">
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none text-gray-400">
                                        <span class="text-sm">Seleccione archivos o arrástrelos aquí</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-4 pt-6">
                                <button type="submit"
                                    class="flex-1 bg-gradient-to-r from-[#ff7c32] to-[#ff9f5c] hover:from-[#e66c29] hover:to-[#ff8c42] text-white font-semibold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    Enviar Correo
                                </button>
                                <button wire:click="closeModal2" type="button"
                                    class="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors duration-200">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>