@extends('auth.app')

@section('title')
    Solicitar contraseña
@endsection

@section('subTitle')
    Solicitar contraseña
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

    <form action="{{ route('auth.passwordRequestStore') }}" method="POST"
        class="flex flex-col self-center w-full md:w-[32.20rem] mt-10
    px-4 md:px-0 py-1 md:py-0 montserrat-thin">
        @csrf
        @error('email')
            <small class="text-red-500 text-xs font-normal leading-4 tracking-tight mb-1 montserrat-thin">{{ $message }}</small>
        @enderror
        <div class="border-solid border border-[#EAEAEA] relative w-full md:w-[30rem] h-14 rounded-md px-1 py-1">
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
        <button
            class="w-full md:w-[30rem] h-14 bg-[#FF7C32] rounded-[0.3125rem] text-center text-white text-sm font-medium
    tracking-[0.0175rem] leading-5 mt-10 mb-[17%] lg:mb-[25%] 2xl:mb-[35%] montserrat-thin">Solicitar</button>
    </form>
@endsection
