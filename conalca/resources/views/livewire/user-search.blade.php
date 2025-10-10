<div>
    <!-- Search Input -->
    <div class="mb-4">
        <label for="user-search" class="block text-sm font-medium text-gray-700">Buscar Usuario</label>
        <input type="text" id="user-search" wire:model.live="searchTerm"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Ingrese el nombre de usuario...">
    </div>

    <!-- Search Results -->
    @if($searchTerm && $users->isNotEmpty())
        <div class="mb-4">
            <ul class="bg-white border rounded-md shadow-lg max-h-40 overflow-auto">
                @foreach($users as $user)
                    <li wire:click="selectUser('{{ $user->id }}', '{{ $user->name }}')"
                        class="p-2 cursor-pointer hover:bg-gray-100">{{ $user->name }}</li>
                @endforeach
            </ul>
        </div>
    @elseif($searchTerm && $users->isEmpty())
        <div class="mb-4">
            <p class="text-sm text-gray-500">No se encontraron usuarios.</p>
        </div>
    @endif

    <!-- Selected Users -->
    @if($selectedUsers)
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Usuarios Seleccionados</label>
            <ul class="mt-1 space-y-2">
                @foreach($selectedUsers as $user)
                    <li class="flex items-center justify-between">
                        <span>{{ $user['name'] }}</span>
                        <button type="button" wire:click="removeUser('{{ $user['id'] }}')"
                            class="text-red-500 hover:text-red-700">
                            Quitar
                        </button>
                        <input type="hidden" name="to_users[]" value="{{ $user['id'] }}">
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
