import Chart from "chart.js/auto";

// Variable global para almacenar los datos
let analysisData = null;

// Función principal para cargar todos los datos
async function getAnalysisData() {
    console.log('Iniciando carga de datos desde API...');
    
    try {
        const response = await fetch('/api/analysis/data');
        console.log('Respuesta recibida:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Datos parseados:', data);

        if (data) {
            // Asignar directamente los datos (no hay wrapper .data en esta API)
            analysisData = data;
            
            // Cargar datos del mapa
            await loadMapData();
            
            // Actualizar DOM
            console.log('Actualizando DOM con datos:', analysisData);
            updateDOMElements();
            
            // Inicializar gráficos
            initializeCharts();
            
            console.log('✅ Datos cargados exitosamente');
        } else {
            throw new Error('No se recibieron datos válidos');
        }
    } catch (error) {
        console.error('❌ Error cargando datos de análisis:', error);
        
        // Usar datos por defecto
        analysisData = getDefaultData();
        console.log('Usando datos por defecto:', analysisData);
        updateDOMElements();
        initializeCharts();
    }
}

// Función para cargar datos específicos del mapa
async function loadMapData() {
    try {
        const response = await fetch('/api/analysis/map-data');
        
        if (response.ok) {
            const mapResponse = await response.json();
            
            if (mapResponse.success && analysisData) {
                analysisData.mapData = mapResponse.data;
                console.log('Datos del mapa cargados:', analysisData.mapData);
            } else {
                console.warn('No se pudieron cargar datos del mapa:', mapResponse.message);
                // Asegurar que mapData tenga una estructura básica
                if (analysisData) {
                    analysisData.mapData = { cities: [], routes: [] };
                }
            }
        } else {
            console.warn('Error HTTP al cargar datos del mapa:', response.status);
            if (analysisData) {
                analysisData.mapData = { cities: [], routes: [] };
            }
        }
    } catch (error) {
        console.error('Error cargando datos del mapa:', error);
        // Asegurar que mapData tenga una estructura básica
        if (analysisData) {
            analysisData.mapData = { cities: [], routes: [] };
        }
    }
}

// Datos de fallback en caso de error
function getDefaultData() {
    return {
        monthlyChartData: {
            months: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            cotizations: [10, 15, 8, 22, 18, 25, 30, 28, 35, 40, 45, 50],
            efficiency: [65, 70, 68, 75, 72, 80, 85, 88, 90, 87, 92, 95]
        },
        transportTypeDistribution: [
            { tipo: 'Urbano', count: 45 },
            { tipo: 'Intermunicipal', count: 35 },
            { tipo: 'Otros', count: 20 }
        ],
        acceptanceRate: 68,
        averageResponseTime: 24,
        totalCotizations: 150,
        totalClients: 45,
        activeCotizations: 12
    };
}

