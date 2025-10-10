<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CotizationNote;

class CotizationNoteController extends Controller
{
    public function index($id)
    {
        $notes = CotizationNote::where('cotization_model_id',$id)
                 ->with('author:id,name')
                 ->latest()->get();
        return response()->json($notes);
    }

    public function store(Request $request,$id)
    {
        $request->validate([
            'type' => 'required|in:alerta,novedad',
            'body' => 'required|string'
        ]);

        $note = CotizationNote::create([
            'cotization_model_id' => $id,
            'user_id'             => $request->user()->id,
            'type'                => $request->type,
            'body'                => $request->body,
        ]);

        return response()->json($note->load('author'),201);
    }
}
