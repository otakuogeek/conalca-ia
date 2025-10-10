@extends('layout.app')

@section('title')
    {{ 'Gestión de Correo Electrónico' }}
@endsection

@push('styles')
    <style>
        .email-container {
            min-height: calc(100vh - 120px);
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .bg-orange-25 {
            background-color: rgba(255, 124, 50, 0.05);
        }
        
        .scrollbar-default::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar-default::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .scrollbar-default::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .scrollbar-default::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
@endpush

@section('content')
    <div class="email-container w-100">
        @livewire('email-index', ['successMessage' => session('success')])
    </div>
@endsection
