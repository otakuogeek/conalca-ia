<div>
    <!-- You can open the modal using ID.showModal() method -->
    <button class="btn" onclick="my_modal_4.showModal()">open modal</button>
    <dialog id="my_modal_4" class="modal">
        <div class="modal-box bg-white" style="width: 80%; border-radius: 15px; overflow-y:scroll;">
            <div style="padding: 20px; overflow-y:scroll;">
                <h3 class="text-lg font-bold mb-4">Agregar Pricing</h3>
                <div class="mb-4">
                    <label for="type_pricing" class="block text-sm font-medium text-gray-700">Tipo de
                        Pricing</label>
                    <select id="type_pricing"
                        class="mt-1 block w-full pl-3 pr-10 py-2 bg-white border-[2px] border-solid border-[#eff4fa]"
                        wire:change="clickHandle" wire:model="type_pricing">
                        <option value="" selected>Selecciona una opcion</option>
                        <option value="up">Urbanos ipiales</option>
                        <option value="sm">San miguel</option>
                        <option value="p">Peru</option>
                        <option value="tt">Trueca tulcan</option>
                        <option value="bd">Bogota dedicados</option>
                        <option value="i">Importacion</option>
                        <option value="ebmcc">Exportacion bogota-medellin-cali-carta</option>
                        <option value="uc">Urbanos colombia</option>
                        <option value="vn">Viajes nacionales</option>
                        <option value="ivco">Impo viajes circulares orig</option>
                        <option value="cs">Carga suelta</option>
                        <option value="dv">Devolucion vacios</option>
                    </select>
                </div>
                <!-- Rest of the inputs divided into 3 columns -->
                @if ($type_pricing == 'up')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="mb-4">
                            <label for="documents"
                                class="block text-sm font-medium text-gray-700">Documentos</label>
                            <input type="text" id="documents" name="documents"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="vehicle_type" class="block text-sm font-medium text-gray-700">Tipo de
                                Vehículo</label>
                            <input type="text" id="vehicle_type" name="vehicle_type"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="extra" class="block text-sm font-medium text-gray-700">Extra</label>
                            <input type="text" id="extra" name="extra"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_extra" class="block text-sm font-medium text-gray-700">Precio
                                Extra</label>
                            <input type="text" id="price_extra" name="price_extra"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_extra2" class="block text-sm font-medium text-gray-700">Precio Extra
                                2</label>
                            <input type="text" id="price_extra2" name="price_extra2"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="download_target" class="block text-sm font-medium text-gray-700">Descarga
                                Destino</label>
                            <input type="text" id="download_target" name="download_target"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="load_target" class="block text-sm font-medium text-gray-700">Carga
                                Destino</label>
                            <input type="text" id="load_target" name="load_target"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_person" class="block text-sm font-medium text-gray-700">Precio
                                Persona</label>
                            <input type="text" id="price_person" name="price_person"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="event" class="block text-sm font-medium text-gray-700">Evento</label>
                            <input type="text" id="event" name="event"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="type_send" class="block text-sm font-medium text-gray-700">Tipo de
                                Envío</label>
                            <input type="text" id="type_send" name="type_send"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="save_box" class="block text-sm font-medium text-gray-700">Guardar
                                Caja</label>
                            <input type="text" id="save_box" name="save_box"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="time_day" class="block text-sm font-medium text-gray-700">Tiempo
                                Día</label>
                            <input type="text" id="time_day" name="time_day"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="return" class="block text-sm font-medium text-gray-700">Retorno</label>
                            <input type="text" id="return" name="return"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="container"
                                class="block text-sm font-medium text-gray-700">Contenedor</label>
                            <input type="text" id="container" name="container"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="complements"
                                class="block text-sm font-medium text-gray-700">Complementos</label>
                            <input type="text" id="complements" name="complements"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="download_price" class="block text-sm font-medium text-gray-700">Precio
                                Descarga</label>
                            <input type="text" id="download_price" name="download_price"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="iva" class="block text-sm font-medium text-gray-700">IVA</label>
                            <input type="text" id="iva" name="iva"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="person_download" class="block text-sm font-medium text-gray-700">Persona
                                Descarga</label>
                            <input type="text" id="person_download" name="person_download"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="rent" class="block text-sm font-medium text-gray-700">Renta</label>
                            <input type="text" id="rent" name="rent"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="download"
                                class="block text-sm font-medium text-gray-700">Descargar</label>
                            <input type="text" id="download" name="download"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="load" class="block text-sm font-medium text-gray-700">Cargar</label>
                            <input type="text" id="load" name="load"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="store"
                                class="block text-sm font-medium text-gray-700">Almacenar</label>
                            <input type="text" id="store" name="store"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="scales" class="block text-sm font-medium text-gray-700">Escalas</label>
                            <input type="text" id="scales" name="scales"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="time" class="block text-sm font-medium text-gray-700">Tiempo</label>
                            <input type="text" id="time" name="time"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="weight" class="block text-sm font-medium text-gray-700">Peso</label>
                            <input type="text" id="weight" name="weight"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="vehicle_extra" class="block text-sm font-medium text-gray-700">Vehículo
                                Extra</label>
                            <input type="text" id="vehicle_extra" name="vehicle_extra"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="download_destiny" class="block text-sm font-medium text-gray-700">Descarga
                                Destino</label>
                            <input type="text" id="download_destiny" name="download_destiny"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="download_destiny_iva"
                                class="block text-sm font-medium text-gray-700">Descarga
                                Destino IVA</label>
                            <input type="text" id="download_destiny_iva" name="download_destiny_iva"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_complements" class="block text-sm font-medium text-gray-700">Precio
                                Complementos</label>
                            <input type="text" id="price_complements" name="price_complements"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_documents" class="block text-sm font-medium text-gray-700">Precio
                                Documentos</label>
                            <input type="text" id="price_documents" name="price_documents"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="load_tulan" class="block text-sm font-medium text-gray-700">Carga
                                Tulan</label>
                            <input type="text" id="load_tulan" name="load_tulan"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="volume" class="block text-sm font-medium text-gray-700">Volumen</label>
                            <input type="text" id="volume" name="volume"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_month" class="block text-sm font-medium text-gray-700">Precio
                                Mensual</label>
                            <input type="text" id="price_month" name="price_month"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_aux_month" class="block text-sm font-medium text-gray-700">Precio
                                Auxiliar Mensual</label>
                            <input type="text" id="price_aux_month" name="price_aux_month"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_week" class="block text-sm font-medium text-gray-700">Precio
                                Semanal</label>
                            <input type="text" id="price_week" name="price_week"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_aux_week" class="block text-sm font-medium text-gray-700">Precio
                                Auxiliar Semanal</label>
                            <input type="text" id="price_aux_week" name="price_aux_week"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_day" class="block text-sm font-medium text-gray-700">Precio
                                Diario</label>
                            <input type="text" id="price_day" name="price_day"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price_aux_day" class="block text-sm font-medium text-gray-700">Precio
                                Auxiliar Diario</label>
                            <input type="text" id="price_aux_day" name="price_aux_day"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="condition"
                                class="block text-sm font-medium text-gray-700">Condición</label>
                            <input type="text" id="condition" name="condition"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="weight_from" class="block text-sm font-medium text-gray-700">Peso
                                Desde</label>
                            <input type="text" id="weight_from" name="weight_from"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="weight_to" class="block text-sm font-medium text-gray-700">Peso
                                Hasta</label>
                            <input type="text" id="weight_to" name="weight_to"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="origin" class="block text-sm font-medium text-gray-700">Origen</label>
                            <input type="text" id="origin" name="origin"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="destination"
                                class="block text-sm font-medium text-gray-700">Destino</label>
                            <input type="text" id="destination" name="destination"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                        <div class="mb-4">
                            <label for="price" class="block text-sm font-medium text-gray-700">Precio</label>
                            <input type="text" id="price" name="price"
                                class="mt-1 block w-full bg-white border-[2px] border-solid border-[#eff4fa]">
                        </div>
                    </div>
                @endif

                <div class="modal-action flex justify-end space-x-4">
                    <button
                        class="btn h-10 bg-[#FF7C32] rounded-lg items-center text-white text-sm font-bold leading-normal justify-center gap-[0.38rem] ml-auto">Agregar
                        Pricing</button>
                    <form method="dialog">
                        <button class="btn">Cerrar</button>
                    </form>
                </div>
            </div>
        </div>
    </dialog>
</div>
