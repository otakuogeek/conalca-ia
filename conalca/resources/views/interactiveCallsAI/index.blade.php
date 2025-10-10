<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Demo IA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-lg { font-size: 1rem !important; } /* Reduce títulos */
        .text-xl { font-size: 1.125rem !important; } /* Ajuste de subtítulos */
        .text-2xl { font-size: 1.25rem !important; } /* Ajuste de títulos principales */

        .p-8 { padding: 1rem !important; } /* Reducimos padding interno */
        .py-3 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; } /* Botones más pequeños */
        .px-6 { padding-left: 1rem !important; padding-right: 1rem !important; } /* Ajuste en botones */

        /* Reducimos las tarjetas */
        .max-w-2xl { max-width: 450px !important; } 

        /* Ajustamos la tabla */
        .table-auto { font-size: 0.85rem !important; } /* Letras más pequeñas en la tabla */

        /* Ajustamos los inputs y selects */
        input, select, textarea {
        font-size: 0.85rem !important;
        padding: 0.5rem !important;
        }

        .loader {
            position: relative;
            width: 40px; /* Tamaño más compacto */
            height: 40px;
            display: none; /* Oculto por defecto */
        }

        .loader div {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 6px solid transparent;
            animation: pulse 1.5s infinite cubic-bezier(0.5, 0, 0.5, 1);
            box-shadow: 0 0 10px rgba(242, 141, 38, 0.6); /* Agregar un glow */
        }

        .loader div:nth-child(1) {
            border-top-color: #F28D26;
            animation-delay: 0s;
        }

        .loader div:nth-child(2) {
            border-right-color: #F28D26;
            animation-delay: 0.3s;
        }

        .loader div:nth-child(3) {
            border-bottom-color: #F28D26;
            animation-delay: 0.6s;
        }

        .loader div:nth-child(4) {
            border-left-color: #F28D26;
            animation-delay: 0.9s;
        }

        /* Rotación sutil para el loader */
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animación mejorada */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .loader {
            animation: rotate 2s linear infinite; /* Rotación para más dinamismo */
        }
    </style>
