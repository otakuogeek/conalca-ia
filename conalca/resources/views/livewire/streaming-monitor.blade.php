<div class="p-6 bg-gray-50 min-h-screen" wire:poll.3s="refreshData">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">🎵 Monitor de Streaming de Audio</h1>
                <p class="text-gray-600 mt-2">Sistema de audio interactivo bidireccional con ElevenLabs</p>
            </div>
            <div class="flex space-x-3">
                <button wire:click="toggleAutoRefresh" 
                        class="px-4 py-2 rounded-lg font-medium transition-colors
                               {{ $autoRefresh ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }}">
                    {{ $autoRefresh ? '🔄 Auto-refresh ON' : '⏸️ Auto-refresh OFF' }}
                </button>
                <button wire:click="loadData" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
                    🔄 Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Status Cards --}}
    @php
        $health = $this->getStreamingHealth();
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- System Status --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 {{ $health['status'] === 'active' ? 'border-green-500' : 'border-gray-400' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 {{ $health['status'] === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600' }} rounded-full flex items-center justify-center">
                        {{ $health['status'] === 'active' ? '🟢' : '🔴' }}
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Estado del Sistema</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $health['status'] === 'active' ? 'Activo' : 'Inactivo' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Active Sessions --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                        📞
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Sesiones Activas</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ count($activeSessions) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Static Audios --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 {{ $health['health'] === 'good' ? 'border-green-500' : 'border-yellow-500' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 {{ $health['health'] === 'good' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' }} rounded-full flex items-center justify-center">
                        🎼
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Audios Estáticos</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ array_sum(array_column($staticAudioStats, 'count')) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Storage Usage --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center">
                        💾
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Almacenamiento</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $streamingStats['total_size_mb'] ?? 0 }} MB</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Sessions Table --}}
    <div class="bg-white rounded-lg shadow-sm mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">📞 Sesiones de Streaming Activas</h3>
            <p class="text-sm text-gray-500">Llamadas con audio bidireccional en tiempo real</p>
        </div>
        
        @if(empty($activeSessions))
            <div class="p-8 text-center">
                <div class="text-gray-400 text-6xl mb-4">🔇</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay sesiones activas</h3>
                <p class="text-gray-500">Las sesiones de streaming aparecerán aquí cuando estén activas</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Call SID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Actividad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activeSessions as $session)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                    {{ substr($session['call_sid'], 0, 20) }}...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        🟢 Activa
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ gmdate('H:i:s', $session['duration']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $session['last_activity'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="clearSession('{{ $session['call_sid'] }}')" 
                                            class="text-red-600 hover:text-red-900 transition-colors">
                                        🗑️ Limpiar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Static Audios Categories --}}
    <div class="bg-white rounded-lg shadow-sm mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-medium text-gray-900">🎼 Audios Estáticos por Categoría</h3>
                <p class="text-sm text-gray-500">Respuestas pregeneradas para interacciones rápidas</p>
            </div>
            <button wire:click="regenerateStaticAudios" 
                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                🔄 Regenerar Audios
            </button>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($staticAudioStats as $category => $stats)
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-900 capitalize">
                                @php
                                    $icons = [
                                        'basic' => '🔧',
                                        'sentiment' => '😊',
                                        'urgent' => '🚨',
                                        'generic' => '💬',
                                        'greetings' => '👋',
                                        'interactive' => '🎯',
                                        'farewells' => '👋',
                                        'special' => '⭐'
                                    ];
                                @endphp
                                {{ $icons[$category] ?? '📁' }} {{ ucfirst($category) }}
                            </h4>
                            <span class="text-lg font-bold text-blue-600">{{ $stats['count'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ $stats['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Storage Stats --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-medium text-gray-900">💾 Estadísticas de Almacenamiento</h3>
                <p class="text-sm text-gray-500">Uso de espacio en audios estáticos y temporales</p>
            </div>
            <button wire:click="cleanupTempFiles" 
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium">
                🧹 Limpiar Temporales
            </button>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Static Files --}}
                <div class="text-center">
                    <div class="text-3xl text-blue-600 mb-2">🎼</div>
                    <h4 class="text-lg font-medium text-gray-900">Audios Estáticos</h4>
                    <p class="text-2xl font-bold text-blue-600">{{ $streamingStats['static_audios']['count'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">{{ $streamingStats['static_audios']['size_mb'] ?? 0 }} MB</p>
                </div>

                {{-- Temp Files --}}
                <div class="text-center">
                    <div class="text-3xl text-yellow-600 mb-2">⏳</div>
                    <h4 class="text-lg font-medium text-gray-900">Archivos Temporales</h4>
                    <p class="text-2xl font-bold text-yellow-600">{{ $streamingStats['temp_audios']['count'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">{{ $streamingStats['temp_audios']['size_mb'] ?? 0 }} MB</p>
                </div>

                {{-- Total Usage --}}
                <div class="text-center">
                    <div class="text-3xl text-purple-600 mb-2">💾</div>
                    <h4 class="text-lg font-medium text-gray-900">Uso Total</h4>
                    <p class="text-2xl font-bold text-purple-600">{{ $streamingStats['total_size_mb'] ?? 0 }} MB</p>
                    <p class="text-sm text-gray-500">Almacenamiento combinado</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            ✅ {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- Last Updated --}}
    <div class="mt-8 text-center text-sm text-gray-500">
        <p>🕒 Última actualización: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>🔄 Auto-refresh: {{ $autoRefresh ? 'Habilitado' : 'Deshabilitado' }} (cada {{ $refreshInterval }}s)</p>
    </div>
</div>