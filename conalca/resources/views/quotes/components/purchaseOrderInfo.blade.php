{{-- showQuoteModal --}}

{{-- row 1 --}}
<div
    class="w-full md:w-[28rem] min-h-[8.5rem] rounded-[0.82rem] bg-white pt-[1.06rem] pl-4 pr-[1.13rem] pb-[1.19rem] flex flex-col">
    <div class="flex flex-row flex-wrap items-center justify-between w-full mb-[0.81rem]">
        <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal">PENDIENTES</h4>
        <span class="text-[#FF7C32] text-3xl font-bold leading-normal -mt-1">+</span>
    </div>
    <div class="w-full mr-[0.63rem] min-h-[3.69rem] bg-[#FFEDED] pt-[0.56rem] pb-2 pr-3 pl-[0.94rem] rounded-[0.82rem]">
        <small class="text-[0.625rem] text-[#898989] font-normal leading-[0.875rem]">
            Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en demostraciones de tipografías o de
            borradores de diseño para probar el diseño visual antes de insertar el texto final.
        </small>
    </div>
</div>
{{-- end row 1 --}}
{{-- row 2 --}}
<div
    class="w-full md:w-[28rem] min-h-[29.75rem] rounded-[0.82rem] bg-white pt-[1.88rem] pl-[0.87rem] flex flex-col items-start justify-start
    shadow-md">
    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal ml-[1.1rem] mb-4">ORDEN <span
            class="font-normal">|</span>
        <span class="text-[#898989] font-normal">IDO001</span>
    </h4>
    <div
        class="flex flex-col md:flex-row justify-center md:justify-between w-full md:w-[23.20rem] ml-[1.1rem] font-normal leading-[1.03125rem]">
        <div class="flex flex-col items-start justify-start">
            <label for="origin" class="w-full text-[0.625rem] text-[#89898983]">
                Origen</label>
            <input type="text" placeholder="Bogota D.C." name="origin"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="charge_type" class="w-full text-[0.625rem] text-[#89898983]">
                Tipo de carga</label>
            <input type="text" placeholder="Maquinaría" name="charge_type"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="product" class="w-full text-[0.625rem] text-[#89898983]">
                Producto empaque</label>
            <input type="text" placeholder="N/A" name="product"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
        </div>
        <div class="flex flex-col items-start justify-start">
            <label for="destinatation" class="w-full text-[0.625rem] text-[#89898983]">
                Destino</label>
            <input type="text" placeholder="Barranquilla" name="destinatation"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="weight" class="w-full text-[0.625rem] text-[#89898983]">
                Peso</label>
            <input type="text" placeholder="12 Toneladas" name="weight"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="quantity" class="w-full text-[0.625rem] text-[#89898983]">
                Cantidad de vehículos</label>
            <input type="number" placeholder="1" name="quantity"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
        </div>
    </div>
    <div class="w-full md:w-[23.19rem] min-h-[6.19rem] rounded-xl pl-4 border border-solid border-[#dcdcdc]">
        <div
            class="flex flex-col md:flex-row justify-center md:justify-between w-full font-normal leading-[1.03125rem]">
            <div class="flex flex-col items-start justify-start">
                <label for="vehicle_type" class="w-full text-[0.625rem] text-[#89898983]">
                    Tipo de vehículo</label>
                <input type="text" placeholder="Cama baja" name="vehicle_type"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                <label for="vehicle_type_2" class="w-full text-[0.625rem] text-[#89898983]">
                    Tipo de vehículo</label>
                <input type="text" placeholder="Cama baja" name="vehicle_type_2"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                <label for="car_id" class="w-full text-[0.625rem] text-[#89898983]">
                    Placas</label>
                <input type="text" placeholder="AOS43R" name="car_id"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
            </div>
            <div class="flex flex-col items-start justify-start">
                <label for="body_work" class="w-full text-[0.625rem] text-[#89898983]">
                    Carrocería</label>
                <input type="text" placeholder="N/A" name="body_work"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                <label for="car_model" class="w-full text-[0.625rem] text-[#89898983]">
                    Modelo</label>
                <input type="text" placeholder="2005" name="car_model"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                <label for="driver" class="w-full text-[0.625rem] text-[#89898983]">
                    Conductor</label>
                <input type="number" placeholder="Nombre del conductor" name="driver"
                    class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] md:mb-0 text-[#898989]" />
            </div>
        </div>
    </div>
    <div
        class="flex flex-col md:flex-row justify-center md:justify-between w-full md:w-[20.20rem] font-normal leading-[1.03125rem]
            ml-[1.1rem] mt-[0.56rem]">
        <div class="flex flex-col items-start justify-start">
            <label for="rate" class="w-full text-[0.625rem] text-[#89898983]">
                Tipo de tarifa</label>
            <input type="text" placeholder="N/A" name="rate"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="merchandise_value" class="w-full text-[0.625rem] text-[#89898983]">
                Valor de la mercancía</label>
            <input type="text" placeholder="$300.000.000" name="merchandise_value"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
        </div>
        <div class="flex flex-col items-start justify-start">
            <label for="client_rate" class="w-full text-[0.625rem] text-[#89898983]">
                Tarifa cliente</label>
            <input type="text" placeholder="N/A" name="client_rate"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
            <label for="insurance" class="w-full text-[0.625rem] text-[#89898983]">
                Seguro mercancía</label>
            <input type="text" placeholder="$300.000.000" name="insurance"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
        </div>
    </div>
    <small class="text-[0.625rem] text-[#89898984] font-normal leading-[1.03125rem] ml-[1.01rem]">Descripción de
        mercancia</small>
    <div
        class="w-full md:w-[23.19rem] min-h-[4.44rem] rounded-xl border border-solid border-[#dcdcdc] pt-[0.38rem] pr-[0.87rem] pl-4 mb-4">
        <small class="text-[0.69rem] text-[#898989] font-normal leading-[1.03125rem] -mt-2">
            Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en demostraciones de tipografías o de
            borradores de diseño para probar el diseño visual antes de insertar el texto final.
        </small>
    </div>
</div>
{{-- end row 2 --}}
{{-- row 3 --}}
<div
    class="flex flex-col w-full md:w-[28rem] min-h-[7.94rem] rounded-[0.82rem] bg-white shadow-md pt-3 pl-[2.37rem] pr-9 mb-5">
    <div class="flex flex-row flex-wrap items-start justify-between w-full">
        <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal">FACTURA</h4>
        <span class="mt-3">
            <svg width="33" height="33" viewBox="0 0 33 33" fill="none"
                xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect width="33" height="33" fill="url(#pattern0)" fill-opacity="0.5" />
                <defs>
                    <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_114_1369" transform="scale(0.0078125)" />
                    </pattern>
                    <image id="image0_114_1369" width="128" height="128"
                        xlink:href="data:image/png;base64,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" />
                </defs>
            </svg>
        </span>
    </div>
</div>
{{-- end row 3 --}}
