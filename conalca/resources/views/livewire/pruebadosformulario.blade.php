<div>
    <style>
        .message-bubble {
            max-width: 80%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 15px;
            word-wrap: break-word;
            white-space: pre-wrap;
            /* Asegura el salto de línea */
        }

        .userMessage {
            background-color: rgba(255, 124, 50, 0.2);
            /* Color para los mensajes del usuario */
            color: #000;
            align-self: flex-end;
            text-align: right;
        }

        .botMessage {
            background-color: rgba(200, 200, 200, 0.2);
            /* Color para los mensajes del bot */
            color: #000;
            align-self: flex-start;
            text-align: left;
        }

        #chatbox {
            display: flex;
            flex-direction: column;
        }
    </style>

   @if($showModalCreateSolicitud)
        <script> if(!window.solicitudData){window.solicitudData=@json($solicitud_data);} </script>

        <!-- ===== Overlay ===== -->
        <div class="fixed inset-0 z-40 bg-black/60"></div>

        <!-- ===== Modal ===== -->
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8 overflow-y-auto">
            <div class="relative w-full max-w-6xl mx-auto bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-2xl border border-gray-100 flex flex-col md:flex-row overflow-hidden">

                <!-- ═══════════  Wizard / Form  ═══════════-->
                <form id="wizardForm" class="w-full md:w-1/2 h-[85vh] md:h-screen overflow-y-auto bg-white">
                    <!-- ═════════ Header con logo y título ═════════ -->
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 sticky top-0 z-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold mb-1">Solicitud de Transporte</h2>
                                <p class="text-orange-100 text-sm">Complete todos los pasos para procesar su solicitud</p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- ═════════ Barra de progreso mejorada ═════════ -->
                    <div class="px-8 py-6 bg-white border-b border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-600">Progreso</span>
                            <span id="progressText" class="text-sm font-semibold text-orange-600">Paso 1 / 6</span>
                        </div>
                        <div id="progressBar" class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div id="progressBarFill"
                                class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full transition-all duration-500 ease-out shadow-sm"
                                style="width:0%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-xs text-gray-400">Inicio</span>
                            <span class="text-xs text-gray-400">Finalización</span>
                        </div>
                    </div>

                    <div class="p-8">

                    <!-- STEP 1 – DATOS BÁSICOS -->
                    <section id="step-1" class="step space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                1
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Datos Básicos</h3>
                                <p class="text-gray-600 text-sm">Información general del cliente y tipo de servicio</p>
                            </div>
                        </div>
                        
                        <input type="hidden" id="CotizacionModelId" value="{{ $CotizacionModelId }}">

                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                Información del Cliente
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Cliente -->
                                <div class="space-y-2">
                                    <x-input id="cliente_codigo" label="Cliente" type="number" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <!-- Tipo de viaje -->
                                <div class="space-y-2">
                                    <x-select id="tipo_viaje" label="Tipo Viaje"
                                              :options="['NACIONAL','URBANO','DEVOLUCION','EXPORTACION','CONTENEDOR VACIO','ITR','ALMACENAMIENTO','LEGALIZACION','NACIONAL ESPECIAL','INTERNACIONAL','URBANO-ITR']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                </svg>
                                Configuración Comercial
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Moneda -->
                                <div class="space-y-2">
                                    <x-select id="moneda" label="Moneda"
                                              :options="['PESOS','DOLARES','BOLIVARES','BOLIVARES FUERTES']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <!-- Fuente -->
                                <div class="space-y-2">
                                    <x-select id="fuente_solicitud" label="Fuente Solicitud"
                                              :options="['TELEFONO DESPACHADOR','TELEFONO ATENCION CLIENTE','MAIL','FAX','SIA','PAGINA WEB']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <div class="space-y-2">
                                    <x-input  id="condicion_despacho"    label="Condición Despacho" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                                
                                <div class="space-y-2">
                                    <x-input  id="condicion_facturacion" label="Condición Facturación" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                                
                                <div class="space-y-2">
                                    <x-input  id="ciudad_facturacion"    label="Ciudad Facturación" type="number" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                                
                                <div class="space-y-2">
                                    <x-input  id="vendedor"              label="Vendedor"           type="number" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <div class="space-y-2 md:col-span-2">
                                    <x-select id="tipo_operacion" label="Tipo Operación"
                                              :options="['DISTRIBUCION','IMPORTACION','EXPORTACION']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 2 – DETALLE -->
                    <section id="step-2" class="step hidden space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                2
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Detalle del Servicio</h3>
                                <p class="text-gray-600 text-sm">Especificaciones técnicas y logísticas del transporte</p>
                            </div>
                        </div>

                        <!-- Ubicaciones -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                Ubicaciones
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <x-input id="origen"  label="Origen" class="transition-all duration-300 hover:border-blue-300 focus:ring-2 focus:ring-blue-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="destino" label="Destino" class="transition-all duration-300 hover:border-blue-300 focus:ring-2 focus:ring-blue-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Mercancía -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/>
                                </svg>
                                Información de la Mercancía
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-input id="cantidad_mercancia" label="Cantidad Mercancía" type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="peso"               label="Peso (kg)"          type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="valor_mercancia" label="Valor Mercancía" type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="producto" label="Producto" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="empaque"  label="Empaque" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2 md:col-span-2 lg:col-span-1">
                                    <x-input  id="descripcion_mercancia" label="Descripción Mercancía" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Vehículos -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                </svg>
                                Especificaciones del Vehículo
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="space-y-2">
                                    <x-input id="cantidad_vehiculos" label="Cantidad Vehículos" type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="clase_vehiculo"     label="Clase Vehículo"     type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="carroceria"         label="Carrocería"         type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="minimo_modelo"      label="Mínimo Modelo"      type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Tarifas y Flete -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                Tarifas y Costos
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-select id="tipo_flete" label="Tipo Flete"
                                              :options="['CARGA SUELTA','CUPO','CONSOLIDADO','EXPRESO','GALON','VAN','CONTENEDOR']"
                                              class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="flete_conductor"  label="Flete Conductor"   type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="flete_ministerio" label="Flete Ministerio"  type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="tipo_tarifa" label="Tipo Tarifa" :options="['GENERAL','PESO','GALON']" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="tarifa_cliente" label="Tarifa Cliente" type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Responsabilidades -->
                        <div class="bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border border-red-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 0a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1h-6a1 1 0 01-1-1V8z" clip-rule="evenodd"/>
                                </svg>
                                Responsabilidades y Servicios Adicionales
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-select id="cargue_cuenta_de"    label="Cargue por Cuenta de"    :options="['CLIENTE','EMPRESA','DESTINATARIO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="descargue_cuenta_de" label="Descargue por Cuenta de" :options="['CLIENTE','EMPRESA','DESTINATARIO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="seguro_cuenta_de"    label="Seguro por Cuenta de"    :options="['CLIENTE','EMPRESA']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="kit_seguridad"         label="Kit Seguridad" :options="['SI','NO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="sub_cliente" label="Sub Cliente" type="number" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="tipo_remesa_rndc" label="Tipo Remesa RNDC"
                                              :options="['REMESA GENERAL','CONTENEDOR CARGADO','CONTENEDOR VACIO']"
                                              class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="2" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 3 – CARGUE -->
                    <section id="step-3" class="step hidden space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                3
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Información de Cargue</h3>
                                <p class="text-gray-600 text-sm">Detalles sobre la recolección y entrega de la mercancía</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-6 border border-teal-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-6 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Programación y Contactos
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-input id="fecha_cargue" label="Fecha Cargue" type="date" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="hora_cargue"  label="Hora Cargue"  type="time" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="promesa_servicio"     label="Promesa Servicio"     type="date" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="remitente"    label="Remitente"    type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="destinatario" label="Destinatario" type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="contacto"     label="Contacto" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <x-input id="observacion_cargue" label="Observación Cargue" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="documento_transporte" label="Documento Transporte" type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="3" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 4 – CONTENEDOR -->
                    <section id="step-4" class="step hidden space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                4
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Contenedor</h3>
                                <p class="text-gray-600 text-sm">Configuración para transporte en contenedor</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-8 border border-indigo-200 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-700 mb-6">¿El transporte requiere contenedor?</h4>
                            <div class="max-w-md mx-auto">
                                <x-select id="contenedor" label="¿Contenedor?" :options="['SI','NO']" class="text-lg transition-all duration-300 hover:border-indigo-300 focus:ring-2 focus:ring-indigo-500"/>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="4" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 5 – INTERNACIONAL -->
                    <section id="step-5" class="step hidden space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                5
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Modalidad Internacional</h3>
                                <p class="text-gray-600 text-sm">Configuración para comercio internacional</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-700 mb-6">Seleccione la modalidad internacional</h4>
                            <div class="max-w-md mx-auto">
                                <x-select id="modalidad_internacional" label="Modalidad"
                                          :options="['OTM','DTA','DTAI','NACIONALIZADA']"
                                          class="text-lg transition-all duration-300 hover:border-emerald-300 focus:ring-2 focus:ring-emerald-500"/>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="5" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 6 – Acompañamiento -->
                    <section id="step-6" class="step hidden space-y-8 animate-fadeIn">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                6
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Vehículo de Acompañamiento</h3>
                                <p class="text-gray-600 text-sm">Configuración final del servicio de transporte</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl p-8 border border-amber-200">
                            <div class="text-center mb-8">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                    </svg>
                                </div>
                                <h4 class="text-xl font-semibold text-gray-700 mb-2">¿Requiere vehículos de acompañamiento?</h4>
                                <p class="text-gray-600 text-sm">Especifique la cantidad de vehículos adicionales necesarios</p>
                            </div>
                            
                            <div class="max-w-md mx-auto">
                                <x-input id="vehiculo_acom" label="# Vehículos Acompañamiento" type="number" 
                                        class="text-lg transition-all duration-300 hover:border-amber-300 focus:ring-2 focus:ring-amber-500"/>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-700 mb-2">¡Último paso!</h4>
                                <p class="text-gray-600 text-sm mb-6">Revise toda la información antes de enviar su solicitud</p>
                                <x-submit class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-10 py-4 rounded-lg font-bold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                            </div>
                        </div>
                    </section>
                    </div>
                </form>

                <!-- ═══════════  Chat lateral mejorado  ═══════════-->
                <aside class="w-full md:w-1/2 bg-gradient-to-br from-gray-50 to-gray-100 border-t md:border-t-0 md:border-l flex flex-col">
                    <!-- Header del chat -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 text-white p-6 sticky top-0 z-10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold">Asistente IA</h3>
                                <p class="text-gray-300 text-sm">Estoy aquí para ayudarte</p>
                            </div>
                            <div class="flex-1"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Área de mensajes -->
                    <div id="chatbox" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <!-- Mensaje de bienvenida -->
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm border border-gray-200 max-w-xs">
                                <p class="text-gray-700 text-sm">¡Hola! Soy tu asistente IA. Estoy aquí para ayudarte a completar tu solicitud de transporte. ¿En qué puedo ayudarte?</p>
                            </div>
                        </div>
                    </div>

                    <!-- Input área mejorada -->
                    <div class="p-6 border-t border-gray-200 bg-white">
                        <div class="flex items-end space-x-3">
                            <div class="flex-1 relative">
                                <textarea id="userInput"
                                          rows="1"
                                          placeholder="Escribe tu mensaje aquí..."
                                          class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 pr-12 focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all duration-300 text-gray-700 placeholder-gray-400 bg-gray-50 focus:bg-white"></textarea>
                                <div class="absolute right-3 bottom-3 text-xs text-gray-400">
                                    Enter para enviar
                                </div>
                            </div>

                            <button type="button"
                                    onclick="sendMessage()"
                                    class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white flex items-center justify-center hover:from-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span></span>
                            <span class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                <span>En línea</span>
                            </span>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
    <script src="{{ asset('vendor/sortablejs/jquery-sortable.js') }}"></script>
    <script>
        const openaiUri = 'https://api.openai.com/v1';
        const privateToken = '{{ env("OPENAI_API_KEY") }}';
        const assistantStep1 = 'asst_NRyScHbWS5rBZlW3LZ7BjpBx';
        const assistantStep2 = 'asst_4OeigFNuv9712ODPBFJREi5J';
        const assistantStep3 = 'asst_svqD1PUjnJEdlCWVUXXIWcUU';
        const assistantStep4 = 'asst_MsWhUGMTnQIftOVk1oEoq4Ov';
        const assistantStep5 = 'asst_G7qUNTQKvafZvmre0i9iioEk';
        const assistantStep6 = 'asst_cySu9Zr39jiCluhmYSgmpEG6';
        const assistantStep7 = 'asst_9INijQyakJIrtVfYguey5vzF';
        const assistantStep8 = 'asst_ZQJzZV9IuPLNLJaJVFFPNudW';
        const assistantStep9 = 'asst_RogHyE0LmSdJMoORCELLsQrK';
        // const chatbox = document.getElementById('chatbox');
        // const userInput = document.getElementById('userInput');


        // Generar un ID único para el thread
        let threadID = null;
        let currentStep = 1;
        const totalSteps = 6;

        function updateProgressBar () {
            const percent = (currentStep-1) / (totalSteps-1) * 100;  // 0-100
            document.getElementById('progressBarFill').style.width  = percent + '%';
            document.getElementById('progressText').innerText       = `Paso ${currentStep} / ${totalSteps}`;
        }

        document.addEventListener('click', function(event) {
            // Busca si el click fue en un .next-btn (o uno de sus hijos)
            let el = event.target;
            while (el && el !== document.body) {
                if (el.id && (el.id.startsWith('next-btn') || el.id === 'submitButton')) {
                if (currentStep == 1) {
                    var cliente_codigo       = $("#cliente_codigo").val();
                    var tipo_viaje = $("#tipo_viaje").val();
                    var moneda = $("#moneda").val();
                    var fuente_solicitud = $("#fuente_solicitud").val();
                    var condicion_despacho = $("#condicion_despacho").val();
                    var condicion_facturacion = $("#condicion_facturacion").val();
                    var ciudad_facturacion = $("#ciudad_facturacion").val();
                    var vendedor = $("#vendedor").val();
                    var tipo_operacion = $("#tipo_operacion").val();
                    var missingFields = [];
                    if (cliente_codigo == '')     missingFields.push("Cliente codigo");
                    if (tipo_viaje == '') missingFields.push("Tipo de viaje");
                    if (moneda == '') missingFields.push("Moneda");
                    if (fuente_solicitud == '') missingFields.push("Fuente de solicitud");
                    if (condicion_despacho == '') missingFields.push("Condición de despacho");
                    if (condicion_facturacion == '') missingFields.push("Condición de facturación");
                    if (ciudad_facturacion == '') missingFields.push("Ciudad de facturación");
                    if (ciudad_facturacion.length !== 8) { missingFields.push("Ciudad de facturación (8 dígitos)"); }
                    if (vendedor == '') missingFields.push("Vendedor");
                    if (tipo_operacion == '') missingFields.push("Tipo de operación");
                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }
                if (currentStep == 2) {
                    var origen = $("#origen").val();
                    var destino = $("#destino").val();
                    var cantidad_mercancia = $("#cantidad_mercancia").val();
                    var number = $("#number").val();
                    var producto = $("#producto").val();
                    var empaque = $("#empaque").val();
                    var cantidad_vehiculos = $("#cantidad_vehiculos").val();
                    var clase_vehiculo = $("#clase_vehiculo").val();
                    var carroceria = $("#carroceria").val();
                    var minimo_modelo = $("#minimo_modelo").val();
                    var tipo_flete = $("#tipo_flete").val();
                    var flete_conductor = $("#flete_conductor").val();
                    var flete_ministerio = $("#flete_ministerio").val();
                    var tipo_tarifa = $("#tipo_tarifa").val();
                    var tarifa_cliente = $("#tarifa_cliente").val();
                    var valor_mercancia = $("#valor_mercancia").val();
                    var cargue_cuenta_de = $("#cargue_cuenta_de").val();
                    var descargue_cuenta_de = $("#descargue_cuenta_de").val();
                    var seguro_cuenta_de = $("#seguro_cuenta_de").val();
                    var descripcion_mercancia = $("#descripcion_mercancia").val();
                    var sub_cliente = $("#sub_cliente").val();

                    var missingFields = [];

                    if (origen == '') missingFields.push("Origen");
                    if (destino == '') missingFields.push("Destino");
                    if (cantidad_mercancia == '') missingFields.push("Cantidad de mercancía");
                    if (number == '') missingFields.push("Número");
                    if (producto == '') missingFields.push("Producto");
                    if (empaque == '') missingFields.push("Empaque");
                    if (cantidad_vehiculos == '') missingFields.push("Cantidad de vehículos");
                    if (clase_vehiculo == '') missingFields.push("Clase de vehículo");
                    if (carroceria == '') missingFields.push("Carrocería");
                    if (minimo_modelo == '') missingFields.push("Mínimo modelo");
                    if (tipo_flete == '') missingFields.push("Tipo de flete");
                    if (flete_conductor == '') missingFields.push("Flete del conductor");
                    if (flete_ministerio == '') missingFields.push("Flete del ministerio");
                    if (tipo_tarifa == '') missingFields.push("Tipo de tarifa");
                    if (tarifa_cliente == '') missingFields.push("Tarifa del cliente");
                    if (valor_mercancia == '') missingFields.push("Valor de la mercancía");
                    if (cargue_cuenta_de == '') missingFields.push("Cargue por cuenta de");
                    if (descargue_cuenta_de == '') missingFields.push("Descargue por cuenta de");
                    if (seguro_cuenta_de == '') missingFields.push("Seguro por cuenta de");
                    if (descripcion_mercancia == '') missingFields.push("Descripción de la mercancía");
                    if (sub_cliente == '') missingFields.push("Sub cliente");

                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }

                if (currentStep == 3) {
                    var fecha_cargue = $("#fecha_cargue").val();
                    var hora_cargue = $("#hora_cargue").val();
                    var remitente = $("#remitente").val();
                    var observacion_cargue = $("#observacion_cargue").val();
                    var contacto = $("#contacto").val();
                    var destinario = $("#destinario").val();
                    var promesa_servicio = $("#promesa_servicio").val();
                    var promesa_servicio_hora = $("#promesa_servicio_hora").val();
                    var documento_transporte = $("#documento_transporte").val();

                    var missingFields = [];

                    if (fecha_cargue == '') missingFields.push("Fecha de cargue");
                    if (hora_cargue == '') missingFields.push("Hora de cargue");
                    if (remitente == '') missingFields.push("Remitente");
                    if (observacion_cargue == '') missingFields.push("Observación de cargue");
                    if (contacto == '') missingFields.push("Contacto");
                    if (destinario == '') missingFields.push("Destinatario");
                    if (promesa_servicio == '') missingFields.push("Promesa de servicio");
                    if (promesa_servicio_hora == '') missingFields.push("Hora de promesa de servicio");
                    if (documento_transporte == '') missingFields.push("Documento de transporte");

                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }

                if (currentStep == 4) {
                    var tipo_carga_cont = $("#tipo_carga_cont").val();
                    var contenedor = $("#contenedor").val();

                    var missingFields = [];

                    if (tipo_carga_cont == '') missingFields.push("Tipo de carga contenedor");
                    if (contenedor == '') missingFields.push("Contenedor");

                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }

                if (currentStep == 5) {
                    var modalidad_internacional = $("#modalidad_internacional").val();

                    if (modalidad_internacional == '') {
                        alert('El campo "Modalidad Internacional" es requerido.');
                        return
                    }
                }

                if (currentStep == 7) {
                    var condicion_factura_condition = $('#condicion_factura_condition').val();
                    var opcion_condicion_cumplida = $('#opcion_condicion_cumplida').val();

                    var missingFields = [];

                    if (condicion_factura_condition == '') {
                        missingFields.push('Condición de Factura');
                    }

                    if (opcion_condicion_cumplida == '') {
                        missingFields.push('Opción de Condición Cumplida');
                    }

                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }

                if (currentStep == 8) {
                    var vehiculo_acom = $("#vehiculo_acom").val();
                    var tipo_vehiculo_acom = $("#tipo_vehiculo_acom").val();
                    var acompanamiento_cuenta_acom = $("#acompanamiento_cuenta_acom").val();
                    var valor_acompanante_acom = $("#valor_acompanante_acom").val();

                    var missingFields = [];

                    if (vehiculo_acom == '') {
                        missingFields.push('Vehículo de Acompañamiento');
                    }

                    if (tipo_vehiculo_acom == '') {
                        missingFields.push('Tipo de Vehículo');
                    }

                    if (acompanamiento_cuenta_acom == '') {
                        missingFields.push('Acompañamiento por Cuenta de');
                    }

                    if (valor_acompanante_acom == '') {
                        missingFields.push('Valor del Acompañante');
                    }

                    if (missingFields.length > 0) {
                        alert('Los siguientes campos son requeridos: ' + missingFields.join(', '));
                        return
                    }
                }
                    document.getElementById(`step-${currentStep}`).classList.add('hidden');
                    threadID = null
                    currentStep++;
                    if (currentStep <= totalSteps) {
                        document.getElementById(`step-${currentStep}`).classList.remove('hidden');
                    }
                    updateProgressBar(); 
                    break; // Importante para salir del while
                }
                el = el.parentElement;
            }
        });


        // Crear un nuevo thread
        async function createThread() {
            if (!threadID) { // Verificar si el `threadID` ya existe
                try {
                    const response = await fetch(`${openaiUri}/threads`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${privateToken}`,
                            'OpenAI-Beta': 'assistants=v2'
                        }
                    });

                    const data = await response.json();
                    if (data && data.object === 'thread') {
                        threadID = data.id;
                        localStorage.setItem('threadID', threadID); // Guardar en localStorage
                    }
                } catch (error) {
                    console.error('Error al crear el thread:', error);
                }
            }
        }

        // Enviar un mensaje
        async function sendMessage() {
            try {
                const userInput = document.getElementById('userInput');
                if (!userInput) {
                    console.error("No se encontró el input #userInput en el DOM");
                    return;
                }
                const chatbox = document.getElementById('chatbox');
                if (!chatbox) {
                    console.error("No se encontró el chatbox en el DOM");
                    return;
                }
                const userMessage = userInput.value;

                if (!threadID) {
                    await createThread(); // Crear el thread si no existe
                }

                // Mostrar el mensaje del usuario en la interfaz
                const userMessageElement = document.createElement('div');
                userMessageElement.className = 'message-bubble userMessage';
                userMessageElement.textContent = userMessage;
                chatbox.appendChild(userMessageElement);
                userInput.value = '';

                // Enviar el mensaje a OpenAI
                const response = await fetch(`${openaiUri}/threads/${threadID}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    },
                    body: JSON.stringify({
                        role: 'user',
                        content: userMessage
                    })
                });

                const data = await response.json();
                if (data) {
                    // Ejecutar el asistente después de enviar el mensaje
                    await runAssistant();
                }
            } catch (error) {
                console.error('Error al enviar mensaje:', error);
            }
        }

        async function checkRunStatus(runId) {
            try {
                const response = await fetch(`${openaiUri}/threads/${threadID}/runs/${runId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    }
                });

                const data = await response.json();

                if (data.status === 'completed') {
                    // Llamar a getMessages y procesar los mensajes
                    const messagesResponse = await getMessages();
                    const messages = messagesResponse.data;

                    // Encontrar el primer mensaje del bot
                    const botMessage = messages.find(message => message.role === 'assistant');

                    if (botMessage) {
                        // Extraer el texto del primer mensaje del bot
                        const botMessageText = botMessage.content.find(contentItem => contentItem.type === 'text').text
                            .value;

                        // Crear el elemento para mostrar el mensaje del bot
                        const botMessageElement = document.createElement('div');
                        botMessageElement.className = 'message-bubble botMessage';
                        // botMessageElement.textContent = botMessageText;
                        botMessageElement.innerHTML = botMessageText.replace(/\n/g, '<br>');
                        chatbox.appendChild(botMessageElement);
                    }
                } else if (data.status === 'requires_action' && data.required_action.type === 'submit_tool_outputs') {
                    const toolCallId = data.required_action.submit_tool_outputs.tool_calls[0].id;
                    const toolArguments = data.required_action.submit_tool_outputs.tool_calls[0].function.arguments;
                    if (currentStep == 1) {
                        llenarFormulario(toolArguments)
                    }
                    if (currentStep == 2) {
                        llenarFormulario2(toolArguments)
                    }
                    if (currentStep == 3) {
                        llenarFormulario3(toolArguments)
                    }
                    if (currentStep == 4) {
                        llenarFormulario4(toolArguments)
                    }
                    if (currentStep == 5) {
                        llenarFormulario5(toolArguments)
                    }
                    if (currentStep == 6) {
                        llenarFormulario6(toolArguments)
                    }
                    if (currentStep == 7) {
                        llenarFormulario7(toolArguments)
                    }
                    if (currentStep == 8) {
                        llenarFormulario8(toolArguments)
                    }
                    if (currentStep == 9) {
                        llenarFormulario9(toolArguments)
                    }
                } else if (data.status === 'failed') {
                    console.error("El run falló:", data);
                } else {
                    // Esperar un tiempo antes de volver a verificar
                    setTimeout(() => checkRunStatus(runId), 5000); // Espera 5 segundos antes de volver a verificar
                }
            } catch (error) {
                console.error('Error al verificar el estado del run:', error);
            }
        }
        // Ejecutar el asistente y verificar el estado del run
        async function runAssistant() {
            try {
                let assistantId;
                switch (currentStep) {
                    case 1:
                        assistantId = assistantStep1;
                        break;
                    case 2:
                        assistantId = assistantStep2;
                        break;
                    case 3:
                        assistantId = assistantStep3;
                        break;
                    case 4:
                        assistantId = assistantStep4;
                        break;
                    case 5:
                        assistantId = assistantStep5;
                        break;
                    case 6:
                        assistantId = assistantStep6;
                        break;
                    case 7:
                        assistantId = assistantStep7;
                        break;
                    case 8:
                        assistantId = assistantStep8;
                        break;
                    case 9:
                        assistantId = assistantStep9;
                        break;
                    default:
                        throw new Error("Paso actual no válido.");
                }
                const response = await fetch(`${openaiUri}/threads/${threadID}/runs`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    },
                    body: JSON.stringify({
                        assistant_id: assistantId
                    })
                });

                const data = await response.json();

                if (data && data.status === 'queued') {
                    // Verificar el estado del run
                    checkRunStatus(data.id);
                } else {
                    console.error("Error en la ejecución del asistente:", data);
                }
            } catch (error) {
                console.error('Error al ejecutar el asistente:', error);
            }
        }

        async function getMessages() {
            try {
                const response = await fetch(`${openaiUri}/threads/${threadID}/messages`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    }
                });

                return await response.json();
            } catch (error) {
                console.error('Error al obtener los mensajes:', error);
            }
        }

        function llenarFormulario(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('tipo_viaje', toolArguments.tipo_viaje);
            setValue('moneda', toolArguments.moneda);
            setValue('fuente_solicitud', toolArguments.fuente_solicitud);
            setValue('observacion', toolArguments.observacion);
            setValue('condicion_despacho', toolArguments.condicion_despacho);
            setValue('condicion_facturacion', toolArguments.condicion_facturacion);
            setValue('observacion_remesa', toolArguments.observacion_remesa);
            setValue('tipo_imagen', toolArguments.tipo_imagen);
            setValue('recomendacion_trafico', toolArguments.recomendado_trafico);
            setValue('ciudad_facturacion', toolArguments.ciudad_facturacion);
            setValue('vendedor', toolArguments.vendedor);
            setValue('cliente_final', toolArguments.cliente_final);
            setValue('tipo_operacion', toolArguments.tipo_operacion);
            setValue('fecha_solicitud', toolArguments.fecha_solicitud);
            setValue('instrucciones_servicio', toolArguments.instrucciones_servicio);
            setValue('maersk_numero_viaje', toolArguments.maersk_numero_viaje);
            setValue('solicitud_servicio', toolArguments.solicitud_servicio);
            setValue('mostrar_digitalizados_vehiculos', toolArguments.digitalizados_web_vehiculo);
            setValue('mostrar_digitalizados_conductor', toolArguments.digitalizados_web_conductor);
            setValue('usuario_autorizado', toolArguments.usuario_autorizado);
            setValue('empresa', toolArguments.empresa);
            setValue('usuario', toolArguments.usuario);
        }

        function llenarFormulario2(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue("ciudad_intermedia", toolArguments.ciudad_intermedia)
            setValue("peso_despachado", toolArguments.peso_despachado)
            setValue("cantidad_vehiculos_despachados", toolArguments.cantidad_vehiculos_despachados)
            setValue("clase_vehiculo", toolArguments.clase_vehiculo)
            setValue("carroceria", toolArguments.carroceria)
            setValue("minimo_modelo", toolArguments.minimo_modelo)
            setValue("tipo_flete", toolArguments.tipo_flete)
            setValue("flete_conductor", toolArguments.flete_conductor)
            setValue("flete_ministerio", toolArguments.flete_ministerio)
            setValue("tipo_tarifa", toolArguments.tipo_tarifa)
            setValue("tarifa_cliente", toolArguments.tarifa_cliente)
            setValue("tipo_documento", toolArguments.tipo_documento)
            setValue("numero_documento", toolArguments.numero_documento)
            setValue("valor_mercancia", toolArguments.valor_mercancia)
            setValue("restricciones_cliente", toolArguments.restricciones_cliente)
            setValue("cargue_cuenta_de", toolArguments.cargue_cuenta_de)
            setValue("descargue_cuenta_de", toolArguments.descargue_cuenta_de)
            setValue("descripcion_mercancia", toolArguments.descripcion_mercancia)
            setValue("servicio_escolta", toolArguments.servicio_escolta)
            setValue("kit_seguridad", toolArguments.kit_seguridad)
            setValue("kit_cinchas", toolArguments.kit_cinchas)
            setValue("guia_acompanamiento", toolArguments.guia_acompanamiento)
            setValue("observacion_detalle", toolArguments.observacion_detalle)
            setValue("sub_cliente", toolArguments.sub_cliente)
            setValue("guia_despacho", toolArguments.guia_despacho)
            setValue("numero_viaje", toolArguments.numero_viaje)
            setValue("numero_pedido", toolArguments.numero_pedido)
            setValue("nota_entrega", toolArguments.nota_entrega)
            setValue("planilla_entrega", toolArguments.planilla_entrega)
            setValue("numero_factura", toolArguments.numero_factura)
            setValue("modelo", toolArguments.modelo)
            setValue("qty", toolArguments.qty)
            setValue("t_cbm", toolArguments.t_cbm)
            setValue("order_no", toolArguments.order_no)
            setValue("o_c_cliente", toolArguments.o_c_cliente)
            setValue("bodega", toolArguments.bodega)
            setValue("fecha_expiracion", toolArguments.fecha_expiracion)
            setValue("net_amt_txn", toolArguments.net_amt_txn)
            setValue("prec_unit", toolArguments.prec_unit)
            setValue("remark", toolArguments.remark)
            setValue("addr", toolArguments.addr)
            setValue("appoint_date", toolArguments.appoint_date)
            setValue("tipo_vehiculo", toolArguments.tipo_vehiculo)
            setValue("item", toolArguments.item)
            setValue("load", toolArguments.load)
            setValue("seguro_cuenta_de", toolArguments.seguro_cuenta_de)

        }

        function llenarFormulario3(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('fecha_cargue', toolArguments.fecha_cargue)
            setValue('hora_cargue', toolArguments.hora_cargue)
            setValue('remitente', toolArguments.remitente)
            setValue('direccion_cargue', toolArguments.direccion_cargue)
            setValue('observacion_cargue', toolArguments.observacion_cargue)
            setValue('contacto', toolArguments.contacto)
            setValue('destinario', toolArguments.destinario)
            setValue('promesa_servicio', toolArguments.promesa_servicio)
            setValue('documento_transporte', toolArguments.documento_transporte)
            setValue('manifiesto_cliente', toolArguments.manifiesto_cliente)
            setValue('remesa_cliente', toolArguments.remesa_cliente)
            setValue('remision_cliente', toolArguments.remision_cliente)
            setValue('codigo_entrega', toolArguments.codigo_entrega)
            setValue('email', toolArguments.email)
        }

        function llenarFormulario4(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('tipo_carga_cont', toolArguments.tipo_carga_cont)
            setValue('tamano_contenedor', toolArguments.tamano_contenedor)
            setValue('sitio_entrega_cont', toolArguments.sitio_entrega_cont)
            setValue('cantidad_cont', toolArguments.cantidad_cont)
            setValue('peso_contenedor', toolArguments.peso_contenedor)
            setValue('tipo_contenedor', toolArguments.tipo_contenedor)
            setValue('fecha_entrega_cont', toolArguments.fecha_entrega_cont)
            setValue('contenedor', toolArguments.contenedor)
            setValue('numero_cont', toolArguments.numero_cont)
        }

        function llenarFormulario5(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('modalidad_internacional', toolArguments.modalidad_internacional)
            setValue('tipo_viaje_int', toolArguments.tipo_viaje)
            setValue('numero_documento_int', toolArguments.numero_documento_int)
            setValue('fecha_llegada_int', toolArguments.fecha_llegada_int)
            setValue('aduana_int', toolArguments.aduana_int)
            setValue('nombre_cliente', toolArguments.nombre_cliente)
            setValue('nombre_exportador', toolArguments.nombre_exportador)
            setValue('datos_agente_aduana', toolArguments.datos_agente_aduana)
            setValue('datos_bodega_ingresa', toolArguments.datos_bodega_ingresa)
            setValue('ciudad_int', toolArguments.ciudad_int)
            setValue('tipo_operacion_int', toolArguments.tipo_operacion)
            setValue('quien_paga_almacenamiento', toolArguments.quien_paga_almacenamiento)
            setValue('ciudad_otra', toolArguments.ciudad_otra)
            setValue('tipo_otro', toolArguments.tipo_otro)
            setValue('nombre_importador', toolArguments.nombre_importador)
            setValue('descripcion_mercancia', toolArguments.descripcion_mercancia)
            setValue('cantidad_peso_mercancia', toolArguments.cantidad_peso_mercancia)
            setValue('fecha_vencimiento_modalidad', toolArguments.fecha_vencimiento_modalidad)
            setValue('paso_frontera', toolArguments.paso_frontera)



        }

        function llenarFormulario6(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('articulo_1', toolArguments.articulo_1)
            setValue('cantidad_1', toolArguments.cantidad_1)
            setValue('articulo_2', toolArguments.articulo_2)
            setValue('cantidad_2', toolArguments.cantidad_2)
            setValue('articulo_3', toolArguments.articulo_3)
            setValue('cantidad_3', toolArguments.cantidad_3)
            setValue('articulo_4', toolArguments.articulo_4)
            setValue('cantidad_4', toolArguments.cantidad_4)
        }

        function llenarFormulario7(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('condicion_factura_condition', toolArguments.condicion_factura)
            setValue('factura_remesa_hija', toolArguments.factura_remesa_hija)
            setValue('opcion_factura_remesa', toolArguments.opcion_factura_remesa)
            setValue('opcion_condicion_cumplida', toolArguments.opcion_condicion_cumplida)
        }

        function llenarFormulario8(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('vehiculo_acom', toolArguments.vehiculo_acom)
            setValue('tipo_vehiculo_acom', toolArguments.tipo_vehiculo_acom)
            setValue('acompanamiento_cuenta_acom', toolArguments.acompanamiento_cuenta_acom)
            setValue('valor_acompanante_acom', toolArguments.valor_acompanante_acom)
        }

        function llenarFormulario9(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };


            setValue('nombre_cliente_ent', toolArguments.nombre_cliente_ent)
            setValue('direccion_entrega_ent', toolArguments.direccion_entrega_ent)
            setValue('numero_documento_ent', toolArguments.numero_documento_ent)
            setValue('cantidad_ent', toolArguments.cantidad_ent)
            setValue('empaque_ent', toolArguments.empaque_ent)
            setValue('f_12_ent', toolArguments.f_12_ent)

        }
        // Asegúrate de que la función sendMessage esté accesible globalmente
        window.sendMessage = sendMessage;

        // Event listener para el input del chat
        document.addEventListener('DOMContentLoaded', function() {
            const userInput = document.getElementById('userInput');
            if (userInput) {
                userInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $(document).on('click', '#sendInfo', function(e) {

                // Mapeo de nombres de colores a valores hexadecimales
                var colores = {
                    "rojo": "#F4A6A6", // Rojo tenue
                    "verde": "#B4D7B1", // Verde tenue
                    "azul": "#A2B8D9", // Azul tenue
                    "amarillo": "#F7E5B0", // Amarillo tenue
                    "negro": "#DADADA", // Negro tenue
                    "blanco": "#FFFFFF", // Blanco
                    "gris": "#C4C4C4" // Gris tenue
                    // Añadir más colores según sea necesario
                };

                // Obtén los valores del formulario
                var nombre = $('input[name="nombre"]').val();
                var nombreColor = $('input[name="color"]').val().toLowerCase();

                // Obtén el valor hexadecimal del color usando el nombre del color
                var color = colores[nombreColor] ||
                    '#FFFFFF'; // Usa blanco como valor predeterminado si el color no está en el mapeo

                // Crea el nuevo elemento padre
                var nuevoPadre = $('<div>').addClass('flex flex-col items-start justify-center mr-2').attr(
                    'draggable', 'true');

                var boton = $('<button>').addClass(
                        'flex flex-col px-6 pt-[1.62rem] pb-6 w-full md:w-[17rem] h-[5.562rem] rounded-xl text-left relative mb-1'
                    )
                    .css('background-color', color)
                    .append($('<h6>').addClass('text-[#202020] text-base font-medium leading-normal')
                        .text(nombre))
                    .append($('<span>').addClass('text-[#898989] text-sm font-normal leading-normal').text(
                        'Canal Creado'));

                var hijos = $('<div>').addClass('sortable-group-child')
                    .append($('<hr>').addClass(
                        ' text-[#202020] flex flex-row pl-[1.81rem] pt-[0.87rem] pb-[0.94rem] w-full md:w-[17rem] h-[0.100rem] rounded-xl bg-white text-left relative shadow-sm items-center justify-start gap-[1.13rem] non-draggable'
                    ));

                // Agrega el botón y los hijos al nuevo elemento padre
                nuevoPadre.append(boton);
                nuevoPadre.append(hijos);

                // Agrega el nuevo elemento padre al contenedor principal
                $('#printCanal').append(nuevoPadre);
                makeSortable();

                // Cierra el modal
                $('#closeModalSolicitud').modal('hidden'); // Método de Bootstrap para ocultar el modal
            });



            $('.openModalCanal1').click(function() {
                if ($('#openModalCanal2').hasClass('hidden')) {
                    $('#openModalCanal2').removeClass('hidden');
                } else {
                    $('#openModalCanal2').addClass('hidden');
                }
            });

            $('#closeModalSolicitud').click(function() {
                location.reload()
            })

            $('#closeModalSolicitud2').click(function() {
                location.reload()
            })

            $('.openModalSolicitud').click(function() {
                if ($('#openModalShow').hasClass('hidden')) {
                    var movedElement = $(this);
                    var quoteData = movedElement.attr('quote-data');
                    var quoteObject = JSON.parse(quoteData);
                    var fields = [{
                            label: 'Fecha de Cargue',
                            value: quoteObject.cargue.fecha_cargue
                        },
                        {
                            label: 'Hora de Cargue',
                            value: quoteObject.cargue.hora_cargue
                        },
                        {
                            label: 'Remitente',
                            value: quoteObject.cargue.remitente
                        },
                        {
                            label: 'Dirección de Cargue',
                            value: quoteObject.cargue.direccion_cargue
                        },
                        {
                            label: 'Observación de Cargue',
                            value: quoteObject.cargue.observacion_cargue
                        },
                        {
                            label: 'Contacto',
                            value: quoteObject.cargue.contacto
                        },
                        {
                            label: 'Destinatario',
                            value: quoteObject.cargue.destinario
                        },
                        {
                            label: 'Promesa de Servicio',
                            value: quoteObject.cargue.promesa_servicio
                        },
                        {
                            label: 'Documento de Transporte',
                            value: quoteObject.cargue.documento_transporte
                        },
                        {
                            label: 'Manifiesto del Cliente',
                            value: quoteObject.cargue.manifiesto_cliente
                        },
                        {
                            label: 'Remesa del Cliente',
                            value: quoteObject.cargue.remesa_cliente
                        },
                        {
                            label: 'Remisión del Cliente',
                            value: quoteObject.cargue.remision_cliente
                        },
                        {
                            label: 'Código de Entrega',
                            value: quoteObject.cargue.codigo_entrega
                        },
                    ];
                    var cargaPrint = document.getElementById('carga_print');

                    fields.forEach(function(field) {
                        var smallElement = document.createElement('small');
                        smallElement.className =
                            'text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]';
                        smallElement.textContent = field.label + ': ' + field.value;
                        cargaPrint.appendChild(smallElement);

                        // Create a line break
                        var lineBreak = document.createElement('br');
                        cargaPrint.appendChild(lineBreak);
                    });
                    $("#client_name").html(quoteObject.client.name)
                    $("#client_name_2").html(quoteObject.client.name)
                    $("#silogtran_status").html(quoteObject.silogtran_status)
                    $('#id_de_factura').html(quoteObject.id)
                    $('#origen_print').html(quoteObject.origen)
                    $('#destino_print').html(quoteObject.destino)
                    $('#fecha_creacion_print').html(quoteObject.created_at)
                    $('#id_print').html(quoteObject.id) 

                    $('#origin_input_print').val(quoteObject.detalle.origen)
                    $('#tipo_carga_input_print').val(quoteObject.detalle.empaque)
                    $('#producto_input_print').val(quoteObject.detalle.producto)
                    $('#destino_input_print').val(quoteObject.detalle.destino)
                    $('#peso_input_print').val(quoteObject.detalle.peso)
                    $('#cantidad_vehiculos_input_print').val(quoteObject.detalle.cantidad_vehiculos)
                    $('#tipo_vehiculo_input_print').val(quoteObject.detalle.clase_vehiculo)
                    $('#carroceria_input_print').val(quoteObject.detalle.carroceria)
                    $('#modelo_input_print').val(quoteObject.detalle.minimo_modelo)
                    $('#tipo_tarifa_input_print').val(quoteObject.detalle.tipo_tarifa)
                    $('#valor_mercancia_input_print').val(quoteObject.detalle.valor_mercancia)
                    $('#descripcion_mercancia_input_print').html(quoteObject.detalle.descripcion_mercancia)
                    $('#openModalShow').removeClass('hidden');
                } else {
                    $('#openModalShow').addClass('hidden');
                }
            });

        });
    </script>
    <script>
        // Crear una instancia de un observador de mutaciones
        const observer = new MutationObserver((mutationsList, observer) => {
            // Recorrer todas las mutaciones que acaban de suceder
            for (let mutation of mutationsList) {
                // Si el nodo agregado es un elemento
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    const newNode = mutation.addedNodes[0];
                    if (newNode.id === 'voiceElement') {
                        const startButton = document.getElementById('startButton');
                        const stopButton = document.getElementById(
                            'stopButton'); // Obtener referencia al botón de detener
                        const outputDiv = document.getElementById('input_message');
                        const recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition || window
                            .mozSpeechRecognition || window.msSpeechRecognition)();
                        recognition.lang = 'es-ES'; // Cambiar a español de España
                        recognition.continuous = true; // Configurar para que el reconocimiento sea continuo
                        recognition.interimResults = true;

                        recognition.onresult = (event) => {
                            outputDiv.value = "";
                            let transcript = event.results;
                            for (let index = 0; index < transcript.length; index++) {
                                if (transcript[index].isFinal) {
                                    outputDiv.value += transcript[index][0].transcript;
                                }
                            }
                            const event1 = new Event('input', {
                                bubbles: true,
                                cancelable: true,
                            });
                            outputDiv.dispatchEvent(event1);
                        };

                        startButton.addEventListener('click', () => {
                            recognition.start();
                        });

                        stopButton.addEventListener('click', () => {
                            recognition.stop();
                        });


                    }

                }
            }
        });

        // Iniciar la observación del documento con una configuración específica
        observer.observe(document, {
            childList: true,
            subtree: true
        });
    </script>
    <script>
        function redirectToAICalls() {
            // Obtener el ID de cotización desde el span
            let cotizacionId = document.getElementById('id_print').innerText;
            
            // Validar que el ID no esté vacío
            if (!cotizacionId) {
                alert("No hay un ID de cotización disponible.");
                return;
            }

            // Redirigir a la vista con el ID en la URL
            window.location.href = `/ai-calls?cotizacion_id=${cotizacionId}`;
        }
    </script>
    <script>
        /* -----------------------------  WIZARD + AJAX (debug)  ----------------------------- */
        $(function () {

            /* Qué botón pertenece a qué paso y qué campos se envían */
            const STEP_CONFIG = {
                1:{btn:'#next-btn',     step:'step_1', fields:[
                    'tipo_viaje','moneda','fuente_solicitud','condicion_despacho',
                    'condicion_facturacion','ciudad_facturacion','vendedor','tipo_operacion',
                    'centro_costo_despacho',,'cliente_codigo' 
                ]},
                2:{btn:'#next-btn-2',   step:'step_2', fields:[
                    'origen','destino','cantidad_mercancia','peso','producto','empaque',
                    'cantidad_vehiculos','clase_vehiculo','carroceria','minimo_modelo','tipo_flete',
                    'flete_conductor','flete_ministerio','tipo_tarifa','tarifa_cliente','valor_mercancia',
                    'cargue_cuenta_de','descargue_cuenta_de','seguro_cuenta_de','descripcion_mercancia',
                    'kit_seguridad','sub_cliente','tipo_remesa_rndc'
                ]},
                3:{btn:'#next-btn-3',   step:'step_3', fields:[
                    'fecha_cargue','hora_cargue','remitente','observacion_cargue','contacto',
                    'destinatario','promesa_servicio','documento_transporte'
                ]},
                4:{btn:'#next-btn-4',   step:'step_4', fields:['contenedor']},
                5:{btn:'#next-btn-5',   step:'step_5', fields:['modalidad_internacional']},
                6:{btn:'#submitButton', step:'step_6', fields:['vehiculo_acom']}
            };

            /* ----------------   función que dispara la petición AJAX   ---------------- */
            function enviarPaso(el, cfg)
            {
                const data = {
                    step:              cfg.step,
                    CotizacionModelId: $('#CotizacionModelId').val()
                };

                cfg.fields.forEach(f => data[f] = $('#'+f).val());


                $.ajax({
                    url:     '/solicitud/progreso',
                    method:  'POST',
                    data:    data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

                    success : function (resp) {
                        /* ========= DEBUG: lo que contestó el back-end ========= */

                        if (resp.estado === 'completada') {
                            alert('¡Solicitud completada!');
                            location.reload();
                        }
                        /* ─── detectar error Silogtran ─── */
                        if (resp.estado === 'error') {
                            alert('⚠️  Silogtran devolvió un error:\n\n' + resp.advertencia);

                            /* Volvemos visualmente al paso 6 */
                            currentStep = 6;
                            document.querySelectorAll('.step')
                                    .forEach(s => s.classList.add('hidden'));
                            document.getElementById('step-6').classList.remove('hidden');
                            updateProgressBar();      // si ya añadiste la barra de progreso

                            return;                   // no continúes con flujo normal
                        }
                        /* ──────────────────────────────── */
                    },
                    error   : function (xhr, textStatus) {
                        /* ========= DEBUG: error completo ========= */
                        console.error('❌ Error en la petición',
                                    {status: xhr.status, statusText: xhr.statusText, body: xhr.responseText});
                        alert('Error guardando el paso.');
                    }
                });
            }

            /* Un listener por cada botón definido arriba */
            Object.values(STEP_CONFIG).forEach(cfg => {
                $(document).on('click', cfg.btn, function (e) {
                    e.preventDefault();              // no recarga la página
                    enviarPaso(this, cfg);           // this = botón clicado
                });
            });

        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Escuchar el evento global disparado desde React
            window.addEventListener('openSolicitudTransporteModal', function(e) {
                // e.detail contiene el id de la CotizacionModel (route/quote)
                let id = e.detail;
                // Puedes almacenar el id, o triggers más cosas si lo necesitas
                // Llama a un método Livewire que esté escuchando (drog-zone)
                Livewire.dispatch('abrirModalSolicitudTransporte', { id: id });
            });
        });


        function fillFormWithSolicitudData() {
            if(!window.solicitudData || !window.solicitudData.solicitud) return;

            // Paso 1 (datos principales, tabla principal)
            const s = window.solicitudData.solicitud;
            // $('#tipo_viaje').val(s.tipo_viaje || "");
            // $('#moneda').val(s.moneda || "");
            $('#cliente_codigo').val(s.cliente_codigo || "");
            $('#tipo_viaje').val(s.tipo_viaje || '').trigger('change');
            $('#moneda').val(s.moneda || '').trigger('change');
            // $('#fuente_solicitud').val(s.fuente_solicitud || "");
            $('#fuente_solicitud').val(s.fuente_solicitud || '').trigger('change');
            $('#observacion').val(s.observacion || "");
            $('#condicion_despacho').val(s.condicion_despacho || "");
            $('#condicion_facturacion').val(s.condicion_facturacion || "");
            $('#observacion_remesa').val(s.observacion_remesa || "");
            $('#tipo_imagen').val(s.tipo_imagen || "");
            $('#recomendacion_trafico').val(s.recomendacion_trafico || "");
            $('#ciudad_facturacion').val(s.ciudad_facturacion || "");
            $('#vendedor').val(s.vendedor || "");
            $('#cliente_final').val(s.cliente_final || "");
            $('#tipo_operacion').val(s.tipo_operacion || "");
            $('#fecha_solicitud').val(s.fecha_solicitud || "");
            $('#instrucciones_servicio').val(s.instrucciones_servicio || "");
            $('#maersk_numero_viaje').val(s.maersk_numero_viaje || "");
            $('#solicitud_servicio').val(s.solicitud_servicio || "");
            $('#mostrar_digitalizados_vehiculos').val(s.mostrar_digitalizados_vehiculos || "");
            $('#mostrar_digitalizados_conductor').val(s.mostrar_digitalizados_conductor || "");
            $('#usuario_autorizado').val(s.usuario_autorizado || "");
            $('#empresa').val(s.empresa || "");
            $('#usuario').val(s.usuario || "");

            // Paso 2: Detalle
            if(window.solicitudData.detalle) {
                const d = window.solicitudData.detalle;
                $('#origen').val(d.origen || "");
                $('#destino').val(d.destino || "");
                $('#ciudad_intermedia').val(d.ciudad_intermedia || "");
                $('#cantidad_mercancia').val(d.cantidad_mercancia || "");
                $('#peso').val(d.peso || "");
                $('#peso_despachado').val(d.peso_despachado || "");
                $('#producto').val(d.producto || "");
                $('#empaque').val(d.empaque || "");
                $('#cantidad_vehiculos').val(d.cantidad_vehiculos || "");
                $('#cantidad_vehiculos_despachados').val(d.cantidad_vehiculos_despachados || "");
                $('#clase_vehiculo').val(d.clase_vehiculo || "");
                $('#carroceria').val(d.carroceria || "");
                $('#minimo_modelo').val(d.minimo_modelo || "");
                $('#tipo_flete').val(d.tipo_flete || "");
                $('#flete_conductor').val(d.flete_conductor || "");
                $('#flete_ministerio').val(d.flete_ministerio || "");
                $('#tipo_tarifa').val(d.tipo_tarifa || "");
                $('#tarifa_cliente').val(d.tarifa_cliente || "");
                $('#tipo_documento').val(d.tipo_documento || "");
                $('#numero_documento').val(d.numero_documento || "");
                $('#valor_mercancia').val(d.valor_mercancia || "");
                $('#restricciones_cliente').val(d.restricciones_cliente || "");
                $('#cargue_cuenta_de').val(d.cargue_cuenta_de || "");
                $('#descargue_cuenta_de').val(d.descargue_cuenta_de || "");
                $('#seguro_cuenta_de').val(d.seguro_cuenta_de || "");
                $('#descripcion_mercancia').val(d.descripcion_mercancia || "");
                $('#servicio_escolta').val(d.servicio_escolta || "");
                $('#kit_seguridad').val(d.kit_seguridad || "");
                $('#kit_cinchas').val(d.kit_cinchas || "");
                $('#guia_acompanamiento').val(d.guia_acompanamiento || "");
                $('#observacion_detalle').val(d.observacion_detalle || "");
                $('#sub_cliente').val(d.sub_cliente || "");
                $('#guia_despacho').val(d.guia_despacho || "");
                $('#numero_viaje').val(d.numero_viaje || "");
                $('#numero_pedido').val(d.numero_pedido || "");
                $('#nota_entrega').val(d.nota_entrega || "");
                $('#planilla_entrega').val(d.planilla_entrega || "");
                $('#numero_factura').val(d.numero_factura || "");
                $('#modelo').val(d.modelo || "");
                $('#qty').val(d.qty || "");
                $('#t_cbm').val(d.t_cbm || "");
                $('#order_no').val(d.order_no || "");
                $('#o_c_cliente').val(d.o_c_cliente || "");
                $('#bodega').val(d.bodega || "");
                $('#fecha_expiracion').val(d.fecha_expiracion || "");
                $('#net_amt_txn').val(d.net_amt_txn || "");
                $('#prec_unit').val(d.prec_unit || "");
                $('#remark').val(d.remark || "");
                $('#addr').val(d.addr || "");
                $('#appoint_date').val(d.appoint_date || "");
                $('#tipo_vehiculo').val(d.tipo_vehiculo || "");
                $('#item').val(d.item || "");
                $('#load').val(d.load || "");
            }
            // Paso 3: Cargue
            if(window.solicitudData.cargue) {
                const c = window.solicitudData.cargue;
                $('#fecha_cargue').val(c.fecha_cargue || "");
                $('#hora_cargue').val(c.hora_cargue || "");
                $('#remitente').val(c.remitente || "");
                $('#direccion_cargue').val(c.direccion_cargue || "");
                $('#observacion_cargue').val(c.observacion_cargue || "");
                $('#contacto').val(c.contacto || "");
                $('#destinario').val(c.destinario || "");
                        $('#promesa_servicio').val(c.promesa_servicio || "");
                $('#promesa_servicio_hora').val(c.promesa_servicio_hora || "");
                $('#documento_transporte').val(c.documento_transporte || "");
                $('#manifiesto_cliente').val(c.manifiesto_cliente || "");
                $('#remesa_cliente').val(c.remesa_cliente || "");
                $('#remision_cliente').val(c.remision_cliente || "");
                $('#codigo_entrega').val(c.codigo_entrega || "");
                $('#email').val(c.email || "");
            }
            // Paso 4: Contenedor
            if(window.solicitudData.contenedor) {
                const cont = window.solicitudData.contenedor;
                $('#tipo_carga_cont').val(cont.tipo_carga_cont || "");
                $('#tamano_contenedor').val(cont.tamano_contenedor || "");
                $('#sitio_entrega_cont').val(cont.sitio_entrega_cont || "");
                $('#cantidad_cont').val(cont.cantidad_cont || "");
                $('#peso_contenedor').val(cont.peso_contenedor || "");
                $('#tipo_contenedor').val(cont.tipo_contenedor || "");
                $('#fecha_entrega_cont').val(cont.fecha_entrega_cont || "");
                $('#contenedor').val(cont.contenedor || "");
                $('#numero_cont').val(cont.numero_cont || "");
            }
            // Paso 5: Internacional
            if(window.solicitudData.internacional) {
                const i = window.solicitudData.internacional;
                $('#modalidad_internacional').val(i.modalidad_internacional || "");
                $('#tipo_viaje_int').val(i.tipo_viaje_int || "");
                $('#numero_documento_int').val(i.numero_documento_int || "");
                $('#fecha_llegada_int').val(i.fecha_llegada_int || "");
                $('#aduana_int').val(i.aduana_int || "");
                $('#nombre_cliente').val(i.nombre_cliente || "");
                $('#nombre_exportador').val(i.nombre_exportador || "");
                $('#datos_agente_aduana').val(i.datos_agente_aduana || "");
                $('#datos_bodega_ingresa').val(i.datos_bodega_ingresa || "");
                $('#ciudad_int').val(i.ciudad_int || "");
                $('#tipo_operacion_int').val(i.tipo_operacion_int || "");
                $('#quien_paga_almacenamiento').val(i.quien_paga_almacenamiento || "");
                $('#ciudad_otra').val(i.ciudad_otra || "");
                $('#tipo_otro').val(i.tipo_otro || "");
                $('#nombre_importador').val(i.nombre_importador || "");
                $('#descripcion_mercancia_int').val(i.descripcion_mercancia || "");
                $('#cantidad_peso_mercancia').val(i.cantidad_peso_mercancia || "");
                $('#fecha_vencimiento_modalidad').val(i.fecha_vencimiento_modalidad || "");
                $('#paso_frontera').val(i.paso_frontera || "");
            }
            // Paso 6: Equipos
            if(window.solicitudData.equipos) {
                const eq = window.solicitudData.equipos;
                $('#articulo_1').val(eq.articulo_1 || "");
                $('#cantidad_1').val(eq.cantidad_1 || "");
                $('#articulo_2').val(eq.articulo_2 || "");
                $('#cantidad_2').val(eq.cantidad_2 || "");
                $('#articulo_3').val(eq.articulo_3 || "");
                $('#cantidad_3').val(eq.cantidad_3 || "");
                $('#articulo_4').val(eq.articulo_4 || "");
                $('#cantidad_4').val(eq.cantidad_4 || "");
            }
            // Paso 7: Condiciones Factura
            if(window.solicitudData.condiciones) {
                const cf = window.solicitudData.condiciones;
                $('#condicion_factura_condition').val(cf.condicion_factura || "");
                $('#factura_remesa_hija').val(cf.factura_remesa_hija || "");
                $('#opcion_factura_remesa').val(cf.opcion_factura_remesa || "");
                $('#opcion_condicion_cumplida').val(cf.opcion_condicion_cumplida || "");
            }
            // Paso 8: Acompañamiento
            if(window.solicitudData.acompanamiento) {
                const ac = window.solicitudData.acompanamiento;
                $('#vehiculo_acom').val(ac.vehiculo_acom || "");
                $('#tipo_vehiculo_acom').val(ac.tipo_vehiculo_acom || "");
                $('#acompanamiento_cuenta_acom').val(ac.acompanamiento_cuenta_acom || "");
                $('#valor_acompanante_acom').val(ac.valor_acompanante_acom || "");
            }
            // Paso 9: Entrega
            if(window.solicitudData.entrega) {
                const e = window.solicitudData.entrega;
                $('#nombre_cliente_ent').val(e.nombre_cliente_ent || "");
                $('#direccion_entrega_ent').val(e.direccion_entrega_ent || "");
                $('#numero_documento_ent').val(e.numero_documento_ent || "");
                $('#cantidad_ent').val(e.cantidad_ent || "");
                $('#empaque_ent').val(e.empaque_ent || "");
                $('#f_12_ent').val(e.f_12_ent || "");
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Para la primera carga por si ya está renderizado de entrada
            fillFormWithSolicitudData();
        });

        document.addEventListener("livewire:load", function() {
            Livewire.hook('message.processed', (message, component) => {
                // Chequea si tu modal está abierto (seguro por el id de tu modal)
                if ($('#openModalSolicitud').is(':visible')) {
                    fillFormWithSolicitudData();
                }
            });
        });
    </script>
    <script>
        window.addEventListener('solicitud-data-ready', function(e) {
            let data = e.detail;
            // Livewire 3 puede mandarlo como array o como objeto plano.
            if (Array.isArray(data)) {
                window.solicitudData = data[0]?.solicitud_data;
            } else {
                window.solicitudData = data.solicitud_data;
            }
            setTimeout(fillFormWithSolicitudData, 100);
        });
    </script>

    <!-- Estilos CSS adicionales para el diseño mejorado -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Estilos para los componentes x-input y x-select */
        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
        
        /* Efecto hover para las tarjetas de sección */
        .bg-gradient-to-r:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Scroll personalizado para el chat */
        #chatbox::-webkit-scrollbar {
            width: 6px;
        }
        
        #chatbox::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        #chatbox::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        #chatbox::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Estilos para mensajes del chat */
        .message-bubble {
            max-width: 80%;
            word-wrap: break-word;
            border-radius: 18px;
            padding: 12px 16px;
            margin: 8px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .userMessage {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .botMessage {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }

        /* Animaciones suaves para botones */
        .transform {
            transition: transform 0.2s ease-in-out;
        }
        
        /* Efecto de pulso para indicadores */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Mejoras de accesibilidad y focus */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Gradientes personalizados para las secciones */
        .section-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,252,0.9) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Mejoras en los inputs */
        input[type="text"], input[type="number"], input[type="date"], input[type="time"], select, textarea {
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 14px;
            background-color: #fafafa;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, input[type="time"]:focus, select:focus, textarea:focus {
            background-color: white;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Mejorar etiquetas de los inputs */
        label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
    </style>
</div>
