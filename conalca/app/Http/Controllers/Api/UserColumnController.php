<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserColumn; 
use App\Models\GroupCotization; 
use Illuminate\Support\Facades\Auth;

class UserColumnController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return response()->json($user->userColumns()->orderBy('position')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
        ]);
        $user = Auth::user();
        $max_pos = $user->userColumns()->max('position') ?? 0;

        $column = $user->userColumns()->create([
            'name' => $request->name,
            'color' => $request->color,
            'position' => $max_pos + 1,
        ]);

        return response()->json($column, 201);
    }

    public function update(Request $request, UserColumn $column)
    {
        $this->authorize('update', $column); // Opcional: protege acceso sólo a usuario dueño

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:7',
        ]);

        $column->update($request->only(['name', 'color']));

        return response()->json($column);
    }

    public function destroy(UserColumn $column)
    {
        $columnName = $column->name;

        GroupCotization::where('status', $columnName)->update(['status' => 'borrador']);

        $column->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function reorder(Request $request)
    {
        $user = Auth::user();
        // Espera un array de ids en new_order
        $request->validate([
            'new_order' => 'required|array'
        ]);
        foreach ($request->new_order as $i => $col_id) {
            $column = $user->userColumns()->find($col_id);
            if ($column) {
                $column->position = $i;
                $column->save();
            }
        }

        return response()->json(['message' => 'ok']);
    }
}
