<?php

namespace App\Livewire;

use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class EmailIndex extends Component
{
    public $emails = [];
    public $selectedEmail;
    public $forwarding = false; // Variable para manejar la visualización del select y botón de reenviar
    public $selectedUserId; // Variable para almacenar el ID del usuario seleccionado
    public $users = [];
    public $isModalOpen = false;
    public $filterBy;
    public $filterByText = '';
    public $isOpen = false;
    public $isVisible = [];
    public $filterBySend = false;
    public $successMessage;
    public $searchTerm = '';
    public $selectedUsers = [];
    public $emailReSend = false;

    public function selectUser($userId, $userName)
    {
        // Add selected user to the list
        if (!in_array($userId, array_column($this->selectedUsers, 'id'))) {
            $this->selectedUsers[] = ['id' => $userId, 'name' => $userName];
        }

        // Clear the search term
        $this->searchTerm = '';
    }

    public function removeUser($userId)
    {
        $this->selectedUsers = array_filter($this->selectedUsers, function($user) use ($userId) {
            return $user['id'] != $userId;
        });
        // Reindex array to avoid issues with array keys
        $this->selectedUsers = array_values($this->selectedUsers);
    }

    public function updatedSearchTerm()
    {
        $this->reset('selectedUserId');
        if ($this->searchTerm) {
            $this->users = User::where('id', '!=', auth()->user()->id)
                ->where('name', 'like', '%' . $this->searchTerm . '%')
                ->limit(10)
                ->get();
        } else {
            $this->users = collect(); // Empty collection if no search term
        }
    }

    public function openEmailModal($emailId)
    {
        $this->selectedEmail = Email::with('from_user_relation', 'to_user_relation', 'parent.replies')->find($emailId);
        if (!$this->filterBySend) {
            $this->selectedEmail->update(['status' => 'read']);
        }
        $this->emailReSend = false;
        $this->isModalOpen = true;
        $this->forwarding = false; // Reseteamos la variable al abrir el modal
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->filterEmails();
        $this->selectedEmail = null;
    }

    public function mount()
    {
        $this->filterEmails();
    }

    public function render()
    {
        // Only load users if there's a search term, otherwise keep empty
        if (empty($this->searchTerm)) {
            $this->users = collect();
        }

        return view('livewire.email-index');
    }

    public function showForwarding()
    {
        $this->forwarding = true;
    }

    public function forwardEmail()
    {
        foreach ($this->selectedUsers as $user) {
            Email::create([
                'from_user' => auth()->id(),
                'to_user' => $user['id'],
                'subject' => $this->selectedEmail->subject,
                'description' => $this->selectedEmail->description,
                'files' => $this->selectedEmail->files,
            ]);
        }

        // Reset the selected users and search term
        $this->selectedUsers = [];
        $this->searchTerm = '';
        $this->emailReSend = true;
        // Optionally close the forwarding modal or reset the forwarding state
        $this->forwarding = false;
    }

    public function filterEmails()
    {
        // Obtener el ID del usuario autenticado
        $userId = auth()->user()->id;

        // Crear una consulta base aplicando el filtro de texto si existe
        $query = Email::with('from_user_relation', 'to_user_relation');
        if ($this->filterBySend) {
            $query->where('from_user', $userId);
        } else {
            $query->where('to_user', $userId);
        }
        // Aplicar el filtro de texto si filterByText tiene un valor
        if ($this->filterByText) {
            $text = $this->filterByText;
            $query->where(function ($query) use ($text) {
                $query->where('subject', 'like', '%' . $text . '%')
                    ->orWhereHas('from_user_relation', function ($query) use ($text) {
                        $query->where('name', 'like', '%' . $text . '%');
                    })
                    ->orWhereHas('to_user_relation', function ($query) use ($text) {
                        $query->where('name', 'like', '%' . $text . '%');
                    });
            });
        }

        // Aplicar el filtro basado en filterBy
        if ($this->filterBy == 'no-read') {
            $query->where('status', 'no-read');
        }

        if ($this->filterBy == 'today') {
            $query->whereDate('created_at', Carbon::today());
        }

        if ($this->filterBy == 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }

        // Obtener los resultados ordenados por fecha de creación descendente
        $this->emails = $query->orderBy('created_at', 'desc')->get();
    }

    // Este método se ejecuta cada vez que cambie la propiedad filterByText
    public function updatedFilterByText($value)
    {
        $this->filterEmails();
    }

    // Este método se ejecuta cada vez que cambie la propiedad filterBy
    public function updatedFilterBy($value)
    {
        $this->filterEmails();
    }

    public function openModal2()
    {
        $this->isOpen = true;
    }

    public function closeModal2()
    {
        $this->isOpen = false;
    }
    public function toggleVisibility($emailId)
    {
        if (isset($this->isVisible[$emailId])) {
            $this->isVisible[$emailId] = !$this->isVisible[$emailId];
        } else {
            $this->isVisible[$emailId] = true;
        }
    }

    public function setFilterSend()
    {
        $this->filterBySend = true;
        $this->filterEmails();
    }

    public function setNormal()
    {
        $this->filterBySend = false;
        $this->filterEmails();
    }
    public $count;
    public function getCount()
    {
        $this->count = Email::with('from_user_relation', 'to_user_relation')->where('to_user', auth()->user()->id)->where('status', 'no-read')->count();
        return $this->count;
    }
}
