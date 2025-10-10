<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CotizacionModel;
use App\Models\GroupCotization;

class SacCotizationController extends Controller
{
    public function groups(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['SAC','SUPER ADMIN','GERENTE DE CUENTA'])) {
            return response()->json(['error'=>'Unauthorized'],403);
        }

        $q = GroupCotization::query()
            ->with(['client:id,name',             // cliente del grupo
                    'cotizaciones.client:id,name' // cliente de cada modelo
            ])
            ->whereIn('status',['en tránsito','en facturación','facturado']);

        /* ---------- Filtros -----------------------------------------*/
        if ($request->filled('group_id'))   $q->where('id',$request->group_id);

        // Filtrar por CotizationModel.id dentro del grupo
        if ($request->filled('cotization_id')) {
            $q->whereHas('cotizaciones',fn($x)=>
                $x->where('id',$request->cotization_id));
        }

        if ($request->filled(['from','to'])) {
            $q->whereBetween('created_at',[
                $request->from.' 00:00:00',
                $request->to  .' 23:59:59'
            ]);
        }
        if ($request->filled('status')) $q->where('status',$request->status);

        return response()->json($q->get());
    }

    // 3.1  Listado con filtros
    public function index(Request $request)
    {
        $user = $request->user();

        // Sólo SAC, SUPER ADMIN y GERENTE DE CUENTA
        if (!$user->hasAnyRole(['SAC','SUPER ADMIN','GERENTE DE CUENTA'])) {
            return response()->json(['error'=>'Unauthorized'],403);
        }

        $q = CotizacionModel::query()
            ->with(['groupCotization','client'])   // eager loading
            ->whereHas('groupCotization', function($query){
                $query->whereIn('status',['en tránsito','en facturación','facturado']);
            });

        // Filtros
        if ($request->filled('id'))      $q->where('id',$request->id);
        if ($request->filled('status'))  $q->whereHas('groupCotization',
                                   fn($h)=>$h->where('status',$request->status));
        if ($request->filled(['from','to'])) {
            $q->whereBetween('created_at',
                 [$request->from.' 00:00:00',$request->to.' 23:59:59']);
        }

        return response()->json($q->get());
    }

    // 3.2  Actualizar estado del grupo
    public function updateGroupStatus(Request $request,$id)
    {
        $request->validate(['status'=>'required|in:en tránsito,en facturación,facturado']);
        $group = GroupCotization::findOrFail($id);

        $user = $request->user();
        if(!$user->hasAnyRole(['SAC','SUPER ADMIN','GERENTE DE CUENTA']))
            return response()->json(['error'=>'Unauthorized'],403);

        $group->status = $request->status;
        $group->save();

        return response()->json($group);
    }
}
