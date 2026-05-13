<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Solicitation;
use App\Models\Pricing;
use Illuminate\Validation\Rule;

class SolicitationController extends Controller
{
    /* ───────────────────────── index ── */
    public function index(Request $request)
    {
        $user  = $request->user()->load('roles');
        $query = Solicitation::with('creator:id,name');

        /* ─── VISIBILIDAD POR ROL ────────────────
        – Pricing y Super-Admin ven todo
        – Comerciales (y demás roles comerciales)
            sólo sus propias solicitudes
        */
        if (!$user->hasAnyRole(['PRICING','SUPER ADMIN','API_PRICING'])) {
            // “comercial”, “asistente comercial”, “gerente de cuenta”, etc.
            $query->where('created_by', $user->id);
        }

        /* ─── FILTRO POR STATUS ───────────────── */
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        /* ─── FILTRO POR RANGO DE FECHAS ──────── */
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json(
            $query->orderByDesc('id')->get()
        );
    }

    /* ───────────────────────── store ── */
    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'origin'       => 'required|string',
            'destination'  => 'required|string',
            'description'  => 'nullable|string',
            'importance'   => ['required', Rule::in(['LOW','MEDIUM','HIGH'])]
        ]);

        // Only commercial roles may create
        if (!$user->hasAnyRole(['ASISTENTE COMERCIAL','GERENTE DE CUENTA','SAC','JEFE COMERCIAL','SUPER ADMIN'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $solicitation = Solicitation::create([
            'created_by'  => $user->id,
            'origin'      => $request->origin,
            'destination' => $request->destination,
            'description' => $request->description,
            'importance'  => $request->importance,
            'status'      => 'PENDING'
        ]);

        return response()->json($solicitation, 201);
    }

    /* ───────────────────────── show ─── */
    public function show($id)
    {
        $solicitation = Solicitation::with(['creator:id,name', 'messages.user:id,name'])
                        ->findOrFail($id);
        return response()->json($solicitation);
    }

    /* ───────────────────────── update ─ */
    public function update(Request $request, $id)
    {
        $solicitation = Solicitation::findOrFail($id);
        $user  = $request->user();

        // role check
        if ($user->id !== $solicitation->created_by && !$user->hasRole('PRICING') && !$user->hasRole('SUPER ADMIN')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'importance' => ['sometimes', Rule::in(['LOW','MEDIUM','HIGH'])],
            'status'     => ['sometimes', Rule::in(['PENDING','IN_PROCESS','ANSWERED','FINALIZED','SENT'])],
            'pricing_id' => ['sometimes', 'exists:pricings,id']
        ]);

        $solicitation->update($request->only(['importance','status','pricing_id']));
        return response()->json($solicitation);
    }

    /* ─────────────── send to next stage ─ */
    public function send(Request $request, $id)
    {
        $solicitation = Solicitation::findOrFail($id);
        $user = $request->user();

        switch (true) {
            // Commercial → Pricing
            case $user->id === $solicitation->created_by && $solicitation->status === 'FINALIZED':
                $solicitation->update(['status' => 'SENT']);
                break;

            // Pricing → SuperAdmin
            case $user->hasRole('PRICING') && $solicitation->status === 'PENDING':
                $solicitation->update(['status' => 'IN_PROCESS']);
                break;

            // SuperAdmin → Pricing (answer)
            case $user->hasRole('SUPER ADMIN') && $solicitation->status === 'IN_PROCESS':
                $solicitation->update(['status' => 'ANSWERED']);
                break;

            // Pricing finishes
            case $user->hasRole('PRICING') && $solicitation->status === 'ANSWERED':
                $solicitation->update(['status' => 'FINALIZED']);
                break;

            default:
                return response()->json(['error' => 'Invalid transition'], 422);
        }

        return response()->json($solicitation->refresh());
    }

    /* ───────────── assign / edit price (PRICING) ─ */
    public function assignPrice(Request $request, $id)
    {
        $sol = Solicitation::findOrFail($id);
        $user = $request->user();

        if (!$user->hasRole('PRICING')) return response()->json(['error'=>'Forbidden'],403);

        $request->validate([
            'price'        => 'required|numeric|min:0',
            'pricing_note' => 'nullable|string'
        ]);

        $sol->update([
            'price'        => $request->price,
            'pricing_note' => $request->pricing_note,
            'status'       => 'ANSWERED'
        ]);

        return response()->json($sol->refresh());
    }

    public function addNote(Request $request, $id)
    {
        $sol  = Solicitation::findOrFail($id);
        $user = $request->user();

        if (!$user->hasRole('SUPER ADMIN')) return response()->json(['error'=>'Forbidden'],403);

        $request->validate([
            'superadmin_note' => 'required|string'
        ]);

        $sol->update([
            'superadmin_note' => $request->superadmin_note,
            'status'          => 'ANSWERED'
        ]);

        return response()->json($sol->refresh());
    }

    public function escalateToSuperAdmin(Request $request, $id)
    {
        $sol  = Solicitation::findOrFail($id);
        $user = $request->user();

        if (!$user->hasRole('PRICING')) return response()->json(['error'=>'Forbidden'],403);

        $sol->update([
            'support_requested_at' => now(),
            'status'               => 'IN_PROCESS'
        ]);

        return response()->json($sol);
    }

    /* ───── Solicitud automática de ruta faltante en pricing ── */
    public function requestPricingRoute(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'routes'   => 'required|array|min:1',
            'routes.*.origin'      => 'required|string',
            'routes.*.destination'  => 'required|string',
            'group_id' => 'nullable|integer',
        ]);

        $created = [];

        foreach ($request->routes as $route) {
            $origin      = strtoupper(trim($route['origin']));
            $destination  = strtoupper(trim($route['destination']));

            // Avoid duplicates: skip if an open request already exists for the same route
            $exists = Solicitation::where('origin', $origin)
                ->where('destination', $destination)
                ->where('type', 'PRICING_ROUTE_REQUEST')
                ->whereNotIn('status', ['FINALIZED', 'REJECTED'])
                ->exists();

            if ($exists) continue;

            $created[] = Solicitation::create([
                'created_by'  => $user->id,
                'origin'      => $origin,
                'destination'  => $destination,
                'type'        => 'PRICING_ROUTE_REQUEST',
                'group_id'    => $request->group_id,
                'importance'  => 'HIGH',
                'status'      => 'PENDING',
                'description' => "Solicitud automática: No existen tarifas configuradas para la ruta {$origin} → {$destination}. Por favor crear pricing para esta ruta.",
            ]);
        }

        return response()->json([
            'success' => true,
            'created' => count($created),
            'message' => count($created) > 0
                ? count($created) . ' solicitud(es) creada(s) para el equipo de Pricing.'
                : 'Ya existen solicitudes pendientes para estas rutas.',
            'solicitations' => $created,
        ], 201);
    }
}