// Función para actualizar elementos del DOM con los datos
function updateDOMElements() {
    console.log('updateDOMElements llamada con analysisData:', analysisData);
    
    if (!analysisData) {
        console.error('No hay analysisData disponible');
        return;
    }
    
    // Actualizar estadísticas principales
    const totalClientsEl = document.getElementById('totalClients');
    if (totalClientsEl) {
        totalClientsEl.textContent = analysisData.totalClients?.toLocaleString() || '0';
        console.log('Actualizado totalClients:', analysisData.totalClients);
    }
    
    const totalCotizationsEl = document.getElementById('totalCotizations');
    if (totalCotizationsEl) {
        totalCotizationsEl.textContent = analysisData.totalCotizationModels?.toLocaleString() || '0';
        console.log('Actualizado totalCotizations:', analysisData.totalCotizationModels);
    }
    
    const acceptanceRateEl = document.getElementById('acceptanceRate');
    if (acceptanceRateEl) {
        acceptanceRateEl.textContent = (analysisData.acceptanceRate || 0) + '%';
        console.log('Actualizado acceptanceRate:', analysisData.acceptanceRate);
    }
    
    const averageResponseTimeEl = document.getElementById('averageResponseTime');
    if (averageResponseTimeEl) {
        averageResponseTimeEl.textContent = (analysisData.averageResponseTime || 0) + 'h';
        console.log('Actualizado averageResponseTime:', analysisData.averageResponseTime);
    }
    
    // Actualizar datos económicos
    if (analysisData.economicAnalysis) {
        const avgValueEl = document.getElementById('avgValue');
        if (avgValueEl) {
            const avgValue = analysisData.economicAnalysis.avg_value || 0;
            avgValueEl.textContent = '$' + Number(avgValue).toLocaleString();
        }
        
        const totalValueEl = document.getElementById('totalValue');
        if (totalValueEl) {
            const totalValue = analysisData.economicAnalysis.total_value || 0;
            totalValueEl.textContent = '$' + Number(totalValue).toLocaleString();
        }
        
        const maxValueEl = document.getElementById('maxValue');
        if (maxValueEl) {
            const maxValue = analysisData.economicAnalysis.max_value || 0;
            maxValueEl.textContent = '$' + Number(maxValue).toLocaleString();
        }
    }
    
    // Actualizar datos de peso
    if (analysisData.weightAnalysis) {
        const avgWeightEl = document.getElementById('avgWeight');
        if (avgWeightEl) {
            const avgWeight = analysisData.weightAnalysis.avg_weight || 0;
            avgWeightEl.textContent = Number(avgWeight).toFixed(1) + 'kg';
        }
    }
    
    // Actualizar ciudades de origen
    console.log('Actualizando ciudades origen:', analysisData.topOriginCities);
    updateCityList('topOriginCities', analysisData.topOriginCities, 'ciudad_origen');
    
    // Actualizar ciudades de destino
    console.log('Actualizando ciudades destino:', analysisData.topDestinationCities);
    updateCityList('topDestinationCities', analysisData.topDestinationCities, 'ciudad_destino');
    
    // Actualizar tipos de vehículo
    console.log('Actualizando tipos vehiculo:', analysisData.vehicleTypeDistribution);
    updateList('vehicleTypes', analysisData.vehicleTypeDistribution, 'vehiculo_requerido');
    
    // Actualizar tipos de producto
    console.log('Actualizando tipos producto:', analysisData.productTypeDistribution);
    updateList('productTypes', analysisData.productTypeDistribution, 'tipo_producto');
    
    // Calcular crecimiento si hay datos mensuales
    if (analysisData.monthlyChartData && analysisData.monthlyChartData.cotizations) {
        const cotizations = analysisData.monthlyChartData.cotizations;
        const currentMonth = cotizations[cotizations.length - 1] || 0;
        const previousMonth = cotizations[cotizations.length - 2] || 0;
        const growth = previousMonth > 0 ? ((currentMonth - previousMonth) / previousMonth) * 100 : 0;
        
        const growthEl = document.getElementById('growth');
        if (growthEl) {
            growthEl.textContent = (growth >= 0 ? '+' : '') + growth.toFixed(1) + '%';
            growthEl.className = `font-semibold ${growth >= 0 ? 'text-green-500' : 'text-red-400'}`;
        }
    }
}

function updateCityList(elementId, data, fieldName) {
    const element = document.getElementById(elementId);
    if (!element || !data || !data.length) {
        if (element) {
            element.innerHTML = '<div class="text-center py-4"><p class="text-sm text-gray-500">No hay datos disponibles</p></div>';
        }
        return;
    }
    
    const html = data.slice(0, 5).map(item => `
        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
            <span class="text-sm font-medium">${item[fieldName] || 'N/A'}</span>
            <span class="text-sm text-blue-600 font-bold">${item.count}</span>
        </div>
    `).join('');
    
    element.innerHTML = html;
}

function updateList(elementId, data, fieldName) {
    const element = document.getElementById(elementId);
    if (!element || !data || !data.length) {
        if (element) {
            element.innerHTML = '<div class="text-center py-4"><p class="text-sm text-gray-500">No hay datos disponibles</p></div>';
        }
        return;
    }
    
    const html = data.slice(0, 5).map(item => `
        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
            <span class="text-sm font-medium">${item[fieldName] || 'N/A'}</span>
            <span class="text-sm text-green-600 font-bold">${item.count}</span>
        </div>
    `).join('');
    
    element.innerHTML = html;
}

