<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\ClientUserAssignment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('assignedUsers');
        
        // Role-based filtering
        if (auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            // Super admin and commercial manager see all clients
        } else {
            // Regular users see only assigned clients
            $query->whereHas('assignedUsers', function($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telefono', 'LIKE', "%{$search}%")
                  ->orWhere('direccion', 'LIKE', "%{$search}%")
                  ->orWhere('ciudad', 'LIKE', "%{$search}%");
            });
        }
        
        // Pagination
        $clients = $query->paginate(15);
        
        // Handle AJAX requests
        if ($request->ajax()) {
            return view('contacts.components.contactsTable', compact('clients'))->render();
        }
        
        return view('contacts.show', compact('clients'));
    }

    /**
     * Get client details for right panel
     */
    public function getClientDetails($id)
    {
        try {
            $client = Client::findOrFail($id);
            return response()->json($client);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'document' => 'required|string|unique:clients,documento',
            'phone_numbers' => 'nullable|string',
            'address' => 'nullable|string',
            'personal_cell' => 'nullable|string',
            'location' => 'nullable|string',
            'branch_office' => 'nullable|string',
            'email' => 'nullable|email|max:255',
        ]);

        $clientData = [
            'documento' => $request->document,
            'cliente' => $request->name,
            'direccion' => $request->address,
            'telefono' => $request->phone_numbers,
            'celular' => $request->personal_cell,
            'ciudad' => $request->location,
            'vendedor_nombre' => auth()->user()->name,
        ];

        // Solo agregar email si se proporciona
        if ($request->filled('email')) {
            $clientData['email'] = $request->email;
        }

        Client::create($clientData);

        return redirect()->route('contacts.show')->with('success', 'Cliente creado exitosamente.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'document' => 'required|string|unique:clients,documento,' . $request->id,
            'phone_numbers' => 'nullable|string',
            'address' => 'nullable|string',
            'personal_cell' => 'nullable|string',
            'location' => 'nullable|string',
            'branch_office' => 'nullable|string',
            'email' => 'nullable|email|max:255',
        ]);

        $client = Client::findOrFail($request->id);
        
        $updateData = [
            'documento' => $request->document,
            'cliente' => $request->name,
            'direccion' => $request->address,
            'telefono' => $request->phone_numbers,
            'celular' => $request->personal_cell,
            'ciudad' => $request->location,
        ];

        // Solo actualizar email si se proporciona
        if ($request->filled('email')) {
            $updateData['email'] = $request->email;
        }

        $client->update($updateData);

        return redirect()->route('contacts.show')->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Assign a user to a client (API endpoint)
     */
    public function assignUserToClient(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return response()->json(['error' => 'No tienes permisos para asignar clientes.'], 403);
        }

        $client = Client::findOrFail($request->client_id);
        $assignedUser = User::findOrFail($request->user_id);

        // Verificar que el usuario a asignar tenga rol comercial
        if (!$assignedUser->hasRole(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])) {
            return response()->json(['error' => 'Solo se pueden asignar usuarios con roles comerciales.'], 400);
        }

        // Verificar si ya existe la asignación
        $existingAssignment = ClientUserAssignment::where('client_id', $request->client_id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existingAssignment) {
            return response()->json(['error' => 'El usuario ya está asignado a este cliente.'], 400);
        }

        ClientUserAssignment::create([
            'client_id' => $request->client_id,
            'user_id' => $request->user_id,
            'assigned_by' => $user->id,
        ]);

        return response()->json(['success' => 'Usuario asignado al cliente exitosamente.']);
    }

    /**
     * Get assigned users for a client (API endpoint)
     */
    public function getClientAssignedUsers($id)
    {
        $client = Client::findOrFail($id);
        $assignedUsers = $client->assignedUsers()->get();

        return response()->json($assignedUsers);
    }

    /**
     * Remove user assignment from client (API endpoint)
     */
    public function removeUserFromClient(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return response()->json(['error' => 'No tienes permisos para desasignar clientes.'], 403);
        }

        $assignment = ClientUserAssignment::where('client_id', $request->client_id)
            ->where('user_id', $request->user_id)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'La asignación no existe.'], 404);
        }

        $assignment->delete();

        return response()->json(['success' => 'Usuario desasignado del cliente exitosamente.']);
    }

    /**
     * Get users with commercial roles (API endpoint)
     */
    public function getCommercialUsers()
    {
        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            return response()->json(['error' => 'No tienes permisos para ver usuarios comerciales.'], 403);
        }

        $commercialUsers = User::role(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])->get(['id', 'name', 'email']);

        return response()->json($commercialUsers);
    }
}
