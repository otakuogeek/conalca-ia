@extends('layout.app')

@section('title', 'User Admin Panel')

@section('content')
    <div class="p-4">
        <div class="flex flex-row space-x-4">
            <div class="text-black mb-4 rounded flex-1">
            @livewire('quote-index')
            </div>
            <div id="my-goal-progress" class="flex-1"></div>
        </div>
            
      
        <div class="text-black rounded">
            <div id="channel-with-custom-columns"></div>
        </div>
        <div class="text-black p-4 rounded">
            @livewire('drog-zone')
        </div>
    </div>

    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endsection