// Inicializar mapa interactivo de Colombia con datos reales
function initColombiaMap() {
    console.log('Inicializando mapa de Colombia...');
    
    // Verificar que Leaflet esté disponible
    if (typeof L === 'undefined') {
        console.error('Leaflet no está cargado');
        return;
    }
    
    // Verificar que el elemento del mapa exista
    const mapElement = document.getElementById('colombia-map');
    if (!mapElement) {
        console.error('Elemento colombia-map no encontrado');
        return;
    }
    
    console.log('Elemento del mapa encontrado, creando mapa...');
    
    // Coordenadas de Colombia
    const colombiaBounds = [
        [-4.227, -81.735], // Suroeste
        [15.5, -66.87]     // Noreste
    ];

    const map = L.map('colombia-map', {
        maxBounds: colombiaBounds,
        maxBoundsViscosity: 1.0
    }).setView([4.5709, -74.2973], 6);

    // Usar un mapa ligero y minimalista
    L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 10,
        minZoom: 5
    }).addTo(map);

    console.log('Mapa base creado, agregando datos...');

    // Usar datos reales si están disponibles
    if (analysisData && analysisData.mapData) {
        console.log('Datos del mapa disponibles:', analysisData.mapData);
        addRealCitiesToMap(map, analysisData.mapData.cities);
        addRealRoutesToMap(map, analysisData.mapData.routes);
    } else {
        console.warn('No hay datos del mapa, usando ciudades por defecto');
        addDefaultCitiesToMap(map);
    }

    // Agregar leyenda
    addMapLegend(map);
    
    console.log('Mapa inicializado completamente');
}

