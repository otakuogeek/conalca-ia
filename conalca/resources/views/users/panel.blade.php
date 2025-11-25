@extends('layout.app')

@section('title', 'User Admin Panel')

@section('content')
    <div class="w-full h-full" id="user-panel"></div>
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection






