<dialog id="quotes_modal_p2" class="modal">
    <div class="flex flex-row flex-wrap bg-[#F3F2F2] w-full md:w-[87.68rem] h-full md:h-[44.12rem] 2xl:h-[52.12rem]
    items-center justify-center overflow-auto mx-2 md:mx-0 rounded-[0.9375rem] shadow-md relative">
        <button
            class="absolute right-[2.31rem] top-[1.37rem] w-[11.19rem] h-[1.69rem] rounded-[0.625rem] bg-[#d9d9d9] text-center">Guardar
            Borrador y Salir</button>
        <div class="w-full md:w-[37.875rem] h-full md:h-[36.187rem] 2xl:h-[46.187rem] rounded-[0.94rem] bg-[#F3F2F2] shadow-xl flex flex-col
    mt-[3.31rem] ml-3 md:ml-[4.13rem] mb-3 md:mb-[2.62rem] md:p-0 md:pt-8 md:pl-[2.62rem] md:pr-[2.31rem] md:pb-4">
            <div class="flex flex-row flex-wrap items-start justify-start md:justify-between gap-3 md:gap-0">
                <div class="flex flex-col justify-start text-black text-base font-normal leading-normal">
                    <h5 class="font-norma">Creación de cotización</h5>
                    <h5 class="font-normal">IDC003 | <span class="font-bold uppercase">Empresa uno</span></h5>
                    <h5 class="font-bold uppercase">Tipo cliente activo</h5>
                    <h5 class="font-norma">{{ date('F d, H:i A') }} | RolAdmin02</h5>
                </div>
                <select name="quote_type" id="quote_type" class="w-[8.94rem] h-[2.63rem] rounded-lg
            bg-white border border-solid border-[#dcdcdc]">
                    <option value="etiqueta" disabled>Etiqueta</option>
                </select>
            </div>
            <h5 class="text-[#00000081] text-base font-normal leading-normal mt-[3.19rem] mb-[0.44rem]">Validando
                información
                ingresada...</h5>
            <form class="flex flex-col text-black text-sm font-normal leading-normal">
                <label for="origin">Origen</label>
                <label for="destination">Destino</label>
                <label for="merchandise_type">Tipo de mercancía</label>
                <label for="value">Valor de la mercancía Peso</label>
                <label for="vehicle_type">Tipología de Vehículo</label>
            </form>
        </div>
        <div
            class="w-full md:w-[40.775rem] h-full md:h-[31.5rem] 2xl:h-[40.187rem] bg-white flex flex-col mt-3 md:mt-[3.31rem] ml-3 md:ml-[4.13rem]
        mb-3 md:mb-[2.62rem] md:p-0 md:pt-8 md:pl-[2.62rem] md:pr-[1.53rem] md:pb-4 gap-6 font-normal leading-normal text-black relative overflow-y-auto scrollbar-default">
            {{-- destination message --}}
            <div class="flex flex-col self-end justify-end pr-[1.52rem] pt-[0.52rem] pb-[1.52rem] pl-4 rounded-tl-[0.94rem] rounded-tr-[0.94rem] rounded-br-none
            rounded-bl-[0.94rem] bg-white shadow-md ml-5">
                <span class="text-[0.5rem] text-right">{{ date('F d, H:i A') }}</span>
                <h6 class="text-sm text-right">
                    para solicitar su ayuda con la siguiente cotización terrestre Orígenes: Puerto marítimo de
                    Buenaventura Destino: Km 13 Vía Yumbo Aeropuerto, CALI. Mercancía: 400 bolsas Enviado en 1x40HC
                    Peso: 4.165kg Por favor contemplar tarifa tanto con: devolución a puerto como: dropp off en Cali De
                    antemano agradezco y espero su pronta respuesta
                </h6>
            </div>
            {{-- origin message --}}
            <div class="flex flex-col self-start justify-start pr-[1.52rem] pt-[0.52rem] pb-[1.52rem] pl-4 rounded-tl-[0.94rem] rounded-tr-[0.94rem] rounded-br-[0.94rem]
            rounded-bl-none bg-[#F0F0F0] shadow-md mr-5">
                <span class="text-[0.5rem] text-left">{{ date('F d, H:i A') }}</span>
                <h6 class="text-sm text-left">
                    Hola, ayudame con estos datos para poder entregarte
                    la cotización....
                </h6>
            </div>
            <form class="absolute bottom-0 left-0 w-full px-3 md:px-4 flex flex-row gap-1">
                @csrf
                <input type="text" placeholder="Escribe tu mensaje" name="message"
                    class="w-full h-8 rounded-lg bg-white mb-1" />
                <button>
                    <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink">
                        <rect width="9.07445" height="19" fill="url(#pattern0)" fill-opacity="0.5" />
                        <defs>
                            <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                                <use xlink:href="#image0_12_608"
                                    transform="matrix(0.0163577 0 0 0.0078125 -0.546896 0)" />
                            </pattern>
                            <image id="image0_12_608" width="128" height="128"
                                xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAelSURBVHic7Z1JjBVFGMd/M0x0mAAyKgQNkIgLqxBQQCPiGDEGRBGUxRjlQkyMB2I8wMEIR0mMSmJUNDFxiwcjiooSBeEgu0QlijCgsosQAWdYB5jxUP0kTnhV1a+ru7pffb/ku0xNff1V1f91dy1dVUP1UwsMBsYBtwA3ANcBVwDdo/9pBY4DfwC/Ad8Da4FtQEfG8QqOGAMsBvajGrES2w+8EvkSCkAtMBPYSOWNXs42ANOBmsxKI8SiCdiC+4bvbJuB8dkUSbChB7AEaCf9xi9Ze3TN0juE4IlhwC6ya/jO1gwMTb2UwiWZjHp799X4JWuJYhEyZBbQhv/GL9l54PFUSyz8x1RUhftu9EuJYEqK5RaA24BT+G/scnYSGTNIjUZgD/4b2WT7gKtSqoOg+Qz/jWtrS1Oqg2CZjv9GjWtTU6mJAGlA3VZ9N2hc2wt0TaE+nFLrOwALngL6+g6iAvoBT/oOoujUA3/i/9dcqR0ELndeKw7J+x1gGtDHdxAJuAZ4yHcQRWYl/n/FSe1r57XikDzPbV8JHAa6+A4kIReA3sBR34Fcijw/AiZS/MYHVYZ7fQdRjjwL4C7fATikyXcA5cizAEb6DsAho3wHUI463wFo+AJY5cDPOOCOCvOuBb5zEMNpBz6ECllI5W/wCzOPNmPy/AgQMkAEEDgigMARAQSOCCBwRABCLmnC/ySOa2tyWD/OkDtA4IgAAkcEEDgigMARAQSOCCBwRACBIwIIHBFA4IgAAievy8IbUZs6uuAJKt+94z3gXUdxbAGOOfLljLyuCTyG+ijEBeMS5P3dYRy5RB4BgSMCCBwRQOCIAAJHBBA4ee0FuOwGDkiYd4KjOHLZDcwrTfhfwiVLwoTqRwQQOCKAwBEBBI4IIHBEAIEjAggcEUDgiAACRwQgVD0LkU2iyiJ3gMARAQROmtPBpildmR69iLe6SlMANwHfaNInAV+leP0icTuwXJM+FtiUxoXTfAScMKR3S/HaRaOHIb01rQv7FICcvH0RU12Y6rJi0nwEmFTb25A+F3VmUFIq3Si6lHeegxjOAIs16b0M+VO7A6RNC+X72G8b8m7S5C2abTCU9R1N3uOGvIlIuxu4S5M20JD3R5eBeOYHQ7quLna6DKQzaQugWZM2GP3HqWvchuKV1Zq0WvQC0P2IEpO2AHZo0hqBmzXpK1AHLhWdC+g/MB0O9NSk6+owMWkLYL0h/W5N2lH0v5yisAr9iWH3GPKvcxhL5nQHzlH+BWeZIf+jmrxFsRmGMi7X5D1HFYyXbKZ8AU+jv/3Vo45f9d2IldoB9EfHNqK6iJX2HhKTxWSQ7vlXj/4XcgZ40W04mbIIOKtJn4leIC4OzfLOSPS/EtOpXA2oo9h9/5rj2h7Mx8evNfgYYchfGH5BX9DRhvwPG/Ln0aYYyjTGkP9XQ/5CsRB9YT+18PGJwUee7COL8iwz+Fhg4aMw9AfaKF/YdlR/WEcjsFvjIy+2F3XwtY4RUZnL+WgD+hl8FI730VfcGszb1o0BThr8+LQTwK0WdbHK4MfV1nS5Yjh61Xeg+v0mHgTOG/z4sHPAZIv4H7PwVU3nJv+PFegLfhB1qzcxA/0jJWs7CzxiEXcj5nGNLy38FJYh6EcGO4DPsdvB9H70081ZWQtqeZuJGswvsueBYRa+Cs1rmCt1rqWvoagZR1+NvwMlahuetfD3qqWvQtMLNTliuqWaJklKNAAvoGbdsmr4dmAJ9uP0EzA/sv4Grrb0V3hsBnZaiLdT2HiyWUW0EbgzRlzDUat6TH5nxfBZFXyIuVIOAYNi+KxBvYytt/Ad19YB04i3w/og4C8L3x/E8Fk1NGI3xn8E1f+Py2jgZctrlLM9wEvY9e07MzaK3eYauhnRqmYUdgM7rcDECq9Rg1p+Ngd4AzU7uRP1HtIW2dHobyuj/5kT5amUSahBIVO5TlDFfX5bpmH3AteOetnr4idMK7qg5j1sBqraUdPBAjAf+9vyt0BfP2Fq6YcazrYth4vvDaqKedhX3knUL+0yH4F2og41bhFnUGqBl0gLwPPEe0nbipob8HH2UQ1qzv/nGPF2AM95iLVQPEP8yZ6fUM/TLHY/r4uutTVmjOexH+EMnvtQ38PHqeAO1GjaEpIdFFWOoaiX0EMVxNUCPJBCTFXNEGA78Su7ZPtQ39zNBm4k3t2hLsozO/KxL0Ec20nWpUyVvJ4bWKIBtbL2aZLH2oY6Bq4ZNTTbysXPrruhvmHoidrYYgDJXzA7UJM784FTCX0FzwTUiFmlv8KsbTf2k1mCJV1RXcV/8N/A5ewEqntqWg4uJKAP8DrqqyLfDV6y01FMfVIst9CJXqg7wgH8NfxhVM/g2pTLKmioR/XLl5LNXeEU8HF0TRdb2AgO6Y5aWfwWsA03Dd4e+Xoz8l1Vm1vlvRuYlF6otQQDgeuBG1B78pVrxFbUYpJdkTWjVhkdST1SITNWU/7XvtpjXF6QvYIDRwQQOCKAwBEBBI4IIHBEAIEjAggcEUDgiAACRwQQOCKAwBEBBI4IIHBEAIGTxZc0tmT1sWR/Q1pWcSzK6Dpa8rQgpMN3ABmTi7qXR0DgiAACRwQQOCKAwBEBBI4IIHD+BQrSFtswrosoAAAAAElFTkSuQmCC" />
                        </defs>
                    </svg>
                </button>
            </form>
        </div>
        <button onclick="quotes_modal_p3.showModal(); quotes_modal_p2.close()" class="w-full md:w-[16.58rem] h-[2.69rem] rounded-lg bg-[#FF7C32] text-center relative sm:mb-4 md:mb-0 md:absolute md:bottom-[2.06rem] md:right-[12.67rem]
            text-white text-sm font-medium leading-normal">Crear
            cotización</button>
    </div>
</dialog>