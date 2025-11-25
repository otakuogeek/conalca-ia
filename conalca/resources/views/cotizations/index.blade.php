@extends('layout.app')

@section('title', 'User Admin Panel')

@section('content')
    <div class="w-full h-full space-y-8">
        <div id="sac-cotizations"></div>
    </div>
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection