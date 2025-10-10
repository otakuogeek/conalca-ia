<?php

namespace App\Livewire;


use Livewire\Component;
use App\Models\User;

class UserSearch extends Component
{
    public $searchTerm = '';
    public $selectedUsers = [];
    public $forForwarding = false;


    public function selectUser($userId, $userName)
    {
        // Add selected user to the list
        if (!in_array($userId, array_column($this->selectedUsers, 'id'))) {
            $this->selectedUsers[] = ['id' => $userId, 'name' => $userName];
        }

        // Clear the search term
        $this->searchTerm = '';

        // If for forwarding, directly forward the email
        if ($this->forForwarding) {
            // Emit the event to the parent component
            $this->emit('forwardEmailToUser', $userId);
        }
    }

    public function removeUser($userId)
    {
        // Remove user from the list
        $this->selectedUsers = array_filter($this->selectedUsers, function ($user) use ($userId) {
            return $user['id'] !== $userId;
        });
    }

    public function render()
    {
        $users = User::where('name', 'like', '%' . $this->searchTerm . '%')
            ->limit(5)
            ->get();

        return view('livewire.user-search', [
            'users' => $users,
        ]);
    }
}
