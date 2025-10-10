<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite('resources/css/app.css')
    <title>@yield('title') - CONALCA AI</title>
    @stack('styles')
    @stack('scripts')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">


</head>

<body>
    <main class="w-screen h-screen flex flex-col md:flex-row md:flex-wrap overflow-y-auto scrollbar-default">
        <article class="hidden md:flex flex-col items-center justify-center w-1/2 h-full bg-[#F3F5F9] relative">
            <div class="absolute top-0 left-0 flex w-full h-1/2 border-b-2 border-solid border-[#fff] z-10 bg-[#f3f5f933] backdrop-blur"
                style="-webkit-backdrop-filter: blur(5px)">
            </div>
            <span class="w-[17.88rem] h-[17.88rem] rounded-full bg-[#FF7C32]">
            </span>
        </article>
        <article class="flex flex-col bg-white w-full md:w-1/2 h-full overflow-x-hidden">
            <img src="{{ asset('img/logo-conalca.png') }}" alt="logo-conalca-ai"
                class="w-[12rem] md:w-[17.5rem] object-cover self-center mt-12" />
            <div class="self-center w-fit md:w-[32.20rem]">
                <h1 class="text-[#202020] font-medium text-3xl md:text-5xl leading-normal text-center md:text-left">
                    @yield('subTitle')
                </h1>
                <h6 class="text-[#626476] text-base font-normal leading-normal text-left mt-5">Plataforma
                    de Conalca SAS
                </h6>
            </div>
            @yield('content')
            <div class="flex flex-row flex-wrap items-center justify-center w-full gap-2">
                <img src="{{ asset('img/image 8.svg') }}" alt="servicio-de-itr" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 2.svg') }}" alt="servicio-de-distribucion" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 5.svg') }}" alt="servicio-de-carga-peligrosa" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 4.svg') }}" alt="transporte-masivo" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 6.svg') }}" alt="servicio-cargas-break" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 7.svg') }}" alt="transporte-hidrocarburos" class="w-[10%] h-auto" />
                <img src="{{ asset('img/image 9.svg') }}" alt="servicio-dat" class="w-[10%] h-auto" />
            </div>
        </article>
    </main>
</body>

</html>
