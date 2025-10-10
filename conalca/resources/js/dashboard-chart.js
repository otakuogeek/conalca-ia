// Dashboard Chart - SOLO DATOS REALES
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 INICIANDO DASHBOARD CON DATOS REALES');
    loadChartJS().then(() => {
        // Esperar un poco para asegurar que el DOM está completamente cargado
        setTimeout(() => {
            initializeDashboardChart();
        }, 100);
    });
});

async function loadChartJS() {
    if (window.Chart) return Promise.resolve();
    
    try {
        const module = await import('chart.js/auto');
        window.Chart = module.default;
        return Promise.resolve();
    } catch (error) {
        console.error('Error loading Chart.js:', error);
        return Promise.reject(error);
    }
}

let dashboardChart = null;

function initializeDashboardChart() {
    console.log('📊 INICIANDO GRÁFICO');
    
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) {
        console.error('❌ Canvas no encontrado');
        return;
    }

    // DESTRUIR CUALQUIER GRÁFICO EXISTENTE PRIMERO
    if (dashboardChart) {
        console.log('🗑️ Destruyendo gráfico anterior');
        try {
            dashboardChart.destroy();
        } catch (e) {
            console.log('⚠️ Error al destruir gráfico anterior:', e);
        }
        dashboardChart = null;
    }

    // LIMPIAR CANVAS COMPLETAMENTE
    const context = ctx.getContext('2d');
    context.clearRect(0, 0, ctx.width, ctx.height);

    // OBTENER DATOS REALES DEL SERVIDOR
    const monthlyData = window.monthlyData;
    
    console.log('🔍 DATOS DEL SERVIDOR:', monthlyData);
    console.log('🔍 Cantidad de datos:', monthlyData?.length || 0);

    // Ocultar loading
    const loadingElement = document.getElementById('chart-loading');
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }

    // SI NO HAY DATOS, MOSTRAR MENSAJE
    if (!monthlyData || !Array.isArray(monthlyData) || monthlyData.length === 0) {
        console.log('❌ NO HAY DATOS REALES - Mostrando mensaje');
        showNoDataMessage();
        return;
    }

    // VERIFICAR SI HAY INGRESOS REALES
    const totalIngresos = monthlyData.reduce((sum, item) => {
        return sum + (parseFloat(item.revenue) || 0);
    }, 0);

    console.log('💰 Total ingresos encontrados: $' + totalIngresos.toLocaleString());

    if (totalIngresos === 0) {
        console.log('❌ NO HAY INGRESOS REALES');
        showNoDataMessage();
        return;
    }

    // PREPARAR DATOS PARA GRÁFICO
    const labels = [];
    const revenueData = [];
    
    monthlyData.forEach((item, index) => {
        const mes = item.month || `Mes ${index + 1}`;
        const revenue = parseFloat(item.revenue) || 0;
        
        labels.push(mes);
        revenueData.push(revenue);
        
        console.log(`📈 ${mes}: $${revenue.toLocaleString()}`);
    });

    // CREAR DATASET
    const datasets = [{
        label: `Ventas ${window.currentYear || new Date().getFullYear()}`,
        data: revenueData,
        borderColor: '#F97316',
        backgroundColor: 'rgba(249, 115, 22, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#F97316',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
    }];

    console.log('✅ Dataset creado con: $' + totalIngresos.toLocaleString());

    // CONFIGURACIÓN DEL GRÁFICO
    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: '#F97316',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            return `${context[0].label} - Ventas Mensuales`;
                        },
                        label: function(context) {
                            const value = context.parsed.y;
                            return `${context.dataset.label}: $${value.toLocaleString('es-ES')}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter, system-ui, sans-serif',
                            size: 12
                        },
                        color: '#6B7280',
                        callback: function(value) {
                            if (value >= 1000000) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return '$' + (value / 1000).toFixed(0) + 'K';
                            }
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter, system-ui, sans-serif',
                            size: 12,
                            weight: '600'
                        },
                        color: '#374151'
                    }
                }
            }
        }
    };

    // CREAR GRÁFICO CON MANEJO DE ERRORES
    try {
        console.log('🎨 Creando nuevo gráfico...');
        dashboardChart = new Chart(ctx, config);
        console.log('🎉 GRÁFICO CREADO CON DATOS REALES');
        console.log('🎉 Dataset mostrado con', revenueData.length, 'meses de datos');
    } catch (error) {
        console.error('❌ Error al crear gráfico:', error);
        showNoDataMessage();
    }
}

function showNoDataMessage() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;

    console.log('🚫 MOSTRANDO MENSAJE DE NO DATOS');

    if (dashboardChart) {
        dashboardChart.destroy();
        dashboardChart = null;
    }

    const canvas = ctx;
    const context = canvas.getContext('2d');
    
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    
    context.clearRect(0, 0, canvas.width, canvas.height);
    
    context.fillStyle = '#6B7280';
    context.font = 'bold 18px Inter, system-ui, sans-serif';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    
    context.fillText('📊 NO HAY DATOS DE VENTAS', centerX, centerY - 20);
    
    context.fillStyle = '#9CA3AF';
    context.font = '14px Inter, system-ui, sans-serif';
    context.fillText('Agrega cotizaciones para ver el análisis de ventas mensuales', centerX, centerY + 10);
    
    console.log('✅ Mensaje mostrado correctamente');
}
