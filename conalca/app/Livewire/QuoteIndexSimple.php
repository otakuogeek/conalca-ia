<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

class QuoteIndexSimple extends Component
{
    public $test_message = "Componente Livewire funcionando correctamente";
    
    public function render()
    {
        Log::info('QuoteIndexSimple: render() llamado');
        return view('livewire.quote-index-simple');
    }
    
    public function mount()
    {
        Log::info('QuoteIndexSimple: mount() llamado');
    }
}