function addRealCitiesToMap(map, cities) {
    if (!cities || !cities.length) return;

    // Colores según el tipo y volumen
    const getMarkerStyle = (city) => {
        let color = '#2563eb'; // azul por defecto
        let radius = 4;

        // Determinar color según el tipo
        if (city.type === 'origin') color = '#f97316'; // naranja para origen
        else if (city.type === 'destination') color = '#16a34a'; // verde para destino
        else if (city.type === 'both') color = '#8b5cf6'; // púrpura para ambos

        // Determinar tamaño según el volumen
        if (city.count >= 20) radius = 12;
        else if (city.count >= 10) radius = 9;
        else if (city.count >= 5) radius = 6;
        else radius = 4;

        return { color, radius };
    };

    cities.forEach(city => {
        const style = getMarkerStyle(city);
        
        const marker = L.circleMarker([city.lat, city.lng], {
            radius: style.radius,
            fillColor: style.color,
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(map);

        const typeText = city.type === 'origin' ? 'Origen' : 
                        city.type === 'destination' ? 'Destino' : 'Origen/Destino';

        marker.bindPopup(`
            <div class="text-center">
                <h3 class="font-bold text-lg">${city.name}</h3>
                <p class="text-sm text-gray-600">Tipo: ${typeText}</p>
                <p class="text-sm text-gray-600">Cotizaciones: ${city.count}</p>
            </div>
        `);
    });
}

function addRealRoutesToMap(map, routes) {
    if (!routes || !routes.length) return;

    // Colores para diferentes rutas
    const colors = ['#2563eb', '#16a34a', '#f97316', '#8b5cf6', '#ef4444', '#06b6d4'];

    routes.forEach((route, index) => {
        const color = colors[index % colors.length];
        
        // Determinar grosor de línea según frecuencia
        let weight = 2;
        if (route.count >= 10) weight = 6;
        else if (route.count >= 5) weight = 4;
        else weight = 2;

        const polyline = L.polyline([route.originCoords, route.destinationCoords], {
            color: color,
            weight: weight,
            opacity: 0.8,
            smoothFactor: 1
        }).addTo(map);

        polyline.bindPopup(`
            <div class="text-center">
                <h3 class="font-bold">${route.name}</h3>
                <p class="text-sm text-gray-600">Cotizaciones: ${route.count}</p>
                <p class="text-xs text-gray-500">Ruta real del sistema</p>
            </div>
        `);
    });
}

function addDefaultCitiesToMap(map) {
    // Principales ciudades de Colombia con datos por defecto
    const defaultCities = [
        { name: 'Bogotá', coords: [4.7110, -74.0721], size: 'grande', routes: 0 },
        { name: 'Medellín', coords: [6.2442, -75.5812], size: 'mediano', routes: 0 },
        { name: 'Cali', coords: [3.4516, -76.5320], size: 'mediano', routes: 0 },
        { name: 'Barranquilla', coords: [10.9639, -74.7964], size: 'pequeño', routes: 0 },
    ];

    // Colores según el tamaño
    const sizeColors = {
        grande: '#2563eb',   // azul
        mediano: '#16a34a',  // verde
        pequeño: '#f97316'   // naranja
    };

    defaultCities.forEach(city => {
        const marker = L.circleMarker(city.coords, {
            radius: city.size === 'grande' ? 8 : city.size === 'mediano' ? 6 : 4,
            fillColor: sizeColors[city.size],
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(map);

        marker.bindPopup(`
            <div class="text-center">
                <h3 class="font-bold text-lg">${city.name}</h3>
                <p class="text-sm text-gray-600">Cargando datos...</p>
            </div>
        `);
    });
}

function addMapLegend(map) {
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'info legend bg-white p-3 rounded shadow-lg');
        
        if (analysisData && analysisData.mapData && analysisData.mapData.routes.length > 0) {
            // Leyenda con datos reales
            div.innerHTML = `
                <h4 class="font-bold mb-2 text-sm">Rutas Reales del Sistema</h4>
                <div class="text-xs">
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
                        <span>Ciudades de origen</span>
                    </div>
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-green-600 mr-2"></div>
                        <span>Ciudades de destino</span>
                    </div>
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-purple-600 mr-2"></div>
                        <span>Origen y destino</span>
                    </div>
                    <div class="flex items-center mt-2">
                        <div class="w-4 h-1 bg-blue-600 mr-2"></div>
                        <span>Rutas frecuentes</span>
                    </div>
                </div>
            `;
        } else {
            // Leyenda por defecto
            div.innerHTML = `
                <h4 class="font-bold mb-2 text-sm">Mapa de Colombia</h4>
                <div class="text-xs">
                    <div class="flex items-center mb-1">
                        <div class="w-3 h-3 rounded-full bg-blue-600 mr-2"></div>
                        <span>Ciudades principales</span>
                    </div>
                    <p class="text-gray-500 mt-2">Cargando rutas...</p>
                </div>
            `;
        }
        return div;
    };
    legend.addTo(map);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async function() {
    console.log('DOM cargado, iniciando carga de datos...');
    
    // Cargar todos los datos
    await getAnalysisData();
    
    // Verificar que el mapa se inicialice después
    setTimeout(() => {
        if (document.getElementById('colombia-map')) {
            console.log('Inicializando mapa después de cargar datos');
            initColombiaMap();
        }
    }, 1000);
});

// Función principal para inicializar todos los gráficos
function initializeCharts() {
    console.log('Inicializando todos los gráficos...');
    
    if (!analysisData) {
        console.warn('No hay datos disponibles para los gráficos');
        return;
    }
    
    // Inicializar todos los gráficos
    initLineChart();
    initDoughnutChart();
    initMiniDonuts();
    initGrowthChart();
    
    console.log('Todos los gráficos inicializados');
}

function initLineChart() {
    console.log('Inicializando gráfico de líneas...');
    const lineChartSell = document.getElementById("lineChartSell");
    if (!lineChartSell) {
        console.warn('Elemento lineChartSell no encontrado');
        return;
    }
    
    // Usar datos reales de la API
    const chartData = analysisData.monthlyChartData || {
        months: ['Ago 2025'],
        cotizations: [analysisData.totalCotizationModels || 0],
        efficiency: [100]
    };
    
    console.log('Datos del gráfico de líneas:', chartData);
    
    const ctx = lineChartSell.getContext("2d");
    new Chart(ctx, {
        type: "line",
        data: {
            labels: chartData.months,
            datasets: [
                {
                    label: "Cotizaciones",
                    data: chartData.cotizations,
                    borderColor: "#2563eb",
                    backgroundColor: "rgba(37, 99, 235, 0.1)",
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: "Eficiencia (%)",
                    data: chartData.efficiency,
                    borderColor: "#16a34a",
                    backgroundColor: "rgba(22, 163, 74, 0.1)",
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#6b7280',
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                    ticks: {
                        color: '#6b7280',
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: '#6b7280',
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
            },
            plugins: {
                legend: {
                    position: "top",
                    labels: {
                        usePointStyle: true,
                        color: '#374151'
                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                }
            },
        },
    });
}

function initDoughnutChart() {
    console.log('Inicializando gráfico de dona...');
    const doughnutYear = document.getElementById("doughnutYear");
    if (!doughnutYear) {
        console.warn('Elemento doughnutYear no encontrado');
        return;
    }
    
    const ctx = doughnutYear.getContext("2d");
    
    // Preparar datos de distribución de tipos de transporte
    const transportData = analysisData.transportTypeDistribution || [];
    console.log('Datos de transporte para dona:', transportData);
    
    const labels = transportData.map(item => item.tipo || 'N/A');
    const data = transportData.map(item => item.count || 0);
    const colors = ["#f97316", "#16a34a", "#2563eb", "#ef4444", "#8b5cf6"];
    
    // Si no hay datos, usar datos por defecto
    if (transportData.length === 0) {
        labels.push('DTA');
        data.push(analysisData.totalCotizationModels || 8);
    }
    
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: labels,
            datasets: [
                {
                    data: data,
                    backgroundColor: colors.slice(0, data.length),
                    borderColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    hoverOffset: 4
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                title: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + percentage + '%';
                        }
                    }
                }
            },
            cutout: "70%",
        },
    });
}

