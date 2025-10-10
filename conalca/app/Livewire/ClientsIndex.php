<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\User;
use App\Models\ClientUserAssignment;
use Livewire\Component;
use Livewire\WithPagination;

class ClientsIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showAssignModal = false;
    public $selectedClientId = null;
    public $selectedUserId = null;
    public $assignedUsers = [];

    protected $queryString = ['search'];

    public function render()
    {
        $user = auth()->user();
        $clients = collect();

        if ($user->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            // SUPER ADMIN y JEFE COMERCIAL pueden ver todos los clientes
            $clients = Client::when($this->search, function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('company_name', 'like', '%' . $this->search . '%')
                            ->orWhere('document', 'like', '%' . $this->search . '%');
            })->with(['assignedUsers' => function($query) {
                $query->select('users.id', 'users.name');
            }])->paginate(15);
        } else {
            // Comerciales solo ven sus clientes asignados
            $clients = $user->assignedClients()
                ->when($this->search, function ($query) {
                    return $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('company_name', 'like', '%' . $this->search . '%')
                            ->orWhere('document', 'like', '%' . $this->search . '%');
                })->paginate(15);
        }

        return view('livewire.clients-index', compact('clients'));
    }

    public function openAssignModal($clientId)
    {
        if (!auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return;
        }

        $this->selectedClientId = $clientId;
        $this->assignedUsers = ClientUserAssignment::where('client_id', $clientId)
            ->with('user:id,name')
            ->get()
            ->pluck('user.id')
            ->toArray();
        $this->showAssignModal = true;
    }

    public function assignUser()
    {
        if (!auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return;
        }

        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'selectedClientId' => 'required|exists:clients,id'
        ]);

        // Verificar que el usuario a asignar tenga rol comercial
        $userToAssign = User::find($this->selectedUserId);
        if (!$userToAssign->hasAnyRole(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])) {
            session()->flash('error', 'Solo se pueden asignar usuarios con roles comerciales.');
            return;
        }

        // Verificar si ya está asignado
        $existingAssignment = ClientUserAssignment::where('client_id', $this->selectedClientId)
            ->where('user_id', $this->selectedUserId)
            ->exists();

        if (!$existingAssignment) {
            ClientUserAssignment::create([
                'client_id' => $this->selectedClientId,
                'user_id' => $this->selectedUserId,
                'assigned_by' => auth()->id()
            ]);

            // Actualizar la lista de usuarios asignados
            $this->assignedUsers[] = $this->selectedUserId;
            session()->flash('success', 'Cliente asignado correctamente.');
        } else {
            session()->flash('error', 'El cliente ya está asignado a este usuario.');
        }

        $this->selectedUserId = null;
    }

    public function removeAssignment($userId)
    {
        if (!auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return;
        }

        ClientUserAssignment::where('client_id', $this->selectedClientId)
            ->where('user_id', $userId)
            ->delete();

        $this->assignedUsers = array_filter($this->assignedUsers, function($id) use ($userId) {
            return $id != $userId;
        });

        session()->flash('success', 'Asignación removida correctamente.');
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedClientId = null;
        $this->selectedUserId = null;
        $this->assignedUsers = [];
    }

    public function getCommercialUsersProperty()
    {
        return User::role(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])
            ->where('active', true)
            ->select('id', 'name')
            ->get();
    }
}
