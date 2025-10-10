@extends('auth.app')

@section('title')
    Inicio de sesión
@endsection

@section('subTitle')
    <span class="bold-text">¡Bienvenido de nuevo!</span>
@endsection

@section('content')
    <style>
        @font-face {
            font-family: 'Montserrat Thin';
            src: url('{{ asset('static/Montserrat-Thin.ttf') }}') format('truetype');
            font-weight: 100;
            font-style: normal;
        }

        .montserrat-thin {
            font-family: 'Montserrat Thin', sans-serif;
        }
    </style>

    <form action="{{ route('auth.store') }}" method="POST"
        class="flex flex-col self-center w-full md:w-[32.20rem] mt-10
    px-4 md:px-0 py-1 md:py-0 montserrat-thin">
        @csrf
        @if (session('message'))
            <p class="text-red-500 my-2 text-sm text-center montserrat-thin">{{ session('message') }}</p>
        @endif
        @error('email')
            <small
                class="text-red-500 text-xs font-normal leading-4 tracking-tight mb-1 montserrat-thin">{{ $message }}</small>
        @enderror
        <div class="border-solid border border-[#EAEAEA] relative w-full md:w-[30rem] h-14 rounded-md px-1 py-1 mb-6">
            <label
                class="absolute left-3 -top-2 bg-white px-1 text-[#626476] text-xs font-normal leading-4 tracking-[0.015rem] montserrat-thin"
                for="email">
                E-mail
            </label>
            <input
                class="appearance-none border-none outline-none w-full h-full px-2 text-[#060C43] text-sm font-medium leading-5
        tracking-[0.0175rem] montserrat-thin"
                id="email" name="email" type="email" placeholder="exmaple@mail.com" value="{{ old('email') }}"
                required />
        </div>
        @error('password')
            <small
                class="text-red-500 text-xs font-normal leading-4 tracking-tight mb-1 montserrat-thin">{{ $message }}</small>
        @enderror




        <div class="border-solid border border-[#EAEAEA] relative w-full md:w-[30rem] h-14 rounded-md px-1 py-1 mb-6">
            <label
                class="absolute left-3 -top-2 bg-white px-1 text-[#626476] text-xs font-normal leading-4 tracking-[0.015rem] montserrat-thin"
                for="password">
                Contraseña
            </label>
            <input
                class="appearance-none border-none outline-none w-full h-full px-2 text-[#060C43] text-sm font-medium leading-5
        tracking-[0.0175rem] montserrat-thin"
                id="password" name="password" type="password" placeholder="Su contraseña" value="{{ old('password') }}"
                required />
            <button type="button" class="absolute right-3 top-3" id="toggle-password-1"
                style="font-size: 18px; color: #666;">
                <span class="input-icon" id="password-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2"
                        fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
            </button>
        </div>

        <script>
            const passwordInput1 = document.getElementById('password');
            const toggleButton1 = document.getElementById('toggle-password-1');
            const passwordIcon1 = document.getElementById('password-icon');

            toggleButton1.addEventListener('click', () => {
                if (passwordInput1.type === 'password') {
                    passwordInput1.type = 'text';
                    passwordIcon1.innerHTML = `
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>`;
                } else {
                    passwordInput1.type = 'password';
                    passwordIcon1.innerHTML = `
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>`;
                }
            });
        </script>






        <a href="{{ route('auth.passwordRequest') }}"
            class="text-black text-sm font-semibold leading-5 tracking-[0.0175rem] text-right md:mr-7 appearance-none
        hover:opacity-75 transition-opacity duration-200 montserrat-thin">¿Olvidaste
            la
            contraseña?</a>
        <button
            class="w-full md:w-[30rem] h-14 bg-[#FF7C32] rounded-[0.3125rem] text-center text-white text-sm font-medium
    tracking-[0.0175rem] leading-5 mt-8 montserrat-thin">Ingresar</button>
    </form>
    <form class="w-full md:w-[32.20rem] mt-14 md:self-center pl-5 md:pl-0 pr-5 md:pr-8 relative self-start montserrat-thin">
        @csrf
        <hr class="border border-solid border-[#EAEAEA]">
        <div class="absolute -top-3 w-full flex justify-center">
            <span class="bg-white px-3 flex text-center montserrat-thin">Ingresar con</span>
        </div>

    </form>
    <span
        class="mt-6 text-[#060C43] text-sm font-normal leading-5 tracking-[0.0175rem] self-center mb-4 md:md-[5.44rem] montserrat-thin">
        ¿No tienes una cuenta?
        <a href="#" onclick="event.preventDefault(); document.getElementById('adminModal').classList.remove('hidden');"
            class="font-semibold text-[#4C69FF] hover:opacity-75 transition-opacity duration-200 montserrat-thin">
            Comunícate con Admin
        </a>

        <!-- Modal -->
        <div id="adminModal" class="fixed inset-0 flex items-center justify-center z-50 hidden px-4"
            style="background-color: #00000082;">
            <div class="bg-white rounded-lg shadow-lg p-6 w-[80%] max-w-sm md:w-[30%] md:max-w-none text-center">
            <h2 class="text-lg font-semibold mb-4 montserrat-thin">Atención</h2>
            <p class="mb-6 montserrat-thin">
                Por favor comuníquese con el jefe comercial para poder generar una nueva contraseña de ingreso y continúe con el ingreso.
            </p>
            <button onclick="document.getElementById('adminModal').classList.add('hidden');"
                class="px-4 py-2 bg-[#FF7C32] text-white rounded montserrat-thin hover:bg-[#e66a1e] transition">
                Cerrar
            </button>
            </div>
        </div>

        <!-- Modal de Error de Base de Datos -->
        <div id="databaseErrorModal" class="fixed inset-0 flex items-center justify-center z-50 hidden px-4"
            style="background-color: #00000082;">
            <div class="bg-white rounded-lg shadow-lg p-6 w-[80%] max-w-sm md:w-[30%] md:max-w-none text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold mb-4 montserrat-thin text-red-600">Error de Conexión</h2>
                <p class="mb-6 montserrat-thin text-gray-700">
                    Se ha perdido la conexión con la base de datos. Por favor, intenta nuevamente en unos momentos.
                </p>
                <div class="flex justify-center space-x-4">
                    <button onclick="location.reload();"
                        class="px-4 py-2 bg-blue-600 text-white rounded montserrat-thin hover:bg-blue-700 transition">
                        Reintentar
                    </button>
                    <button onclick="document.getElementById('databaseErrorModal').classList.add('hidden');"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded montserrat-thin hover:bg-gray-400 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </span>

    <!-- Database Error Modal -->
    @if(session('database_error'))
            </div>

        @if(session('database_error'))
        <!-- Modal de Error de Base de Datos -->
        <div id="databaseErrorModal" class="fixed inset-0 flex items-center justify-center z-50 px-4"
            style="background-color: #00000082;">
            <div class="bg-white rounded-lg shadow-lg p-6 w-[80%] max-w-sm md:w-[30%] md:max-w-none text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold mb-4 montserrat-thin text-red-600">Error de Conexión</h2>
                <p class="mb-6 montserrat-thin text-gray-700">
                    Se ha perdido la conexión con la base de datos. Por favor, intenta nuevamente en unos momentos.
                </p>
                <div class="flex justify-center space-x-4">
                    <button onclick="location.reload();"
                        class="px-4 py-2 bg-blue-600 text-white rounded montserrat-thin hover:bg-blue-700 transition">
                        Reintentar
                    </button>
                    <button onclick="document.getElementById('databaseErrorModal').classList.add('hidden');"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded montserrat-thin hover:bg-gray-400 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
        @endif
    </span>
@endsection
    @endif
@endsection