function initMiniDonuts() {
    console.log('Inicializando mini donuts...');
    
    // Mini donut 1 - Tasa de aceptación
    const miniDonut1 = document.getElementById("miniDonut1");
    if (miniDonut1) {
        console.log('Creando mini donut 1 - Tasa de aceptación');
        const ctx1 = miniDonut1.getContext("2d");
        const acceptanceRate = analysisData.acceptanceRate || 0;
        
        new Chart(ctx1, {
            type: "doughnut",
            data: {
                labels: [],
                datasets: [
                    {
                        data: [acceptanceRate, 100 - acceptanceRate],
                        backgroundColor: ["#30e0a1", "#ccc"],
                        borderColor: ["#30e0a1", "#ccc"],
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: false,
                    },
                },
                cutout: "85%",
            },
        });
    }

    // Mini donut 2 - Tiempo de respuesta
    const miniDonut2 = document.getElementById("miniDonut2");
    if (miniDonut2) {
        const ctx2 = miniDonut2.getContext("2d");
        const responseTime = analysisData.averageResponseTime || 0;
        const maxTime = 48; // 48 horas como máximo
        const percentage = Math.min((responseTime / maxTime) * 100, 100);
        
        new Chart(ctx2, {
            type: "doughnut",
            data: {
                labels: [],
                datasets: [
                    {
                        data: [percentage, 100 - percentage],
                        backgroundColor: ["#f27f16", "#ccc"],
                        borderColor: ["#f27f16", "#ccc"],
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: false,
                    },
                },
                cutout: "85%",
            },
        });
    }
}

function initGrowthChart() {
    const lineYearNoGrid = document.getElementById("lineYearNoGrid");
    if (!lineYearNoGrid) return;
    
    const ctx = lineYearNoGrid.getContext("2d");
    new Chart(ctx, {
        type: "line",
        data: {
            labels: analysisData.monthlyChartData.months,
            datasets: [
                {
                    label: "",
                    data: analysisData.monthlyChartData.cotizations,
                    borderColor: "#2563eb",
                    backgroundColor: "rgba(37, 99, 235, 0.1)",
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    display: false,
                    grid: {
                        display: false,
                    },
                },
                x: {
                    display: false,
                    grid: {
                        display: false,
                    },
                },
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return 'Cotizaciones: ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#2563eb',
                    hoverBorderColor: '#fff',
                    hoverBorderWidth: 2
                }
            }
        },
    });
}
