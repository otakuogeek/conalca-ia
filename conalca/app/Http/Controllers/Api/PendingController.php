<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pending;
use Illuminate\Support\Facades\Auth;

class PendingController extends Controller
{
    public function index($groupCotizationId)
    {
        $userId = Auth::id();
        return Pending::where('group_cotization_id', $groupCotizationId)
            ->where('user_id', $userId) 
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'group_cotization_id'  => 'required|exists:group_cotizations,id',
        ]);

        $pending = Pending::create([
            'title'                => $request->title,
            'description'          => $request->description,
            'user_id'              => Auth::id(),
            'group_cotization_id'  => $request->group_cotization_id,
            'done'                 => false,
        ]);

        return response()->json($pending, 201);
    }

    public function update(Request $request, $id)
    {
        $pending = Pending::findOrFail($id);

        $pending->update($request->only(['title', 'description', 'done']));

        return response()->json($pending);
    }

    public function destroy($id)
    {
        $pending = Pending::findOrFail($id);

        $pending->delete();

        return response()->json(['success' => true]);
    }
}
