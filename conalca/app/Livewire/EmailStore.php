<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Email;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmailStore extends Component
{

    public $users;
    public $isOpen = false;

    public function mount()
    {
        $this->users = User::where('user_id' , '!=', auth()->user()->id)->get();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function render()
    {
        return view('livewire.email-store');
    }
}
