@extends('layout.app')

@section('title')
    {{ 'Tablero Analítico' }}
@endsection

@push('scripts')
    @vite(['resources/js/app.js', 'resources/css/dashboard.css', 'resources/js/dashboard.js', 'resources/js/dashboard-chart.js'])
    
    <!-- Pasar datos del servidor a JavaScript -->
    <script>
        // DATOS REALES DEL SERVIDOR
        window.monthlyData = @json($monthlyData ?? []);
        window.yearlyComparison = @json($yearlyComparison ?? []);
        window.currentYear = {{ $currentYear ?? date('Y') }};
        window.previousYear = {{ $previousYear ?? (date('Y') - 1) }};
        window.currentMonthRevenue = {{ $currentMonthRevenue ?? 0 }};
        window.previousMonthRevenue = {{ $previousMonthRevenue ?? 0 }};
        window.currentMonthInProcess = {{ $currentMonthInProcess ?? 0 }};
        window.previousMonthInProcess = {{ $previousMonthInProcess ?? 0 }};
        window.topClients = @json($topClients ?? []);
        window.transportStates = @json($transportStates ?? []);
        
        // DEBUG COMPLETO - MOSTRAR TODO LO QUE VIENE DEL SERVIDOR
        console.log('🔍 === DEBUG COMPLETO DE DATOS REALES ===');
        console.log('📊 yearlyComparison RAW:', @json($yearlyComparison ?? []));
        console.log('📊 monthlyData RAW:', @json($monthlyData ?? []));
        console.log('📊 currentYear:', {{ $currentYear ?? date('Y') }});
        console.log('📊 previousYear:', {{ $previousYear ?? (date('Y') - 1) }});
        
        // Verificar que window.monthlyData tiene los datos
        console.log('✅ window.monthlyData:', window.monthlyData);
        console.log('✅ Cantidad de meses:', window.monthlyData?.length || 0);

        if(window.monthlyData && window.monthlyData.length > 0) {
            console.log('✅ PRIMER MES:', window.monthlyData[0]);
            console.log('✅ ÚLTIMO MES:', window.monthlyData[window.monthlyData.length - 1]);
            
            const totalIngresos = window.monthlyData.reduce((sum, item) => {
                return sum + (parseFloat(item.revenue) || 0);
            }, 0);
            console.log('💰 TOTAL INGRESOS CALCULADO: $' + totalIngresos.toLocaleString());
        } else {
            console.log('❌ NO HAY DATOS EN window.monthlyData');
        }        console.log('🔍 === FIN DEBUG ===');
    </script>
    <style>
        .dashboard-gradient {
            background: linear-gradient(135deg, #F7B267 0%, #F25C54 100%);
        }
        .glass-morphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-scale:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Enhanced scrollbar for the page */
        body::-webkit-scrollbar {
            width: 8px;
        }
        body::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        body::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #F7B267, #F25C54);
            border-radius: 4px;
        }
        body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #F79D65, #F27059);
        }
        
        /* Smooth page transitions */
        * {
            scroll-behavior: smooth;
        }
        
        /* Loading state for images */
        img {
            transition: opacity 0.3s ease;
        }
        
        /* Enhanced focus states */
        button:focus, a:focus, select:focus {
            outline: 2px solid #F7B267;
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>
@endpush

@section('content')
    

    <!-- Main Dashboard Content -->
    <section class="w-full bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen -mt-16 relative z-20">
        <div class="container mx-auto px-4 md:px-8 pt-20 pb-12">
            
            <!-- Header Controls -->
            <div class="fade-in mb-8">
                @component('dashboardChart.components.headChart')
                @endcomponent
            </div>

            <!-- Metrics Cards -->
            <div class="fade-in mb-8" style="animation-delay: 0.2s">
                @component('dashboardChart.components.metricsChart', compact(
                    'currentMonthRevenue', 'previousMonthRevenue', 'revenueChangePercent',
                    'currentMonthInProcess', 'previousMonthInProcess', 'inProcessChangePercent', 
                    'activeClients', 'previousActiveClients', 'clientsChangePercent',
                    'completedProjects', 'previousCompletedProjects', 'projectsChangePercent'
                ))
                @endcomponent
            </div>

            <!-- Charts Section -->
            <div class="fade-in" style="animation-delay: 0.4s">
                @component('dashboardChart.components.chart', compact(
                    'appointments', 'monthlyData', 'topClients', 'transportStates',
                    'currentMonthRevenue', 'previousMonthRevenue', 'revenueChangePercent',
                    'completedProjects', 'previousCompletedProjects', 'projectsChangePercent'
                ))
                @endcomponent
            </div>

        </div>
    </section>
@endsection
