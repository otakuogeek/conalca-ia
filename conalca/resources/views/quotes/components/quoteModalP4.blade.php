<dialog id="quotes_modal_p4" class="modal">
    <div
        class="flex flex-col w-full md:w-[74.82rem] h-full rounded-[1.125rem] bg-[#F9F9F9] 2xl:h-[59rem] md:pt-[4.41rem] md:pl-[3.69rem]
        md:pr-[3.56rem] overflow-y-auto scrollbar-default relative">
        <button
            class="absolute right-[2.31rem] top-[1.37rem] w-[11.19rem] h-[1.69rem] rounded-[0.625rem] bg-[#FF7C32] text-center text-white
            font-normal text-sm leading-normal hover:opacity-80">
            Guardar
            Borrador y Salir</button>
        <div
            class="flex flex-col md:pl-[3.69rem] md:pr-[3.31rem] pt-4 w-full h-full rounded-[0.94rem] bg-white shadow-xl text-black
            text-sm font-normal leading-normal text-justify overflow-y-auto scrollbar-default">
            <img src="{{ asset('img/logo-conalca.png' z) }}" alt="logo-conalca"
                class="w-[10.5rem] h-[2.34rem] self-end object-cover " />
            <span class="uppercase mt-3">Presentación y cotización conalca</span>
            <span class="uppercase">Titulo</span>
            <span>{{ date('F d, H:i A') }}</span>
            <span class="uppercase">Saludo</span>
            <span class="md:w-[56.75rem] mt-3">Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en
                demostraciones de tipografías o
                de borradores de diseño para probar el diseño visual antes de insertar el texto final. Lorem ipsum es el
                texto que se usa habitualmente en diseño gráfico en demostraciones de tipografías o de borradores de
                diseño para probar el diseño visual antes de insertar el texto final. Valores</span>
            <table class="table-xs w-full md:w-[44.57rem] self-start mt-3">
                {{-- head --}}
                <thead>
                    <tr class="text-[#202020] font-bold uppercase h-[1.2rem] border border-solid border-[#898989]">
                        <th class="align-middle md:text-start border-0">Ruta #</th>
                        <th class="align-middle md:text-center border-0">Origen</th>
                        <th class="align-middle md:text-center border-0">Destino</th>
                        <th class="align-middle md:text-center border-0">Costo</th>
                        <th class="align-middle md:text-center border-0">Tiempo de entrega</th>
                    </tr>
                </thead>
                {{-- body --}}
                <tbody>
                    {{-- row 1 --}}
                    <tr class="font-normal h-[2.82rem] bg-[#FFE6D8] border border-solid border-[#898989]">
                        <td class="align-middle border-0">
                            <span>#1</span>
                        </td>
                        <td class="align-middle border-0 text-center">
                            <span>Bogota DC</span>
                        </td>
                        <td class="align-middle border-0 text-center">
                            <span>Barranquilla</span>
                        </td>
                        <td class="align-middle border-0 text-center">
                            <span>$5.000.000</span>
                        </td>
                        <td class="align-middle border-0 text-center">
                            <span>3 días</span>
                        </td>
                    </tr>
                    {{-- end row 1 --}}
                </tbody>
            </table>
            <span class="mt-[1.31rem] mb-3">Cordialmente</span>
            {{-- singature --}}
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="102" height="42" viewBox="0 0 102 42"
                    fill="none">
                    <path
                        d="M27.0134 39.8971C31.0469 32.6407 37.8693 23.3599 36.9332 14.1201C36.0341 5.24533 28.9287 1.30335 20.6415 2.10043C17.2022 2.43124 2.83397 7.81659 2.03286 14.1201C1.27786 20.0607 13.7253 17.7805 19.7002 17.3784C21.1053 17.2838 36.5023 14.5286 40.3363 17.4508C42.5259 19.1196 37.5063 24.3004 37.3676 24.7639C36.5602 27.463 42.9675 24.0232 45.6945 23.3158C50.4827 22.0736 62.5733 17.4253 63.4343 26.4655C63.5893 28.0935 62.8795 29.6915 62.493 31.2806C62.3351 31.9298 61.1461 33.0533 61.8051 33.1632C62.5054 33.2799 62.5809 31.9727 62.9274 31.353C63.6072 30.1374 64.232 28.8918 64.8824 27.6602C68.2132 21.3531 65.5779 25.6146 66.1858 26.4655C66.9162 27.4882 67.9933 24.7066 69.0097 23.9675C72.3767 21.5187 74.6461 20.4091 78.7846 19.261C86.2061 17.2021 92.8342 19.0444 100 20.9988"
                        stroke="black" stroke-width="3" stroke-linecap="round" />
                    <path d="M63.5068 14.4821C69.495 13.368 75.705 13.4344 81.7535 12.5271" stroke="black"
                        stroke-width="3" stroke-linecap="round" />
                </svg>
            </span>
            {{-- end signature --}}
            <span class="mt-1">Nombre asesor comercial</span>
            <span>Tel</span>
            <span>Pbx</span>
            <span>Ubicación</span>
            <span class="mb-4">Correo</span>
            <span>A continuación puedes aceptar nuestra cotización como aprobado o declinar a generar una negociación
                nueva.</span>
            <div class="flex flex-row flex-wrap items-center justify-between w-full">
                <div class="flex flex-row flex-wrap gap-2">
                    <button
                        class="w-[10.125rem] h-[2.125rem] rounded-lg bg-[#04D000] text-white text-center font-medium hover:opacity-80">
                        Aceptar Cotización</button>
                    <button
                        class="w-[10.125rem] h-[2.125rem] rounded-lg text-[#898989] text-center font-medium
                        hover:bg-red-500 hover:text-white transition-colors duration-300">
                        Rechazar Cotización</button>
                </div>
                <button
                    class="w-[3.725rem] h-[3.725rem] rounded-[100%] border-[4px] border-solid border-[#FF7C32] flex items-center
                    justify-center">
                    <svg width="34" height="34" viewBox="0 0 34 34" fill="none"
                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                        <rect width="34" height="34" fill="url(#pattern0)" fill-opacity="0.5" />
                        <defs>
                            <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1"
                                height="1">
                                <use xlink:href="#image0_128_1162" transform="scale(0.0078125)" />
                            </pattern>
                            <image id="image0_128_1162" width="128" height="128"
                                xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAAAXNSR0IArs4c6QAADlxJREFUeF7tXXuMHVUZ/76Z3e7KWrt755y76wqxQBPTCkgFraUoLQoVKy3PQPyDGN6QCJJI/APiIw3gXxgFBapICBFBkkoory0tAgEjTwtWAa3aQtnu3Tmzu22BstvZ+cxXZ2u73XvnzOPOzt17TrLJJvc753zn9/vNOTPn8R2E7JMthDgZAFYg4jwi6gUA/usBgNbsqyt8iXsAoH/iDxHf8H3/keHh4c1F8ByzcqJcLi8hosuZeCJysip3BpfzbwB4xLbt2yqVCv8/LSm1AEql0gLLsm4BgJXT0oLGr3QvEd3R1ta2ur+/X+XdnDQCmCWlvJWIrgQAO2/HZ2B9uwDgeqXUmjzblkgAPT090vf9tQDAY71JGSKAiL9wXfe7AOBnWGzVomILoKur6xjLstYh4tw8HGzSOjb4vn/+yMjISL3bH0sApVLpcMuyXg7f6OvtW7OX/4xS6nQA2FtPILQF0Nvbe9jY2NjzALCwng6Zsg9CYI1S6op6YqItAMdxHkLE8+rpjCn7UASI6Due591eL2y0BOA4zipEfLheTphyayLwIQDMU0rtqAdOOgLgmb2/AsD8mA4MIOJ6AHgVEflvU6VS+SBmGQ1vPnv2bKe1tfUE27ZPBIATiejrAPCxmA2r21AQKQAhxKUA8Ks4DiPivb7vXzs8PLwzTr5msBVCfAYA7gWARTHaO05Ex3ie91aMPFqmOgLYAgBHa5UGUAGAy5RS6zTtm9XMdhznekT8MQDM0gGBiH7jed4lOrZxbGoKoLu7+9jx8fE3NAvkiYvFSqlXNO2b3kwIwRM+P9UBAhE913W7AWBcx17XpqYApJQ3EtFqncKI6GbP827QsTU2+xGwhBDPxphRXaaUeiZL/GoKQAjxEgB8IapCRNzsuu4JADAWZWt+PxgBKSUvmb8OAIdFYUNEP/c879oouzi/1xIAq3MUAFqiCiSiizzPuy/Kzvw+NQJCiF8DQOT4TkR/8jxvSZY4VhVAuVzuDoJgQKcyIlrged6bOrbG5lAEhBC8j+KuKGyIaKvneUdG2cX5vaoApJQLieg1jcLeV0rNAYBAw9aYTIGAlPJ4IvqLBjijSql2DTttk6oCEEKsAIBHNUp6Til1ioadMamOQIsQgvcDRE4QjY6Oit27d3tZgVlLABcCwO80KnpQKcW2JqVAQAixFQA+HVVES0vLkQMDA2ybSTICyATG9IUYAaTHsKFLMAJoaPrSO28EkB7Dhi7BCKCh6UvvvBFAegwbugQjgIamL73zRgDpMWzoEowAGpq+9M43kwB42vM6AFgVbjGPXAZND29uJfDZPj43cUfcXVFNIQAhxGwA2AAAX8yNkumriEVwtW71TSEAx3HuRsSLdUFpdDtEvNB13Qd12jHjBRAeKB3UAWMG2WxSSmmdpJrxAiiXy8uDIHhyBpGr0xS/vb39E9u3b+coITWTEUAUQo35uxHABG9mCGjyHoCbb14Cq4tgxg8B3HTzGdjkAgibbyaCptBBU/QAjfkel4/XRgD54FzYWowACktNPo4ZAeSDc2FrMQIoLDX5OGYEkA/Oha3FCKCw1OTjmBFAPjgXthYjgMJSk49jRgD54FzYWppJAGYqeOqpYL40IjL4w/j4+KeHh4ffyUrJuZ4ONotB1WmTUq4notMiiP0gDMaRWaSwXAVgloOr0+s4zmpEvDFCABuVUl/L6unncnITgNkQUpu2rq6uObZtc0jeI6pYchzGRUopnbA92hrJTQBmT2A0J0KIzwMA7yKeN8maQ+5erZS6P7qUeBZGAPHwimutvSdwouDwXoaLiIjPTvA5ipeJ6P6hoaHtcSvXsc9NAGYI0KEjf5vcBMBNMy+B+RMcVWOuAjCfgVF05P97rgIIm2cmgvLnuWqN0yGAAjXfuGIE0OQaMAIwApgaASGECRXbBOIwPUATkFyriUYARgBmCGhmDZgeoJnZz3M5uMlxjtX87u7uo4IgWEFEvPY/FwA+CQB8KwuH2NmBiG8S0RN79+7t27lz53CswicZmx4gDXoZ53Uc51TLsm4ioi9pFs07gx6wLOuHg4OD/9LMc5DZdAigSFPB2wDgRQZ9cHBQ94LMJDjXzFMqlY6wbftujS1h1crxEfHOOXPmfG/Lli1805t2ylUABV4MGkPEq13XvVsbuYwMpZRfIaKHAKCctkhE/DMAnO26rtZtb1xfrgIo+HLwR0S0sB4XNFcjVkr5bSLii7kj72aMIY7tLS0tX9a9Vyg3ATTIhpA7lVJXxQA7samU8mIi4gsjIy/wTlDJ67ZtL6lUKh9E5c1NAA2yJ/A1pRRfgVvXVGfyJ3z/vVLqgqiGGAEcjFDdBZAT+ftaFQTB6UNDQ0/VEkFuAjBDAICU8pJwzK9Htz8Vz68qpfjyb6omgtwEwA4080vgNJC/j3NEPMN13aohenMVQLN+BqYgf4iIfhAEwbO2bW8LguAYy7LOAwC+Qt6OGt/D39copa4oRA8QOtFUE0EpyH8BAM5XSu2YTJ7jOIsQke9d+LiGCHYopT5VbRjItQfQcHZGmQghLgWANQk+9cYRcb7ruv+sBojjOFch4i91APN9f+7IyAjPeh6SjAB0EExgk4L8idreQsRlNWb1UErpEpET5R4RLfI87yUjgCikMvo9A/K1RCCl7COi0zXcPlMp9agRgAZSaU0yJD9SBEYAadnKOL8Q4jIAuCvBmB/lyVTDgRkColDL8/c6kj9lT2BeAvNkN6KuHMg/SARBEHQhIr/Umc/A6dZBCvL5gsnPAkDcizPfDreH9Wi2vXATQZp+F99MCHE5ANyZYMx/ur29/Zt79uxZjIjrEohAG5xCTQVre90AhinI39je3n7mxFVyvA+wjiIo1mJQA/Cq5WIK8je0t7evnHyPYL1EUKjlYC1kG8Aoa/InmlwHERRrQ0gDcBvpohCCV9XuiDvmI+JTbW1tq6JuEM1QBMXbEhaJbsEN0pDf0dGxcuvWrR/pNDEDEQy0tLQsLtymUJ3GF9UmBfnrOzo6VumSf8BwoL3SNwmzASJaFmdns1kNjJ7kuRIAeNk11jYuIuqbPXv2WXHJ7+7uPnZ8fHwjAMiYDwTvG1imlOJ5Au1kBFADKiHEdJD/NF+yqs3g/wwTkc8ZjQCqIJ2UfAB4srOz86y4R7TCJz9X8o0Asif/ic7OzrPjkl8ul48LgoC7/SRP/lKl1D9i9hj7zU0PMAm5FN/5eZPfH475ick3PcAk8qWU84hoMwC0xXyiHu/s7Dwnxyc/E/KNAA59+h8HgDNikv9YZ2fnuUnIJ6Kndfb0TfInM/KNAA5AtqenZ67v+/+JS75S6hwAGIuTr1wuf46INiYhHxGX1totHMcPI4AD0JJSXkNEP4sB4KNKqXNzJP+9cJdw1a3iMXw3L4GTwRJC8EGLr2qCuE4pxSd08nry60K+6QFCtsP7elwAaNUQQCLypZTHA8CGBN0+k8/d/hYN32KbmM9AnnOV8gIiekADPULE3jghWLjMopJveoCQcSHEbwHgWxoCeEkptUjDbr8Jk88vfABQipMPALaHY35dnvwJX2r1ACsAYMrTJJMa8pxS6pSYjSuSOR9W5fh7XVFO8b1+ruveFGU38Xsa8i3LWpo09JuufzV7ACnlQiLSuaPu/fA2yyBOxUWxFUIsBYA/6vhj2/ZxlUqF7/aLTCF+/GIZ+8nPi/yaAiiXy91BEGiFGyOiBZ7nvRmJSgENhBC3AsB1Ua4R0VbP8yLv9g3HfH54kpD/rmVZy/J48iOHAACwhBAcdDAyhBkRXeR53n1RIBbxdyEEj7FHR/lGRLd5nndNlF2KJ/9d27aXVioVvkQ6t1Rzk4MQgk+fcIyZmgkRN7uuy9G1Yn0XR5Vb798dx5mPiH/XqYejeHqex0911SSEYAzWJ+j2p4X8mkNA2JXdSESrNQG62fO8G3Rsi2LjOM73EfEnGv7sUkrxUu3eSbatQoiTEHE5ES0HgIVxdw4BwLSRHymAcJOCbgxdvtx4sVLqFQ1AC2HiOM4LiHiShjP7t1jzimEQBMsRkc/ln6p5Pq9aFe+E3X7cNQgNl/VMIve56Y6RYXUVALhMKcXHnQqdwrB1/JJrRTlKRPcgIu/q5af8qCh7zd+nnfzIHoANwoAHHM9WOyHivb7vXzs8PMy3XhcyhXF675km5wpBvpYAOByZEIK/fefHBGsAEfmF6FVE5L9NOrFrY9aR2FxKuZaIzk5cQPKM22zbXlapVKat2z/Q9cghgI0dx1mFiA8nb3NmOT8EAJ6c+oNSipdu+cKE2GnevHltIyMjHgB0xM6cLsO2lpaWpbqHNtJVpZdbSwChCB5CRF4CLUp6EQBOU0rtjuuQlPIbRPRY3Hwp7QtHvu4QsK/dvb29h42NjT0ffuqkxCKz7HcppXjvfqwkhODzfbHzxarkAGOeRWxtbV1WpCd/wj3tHoAzlEqlwy3L4sgWutEpkmKmm4983y+NjIyM6GZgOyEEz7ZpTevGKXcKW/40vn18fPxHRX0hjiUAbmBXVxfHq12HiHyb1bQnnTPwk50UQrBg+BaueiSePn+Bj4ZZlrW2Xhs5snI8tgC44vAbei0AnJyVI0nLSSgAJmlW0jqnyPd2SHhfa2vrM/39/fyy2hApkQDCls2SUt5KRDyW6kauzhqUpEPA6wBwXApndiIi7+rt832/r1oc3hTl55Y1jQD2OVkqlRZYlnULAKzMzev/V5ToJbBUKp1mWRbPUegm3uvAU9zcrfcNDg7yFwiP7w2fUgtgAoFyubyEiDhqFt94GRnAOAPkEn8Gct1CCI7izZE9q6X3iGg9Ez46OvrUrl27hjLwuXBFZCaAA1rGM4f8brACEfmoVS9/RYZfDjq7bmuBlMlE0AGiPTcIgisRkTdwcIj2TdytB0HQNzQ09LfCsVUHh/4L2C0r+bQpSjgAAAAASUVORK5CYII=" />
                        </defs>
                    </svg>
                </button>
            </div>
            <span class="text-[#898989] text-[0.625rem] mb-2 md:mb-0">Te enviamos este e-mail a infgmail.com.
                Administrar
                preferencias de e-mailsConoce cómo cuidamos tu Privacidad y visita los Términos y condiciones.</span>
        </div>
        <button onclick="successModal.showModal(); quotes_modal_p4.close()"
            class="w-full md:w-[14.32rem] h-[3.09rem] rounded-lg bg-[#FF7C32] text-white text-center font-medium mt-4 self-center mb-3">
            Enviar cotización</button>
        <span></span>
    </div>
</dialog>
