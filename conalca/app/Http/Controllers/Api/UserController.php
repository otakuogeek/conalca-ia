<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $authUser = $request->user();

        // Inicia la consulta incluyendo los roles
        $query = User::query()->with('roles');

       if (!$authUser->hasRole('SUPER ADMIN')) {
            $userIds = DB::table('user_creators')
                ->where('created_by', $authUser->id)
                ->pluck('user_id')
                ->toArray(); // Asegúrate de convertirlo a array para poder usar array_push

            // Agregamos el ID del usuario autenticado
            $userIds[] = $authUser->id;

            // Eliminamos duplicados por si acaso
            $userIds = array_unique($userIds);

            // Si hay IDs, aplicamos el filtro
            if (!empty($userIds)) {
                $query->whereIn('id', $userIds);
            } else {
                return response()->json([]);
            }
        }


        if ($request->filled('role')) {
            $query->role($request->get('role'));
        }
        if ($request->filled('status')) {
            $query->where('active', $request->get('status'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }


        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|string',
        ]);

        // Validate role creation rights based on hierarchy
        if (!$this->canCreateRole($user, $request->role)) {
            return response()->json(['error' => 'Unauthorized role assignment'], 403);
        }

        if ($request->has('parent_id')) {
            $parent = User::find($request->parent_id);
            if (!$parent || !$this->canBeParentOf($parent, $request->role)) {
                return response()->json(['error' => 'Invalid parent assignment'], 422);
            }
        }

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active' => true,
        ]);

        $newUser->assignRole($request->role);
        \DB::table('user_creators')->insert([
            'user_id' => $newUser->id,
            'created_by' => $request->input('parent_id', $user->id), // usa parent_id o el creador actual
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json($newUser->load('roles'));
    }

    private function canBeParentOf(User $parent, $role)
    {
        $hierarchy = [
            'SUPER ADMIN' => ['SUPER ADMIN'],
            'JEFE COMERCIAL' => ['SUPER ADMIN'],
            'GERENTE DE CUENTA' => ['JEFE COMERCIAL'],
            'SAC' => ['GERENTE DE CUENTA', 'JEFE COMERCIAL'],
            'ASISTENTE COMERCIAL' => ['JEFE COMERCIAL'],
            'PRICING' => ['SUPER ADMIN'],
        ];

        $parentRoles = $parent->getRoleNames()->toArray();
        return isset($hierarchy[$role]) && array_intersect($parentRoles, $hierarchy[$role]);
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'active' => 'sometimes|boolean',
            'password' => 'nullable|min:6',
            'role' => 'sometimes|string',
        ]);

        $data = $request->only(['name', 'email', 'active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->filled('role')) {
            $currentUser = $request->user();
            $newRole = $request->input('role');

            //  No permitir que un usuario se cambie a sí mismo el rol
            if ($currentUser->id === $user->id) {
                return response()->json(['error' => 'You cannot change your own role'], 403);
            }

            //  Verificar si el nuevo rol está dentro de los roles asignables
            $assignableRoles = $this->getAssignableRolesList($currentUser);
            if (!in_array($newRole, $assignableRoles)) {
                return response()->json(['error' => 'Unauthorized role assignment'], 403);
            }

            $user->syncRoles([$newRole]);
        }

        return response()->json($user->load('roles'));
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->active = false;
        $user->save();

        return response()->json(['message' => 'User deactivated']);
    }

    private function canCreateRole($currentUser, $roleToAssign)
    {
        $hierarchy = [
            'SUPER ADMIN' => ['JEFE COMERCIAL', 'GERENTE DE CUENTA', 'ASISTENTE COMERCIAL', 'SAC', 'PRICING'],
            'JEFE COMERCIAL' => ['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL', 'SAC'],
            'GERENTE DE CUENTA' => ['SAC'],
        ];

        foreach ($currentUser->getRoleNames() as $role) {
            if (isset($hierarchy[$role]) && in_array($roleToAssign, $hierarchy[$role])) {
                return true;
            }
        }
        return $currentUser->hasRole('SUPER ADMIN');
    }

    private function getAssignableRolesList($user)
    {
        $hierarchy = [
            'SUPER ADMIN' => ['SUPER ADMIN','JEFE COMERCIAL', 'GERENTE DE CUENTA', 'ASISTENTE COMERCIAL', 'SAC', 'PRICING'],
            'JEFE COMERCIAL' => ['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL', 'SAC'],
            'GERENTE DE CUENTA' => ['SAC'],
        ];

        $assignable = [];

        foreach ($user->getRoleNames() as $role) {
            if (isset($hierarchy[$role])) {
                $assignable = array_merge($assignable, $hierarchy[$role]);
            }
        }

        if ($user->hasRole('SUPER ADMIN')) {
            $assignable = array_unique($hierarchy['SUPER ADMIN']);
        }

        return array_unique($assignable);
    }

    public function assignableRoles(Request $request)
    {
        $user = $request->user();
        return response()->json($this->getAssignableRolesList($user));
    }

    public function current(Request $request)
    {
        $user = $request->user()->load('roles');
        return response()->json($user);
    }

    public function potentialParents(Request $request)
    {
        $role = $request->query('role');
        $currentUser = $request->user();

        $parentRoles = [
            'JEFE COMERCIAL' => ['SUPER ADMIN'],
            'GERENTE DE CUENTA' => ['JEFE COMERCIAL'],
            'SAC' => ['GERENTE DE CUENTA', 'JEFE COMERCIAL'],
            'ASISTENTE COMERCIAL' => ['JEFE COMERCIAL'],
            'PRICING' => ['SUPER ADMIN'],
        ];

        if (!isset($parentRoles[$role])) {
            return response()->json([]);
        }

        $allowedParentRoles = $parentRoles[$role];

        $users = User::role($allowedParentRoles)->get(['id', 'name']);

        return response()->json($users);
    }

}
