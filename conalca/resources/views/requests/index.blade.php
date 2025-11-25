@extends('layout.app')

@section('title', 'Solicitation')

@section('content')
    <div class="min-h-screen bg-gray-50" style="padding-top: 1.5rem; padding-bottom: 1.5rem;">
        <div class="w-full max-w-7xl mx-auto px-4">
            <div class="w-full h-full" id="solicitation-list"></div>
        </div>
    </div>
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection






