<?php

namespace App\Livewire;

use Livewire\Component;

class ModalPricing extends Component
{

    public $type_pricing;

    public function render()
    {
        return view('livewire.modal-pricing');
    }

}