</head>
<body class="items-center justify-center h-screen bg-gray-100">
    <a href="{{ route('dashboard.show') }}" class="mt-4 inline-block px-6 py-3 text-white font-semibold text-lg bg-orange-500 rounded-lg shadow-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400">
        Ir al Home
    </a>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
        <div class="p-4 rounded shadow">
            <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl text-center mx-auto mt-8">
                <!-- Botón de regreso al Home -->

                <h1 class="text-2xl font-extrabold mb-6 text-gray-900">Interfaz de IA</h1>
                <input type="hidden" id="cotizacion_id" value="{{ $cotizacion_id }}" style="display: none;">

                <!-- Select para elegir tipo de llamada -->
                <label for="tipo_llamada" class="block text-left font-semibold text-gray-700 mb-2">Tipo de Llamada:</label>
                <select id="tipo_llamada" class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-5 focus:outline-none focus:ring-2 focus:ring-[#F28D26] bg-gray-50">
                    <option value="ai_interactiva">AI interactiva (detección de voz)</option>
                    <option value="marcacion">AI Llamada con marcación (1, 2)</option>
                </select>
        
                <!-- Input grande para el prompt -->
                <label for="prompt" class="block text-left font-semibold text-gray-700 mb-2">Prompt para la IA:</label>
                <!-- <textarea id="prompt" rows="5" placeholder="Escribe tu prompt aquí..." class="w-full px-4 py-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-[#F28D26] bg-gray-50"></textarea> -->
                <textarea id="prompt" rows="5" placeholder="El prompt se esta generando..." class="w-full px-4 py-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-[#F28D26] bg-gray-50"></textarea>

                <!-- Tipo de Vehículo -->
                <!-- <label for="type_vehicle" class="block text-left font-semibold text-gray-700 mb-2 mt-4">Tipo de Vehículo:</label>
                <select id="type_vehicle" class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-5 focus:outline-none focus:ring-2 focus:ring-[#F28D26] bg-gray-50">
                    <option value="todos">Todos</option>
                    <option value="camion">Camión</option>
                    <option value="turbo">Turbo</option>
                    <option value="tracto_camion">Tracto camión</option>
                    <option value="furgon">Furgón</option>
                    <option value="furgon_con_refrigerador">Furgón con refrigerador</option>
                </select> -->
                
                <div class="flex items-center justify-between my-6">
                    <span class="text-gray-700 font-semibold">Incluir bloqueados:</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="call_all_drivers" class="sr-only peer">
                        <div class="w-12 h-7 bg-gray-200 rounded-full peer-focus:ring-2 peer-focus:ring-[#F28D26] peer-checked:bg-[#F28D26] transition"></div>
                        <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full peer-checked:translate-x-6 transition-transform"></div>
                    </label>
                </div>
        
                <div class="flex items-center justify-between my-6">
                    <span class="text-gray-700 font-semibold">Excluir aceptantes de la última oferta:</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="exclude_acceptors_last_offer" class="sr-only peer">
                        <div class="w-12 h-7 bg-gray-200 rounded-full peer-focus:ring-2 peer-focus:ring-[#F28D26] peer-checked:bg-[#F28D26] transition"></div>
                        <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full peer-checked:translate-x-6 transition-transform"></div>
                    </label>
                </div>
        
                <button id="startCall" class="w-full bg-[#F28D26] text-white py-3 px-6 rounded-lg font-bold hover:bg-orange-600 transition">
                    Iniciar Llamadas
                </button>
        
                <button id="cancelCall" onclick="cancelCalls()" class="mt-4 w-full bg-red-500 text-white py-3 px-6 rounded-lg font-bold hover:bg-red-600 transition opacity-50 cursor-not-allowed" disabled>
                    Cancelar Llamadas
                </button>
            </div>
        </div>
        <div class="p-4 rounded shadow">
            <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl text-center mx-auto mt-8">
                <h2 class="text-xl font-bold mb-4 text-gray-900">Estado de Llamadas</h2>
                <p><strong>Total Usuarios:</strong> <span id="totalUsuarios">-</span></p>
                <p><strong>Llamadas Realizadas:</strong> <span id="llamadasRealizadas">-</span></p>
                <p><strong>Estado:</strong> <span id="estadoLlamadas" class="font-bold text-blue-600">-</span></p>
                <p><strong>Aceptado por:</strong> <span id="aceptadoPor">-</span></p>
        
                <div class="w-full bg-gray-200 rounded-full h-6 mt-4">
                    <div id="progressBar" class="bg-green-500 h-6 rounded-full text-white text-sm flex items-center justify-center" style="width: 0%;">0%</div>
                </div>
            </div>
            <div>
            <div class="flex justify-center items-center mt-6">
                <div class="loader" id="loading">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl mx-auto mt-8">
        <div class="flex flex-wrap gap-4 mb-4">
            <input type="date" id="start-date" class="border rounded p-2">
            <input type="date" id="end-date" class="border rounded p-2">
            <select id="vehicle-type" class="border rounded p-2">
                <option value="">Todos</option>
                <option value="camion">Camión</option>
                <option value="turbo">Turbo</option>
                <option value="tracto_camion">Tracto camión</option>
                <option value="furgon">Furgón</option>
                <option value="furgon_con_refrigerador">Furgón con refrigerador</option>
            </select>
            <select id="call-status" class="border rounded p-2">
                <option value="">Todos los estados</option>
                <option value="calling">Llamando</option>
                <option value="completed">Completado</option>
            </select>
            <button onclick="fetchAllCalls()" class="bg-blue-500 text-white px-4 py-2 rounded">Filter</button>
        </div>
        <h2 class="text-lg font-bold mb-4 text-gray-900">Llamadas Realizadas</h2>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">ID Solicitud de transporte</th>
                        <th class="border p-2">Total Conductores</th>
                        <th class="border p-2">Llamadas Realizadas</th>
                        <th class="border p-2">Aceptadas</th>
                        <th class="border p-2">No Respondidas</th>
                        <th class="border p-2">Estado</th>
                        <th class="border p-2">Conductores Aceptados</th>
                    </tr>
                </thead>
                <tbody id="calls-table">
                    <!-- Aquí se llenará con JS -->
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl mx-auto mt-8">
        <h2 class="text-lg font-bold mb-4 text-gray-900">Conductores Bloqueados</h2>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">ID Conductor</th>
                        <th class="border p-2">Nombre Conductor</th>
                        <th class="border p-2">Celular Conductor</th>
                        <th class="border p-2">Tipo de Vehículo</th>
                        <th class="border p-2">Acciones</th>
                    </tr>
                </thead>
                <tbody id="blocked-drivers-table">
                    <!-- Aquí se llenará con JS -->
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.getElementById("startCall").addEventListener("click", startCalls);

        function startCalls() {
            // const urlParams = new URLSearchParams(window.location.search);
            // const solicitudTransportesId = urlParams.get("solicitud_transportes") || "";
            const cotizacionId = document.getElementById("cotizacion_id")?.value || "";

            const tipoLlamada = document.getElementById("tipo_llamada").value;
            const prompt = document.getElementById("prompt").value.trim();
            // const typeVehicle = document.getElementById("type_vehicle").value;
            const callAllDrivers = document.getElementById("call_all_drivers").checked; 
            const excludeAcceptorsLastOffer = document.getElementById("exclude_acceptors_last_offer").checked; 
            let url = "";
            let body = {};

            if (tipoLlamada === "ai_interactiva") {
                if (prompt === "") {
                    alert("Por favor, ingresa un prompt antes de iniciar la llamada.");
                    return;
                }
                url = "/interactive-call-drivers";
                body = { 
                    prompt: prompt,
                    call_all_drivers: callAllDrivers,
                    exclude_acceptors_last_offer: excludeAcceptorsLastOffer,
                    // type_vehicle: typeVehicle,
                    cotizacion_id: cotizacionId
                };
            } else if (tipoLlamada === "marcacion") {
                if (prompt === "") {
                    alert("Por favor, ingresa un prompt antes de iniciar la llamada.");
                    return;
                }
                url = "/marking-call-drivers";
                body = { 
                    prompt: prompt,
                    call_all_drivers: callAllDrivers,
                    exclude_acceptors_last_offer: excludeAcceptorsLastOffer,
                    // type_vehicle: typeVehicle,
                    cotizacion_id: cotizacionId
                };
            }

            updateViewState(true); // Activar loading antes de la petición

            fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || "Llamada iniciada correctamente.");
                // updateViewState(true);
            })
            .catch(error => {
                console.error("Error al iniciar la llamada:", error);
                alert("Hubo un error al iniciar la llamada.");
                updateViewState(false);
            });
        }

        function cancelCalls() {
            updateViewState(false);
            fetch('/cancel-call', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || "Llamadas canceladas.");
                // updateViewState(false);
                document.getElementById("estadoLlamadas").textContent = "Cancelado";
            })
            .catch(error => {
                console.error("Error al cancelar la llamada:", error);
                alert("Hubo un error al cancelar las llamadas.");
            });
        }

        function fetchCallStatus() {
            fetch('/last-call') // Solicitar el último registro
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) {
                        console.warn("No hay llamadas registradas.");
                        return;
                    }

                    // Ahora `data` es un objeto del último registro
                    document.getElementById("totalUsuarios").textContent = data.total_drivers ?? '-';
                    document.getElementById("llamadasRealizadas").textContent = data.calls_made ?? '-';
                    document.getElementById("estadoLlamadas").textContent = data.status ?? '-';

                    // Mostrar el nombre de los conductores que aceptaron la oferta
                    let acceptedDrivers = Array.isArray(data.drivers) // Verifica que drivers es un array
                        ? data.drivers.map(driver => driver.driver_name).join(', ')
                        : '-';

                    document.getElementById("aceptadoPor").textContent = acceptedDrivers || '-';

                    // Calcular porcentaje de progreso
                    let porcentaje = (data.calls_made / data.total_drivers) * 100 || 0;

                    // Si el estado es "completada", forzar 100%
                    if (data.status?.toLowerCase() === "completed") {
                        porcentaje = 100;
                    }

                    porcentaje = Math.min(100, Math.max(0, porcentaje));
                    document.getElementById("progressBar").style.width = porcentaje + "%";
                    document.getElementById("progressBar").textContent = Math.round(porcentaje) + "%";

                    // Actualiza la variable global enEjecucion
                    enEjecucion = data.status?.toLowerCase() === "calling";
                    console.log("Nuevo estado de ejecución recibido:", enEjecucion);

                    updateViewState(enEjecucion);
                })
                .catch(error => console.error("Error obteniendo estado de llamadas:", error));
        }

        function fetchAllCalls() {
            // Get filter values
            let startDate = document.getElementById('start-date').value;
            let endDate = document.getElementById('end-date').value;
            let typeVehicle = document.getElementById('vehicle-type').value;
            let status = document.getElementById('call-status').value;

            // Build query parameters
            let queryParams = new URLSearchParams();
            if (startDate) queryParams.append('start_date', startDate);
            if (endDate) queryParams.append('end_date', endDate);
            if (typeVehicle) queryParams.append('type_vehicle', typeVehicle);
            if (status) queryParams.append('status', status);

            fetch(`/all-calls?${queryParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    let table = document.getElementById('calls-table');
                    table.innerHTML = ''; // Clear table before updating

                    data.forEach(call => {
                        let row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${call.quotation_id}</td>
                            <td>${call.total_drivers}</td>
                            <td>${call.calls_made}</td>
                            <td>${call.calls_accepted}</td>
                            <td>${call.calls_not_answered}</td>
                            <td class="font-semibold ${call.status === 'completed' ? 'text-green-600' : 'text-red-600'}">
                                ${call.status}
                            </td>
                            <td>
                                <ul class="list-disc list-inside text-left">
                                    ${call.drivers.map(driver => `
                                        <li class="text-sm text-gray-700">
                                            ${driver.driver_name} (${driver.type_vehicle})<br>
                                            <span class="text-gray-500">${driver.driver_phone_number}</span>
                                        </li>`).join('')}
                                </ul>
                            </td>
                        `;
                        table.appendChild(row);
                    });
                })
                .catch(error => console.error("Error fetching calls:", error));
        }

        function fetchAllBlockedDrivers() {
            fetch('/all-blocked-drivers')
                .then(response => response.json())
                .then(data => {
                    let table = document.getElementById('blocked-drivers-table');
                    table.innerHTML = ''; // Limpiar la tabla antes de llenarla

                    data.forEach(driver => {
                        let row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${driver.driver_id}</td>
                            <td>${driver.driver_name}</td>
                            <td>${driver.driver_phone_number}</td>
                            <td>${driver.type_vehicle}</td>
                            <td>
                                <button 
                                    class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded transition"
                                    onclick="unblockDriver('${driver.driver_id}')"
                                >
                                    Desbloquear
                                </button>
                            </td>
                        `;
                        table.appendChild(row);
                    });
                })
                .catch(error => console.error("Error obteniendo conductores bloqueados:", error));
        }

        function unblockDriver(driverId) {
            if (!confirm('¿Estás seguro de que deseas desbloquear este conductor?')) {
                return;
            }

            fetch('/unblock-driver', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ driver_id: driverId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al eliminar el conductor de la lista de bloqueados.');
                }
                return response.json();
            })
            .then(data => {
                alert(data.message);
                // Actualizar la tabla después de eliminar
                fetchAllBlockedDrivers();
            })
            .catch(error => console.error(error));
        }

        function updateViewState(ejecutando) {
            const startButton = document.getElementById("startCall");
            const cancelButton = document.getElementById("cancelCall");
            const prompt = document.getElementById("prompt");
            const loader = document.getElementById("loading");
            const estadoLlamadas = document.getElementById("estadoLlamadas");
            const progressBar = document.getElementById("progressBar");

            if (ejecutando) {
                startButton.disabled = true;
                startButton.classList.add('opacity-50', 'cursor-not-allowed');

                cancelButton.disabled = false;
                cancelButton.classList.remove('opacity-50', 'cursor-not-allowed');

                prompt.disabled = true;
                loader.style.display = "block";

                estadoLlamadas.textContent = "Ejecutando...";
                estadoLlamadas.classList.remove("text-blue-600", "text-green-600", "text-red-600");
                estadoLlamadas.classList.add("text-orange-600");

                progressBar.style.width = "0%";
                progressBar.textContent = "0%";
                progressBar.classList.remove("bg-red-500", "bg-green-500");
                progressBar.classList.add("bg-blue-500", "transition-all", "duration-500");

            } else {
                startButton.disabled = false;
                startButton.classList.remove('opacity-50', 'cursor-not-allowed');

                cancelButton.disabled = true;
                cancelButton.classList.add('opacity-50', 'cursor-not-allowed');

                prompt.disabled = false;
                loader.style.display = "none";

                estadoLlamadas.textContent = "Finalizado";
                estadoLlamadas.classList.remove("text-orange-600", "text-blue-600");
                estadoLlamadas.classList.add("text-green-600");

                progressBar.style.width = "100%";
                progressBar.textContent = "100%";
                progressBar.classList.remove("bg-blue-500");
                progressBar.classList.add("bg-green-500");
            }
        }

        function fetchLastTransportRequest() {
            fetch(`/last-transport-request`)
                .then(response => response.json())
                .then(data => {
                    let promptTextarea = document.getElementById("prompt");
                    if (data.prompt) {
                        promptTextarea.value = data.prompt;
                    } else {
                        promptTextarea.value = "Error: No se pudo obtener la información del flete.";
                    }
                })
                .catch(error => {
                    console.error("Error al obtener la solicitud de transporte:", error);
                    document.getElementById("prompt").value = "Error: No se pudo cargar la información.";
                });
        }

        document.addEventListener("DOMContentLoaded", fetchLastTransportRequest);

        // Verificar el estado de llamadas y actualizar tablas cada 10 segundos
        setInterval(() => {
            fetchCallStatus();
            fetchAllCalls();
            fetchAllBlockedDrivers();
        }, 10000);

        // Ejecutar las funciones inmediatamente al cargar la página
        fetchCallStatus();
        fetchAllCalls();
        fetchAllBlockedDrivers();
    </script>
</body>
</html>
