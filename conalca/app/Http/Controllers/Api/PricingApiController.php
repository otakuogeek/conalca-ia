<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingApiController extends Controller
{
    /* ============ LISTAR ============ */
    public function index()
    {
        return Pricing::all();
    }

    /* ============ CREAR (uno) ============ */
    public function store(Request $request)
    {
        $data    = $this->validateData($request);
        $pricing = Pricing::create($data);

        return response()->json($pricing, 201);
    }

    /* ============ CREAR (varios)  ============ */
    public function bulkStore(Request $request)           //  ← ← NUEVO
    {
        $request->validate([
            'items'   => 'required|array|min:1',
            'items.*' => 'required|array',
        ]);

        $created = [];

        DB::transaction(function () use ($request, &$created) {
            foreach ($request->input('items') as $index => $row) {

                // Validamos cada “item” de forma individual
                $validator = validator($row, $this->rules());
                if ($validator->fails()) {
                    abort(response()->json([
                        'message' => "Error en el item #{$index}",
                        'errors'  => $validator->errors()
                    ], 422));
                }

                $created[] = Pricing::create($row);
            }
        });

        return response()->json([
            'message'  => 'Registros creados correctamente',
            'total'    => count($created),
            'pricings' => $created
        ], 201);
    }

    /* ============ ACTUALIZAR VARIOS ============ */
    public function bulkUpdate(Request $request)
    {
        // 1. Validación general del payload
        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|integer|distinct|exists:pricings,id',

            // Campos que sí pueden venir (todos opcionales, "sometimes")
            'items.*.origin'       => 'sometimes|string|max:255',
            'items.*.destination'  => 'sometimes|string|max:255',
            'items.*.vehicle_type' => 'sometimes|string|max:255',
            'items.*.weight'       => 'sometimes|numeric',
            'items.*.price'        => 'sometimes|numeric',
        ]);

        $updated = [];

        DB::transaction(function () use ($request, &$updated) {
            foreach ($request->input('items') as $index => $row) {

                // 2. Validación extra por item (opcional, para mensajes más detallados)
                $validator = validator(
                    $row,
                    array_merge(
                        ['id' => 'required|integer|exists:pricings,id'],
                        $this->rules(fn($f) => "sometimes|$f")   // mismas reglas pero opcionales
                    )
                );

                if ($validator->fails()) {
                    abort(response()->json([
                        'message' => "Error en el item #{$index}",
                        'errors'  => $validator->errors()
                    ], 422));
                }

                // 3. Actualizamos el registro
                $pricing = Pricing::find($row['id']);

                // Quitamos el ID del array antes de hacer update
                $data = collect($row)->except('id')->toArray();

                $pricing->update($data);
                $updated[] = $pricing->refresh();   // devolvemos el modelo actualizado
            }
        });

        return response()->json([
            'message'  => 'Registros actualizados correctamente',
            'total'    => count($updated),
            'pricings' => $updated,
        ], 200);
    }

    /* ============ VER (uno) ============ */
    public function show(Pricing $pricing)
    {
        return $pricing;
    }

    /* ============ ACTUALIZAR ============ */
    public function update(Request $request, Pricing $pricing)
    {
        $data = $this->validateData($request, false);
        $pricing->update($data);

        return $pricing;
    }

    /* ============ ELIMINAR ============ */
    public function destroy($id)
    {
        $pricing = Pricing::find($id);

        if (! $pricing) {
            return response()->json([
                'message' => "El registro con ID {$id} no fue encontrado."
            ], 404);
        }

        $pricing->delete();

        return response()->json([
            'message' => 'Registro eliminado exitosamente.'
        ], 200);
    }

    /* ─────────── Helpers ─────────── */
    private function validateData(Request $request, bool $required = true): array
    {
        $rule = fn($field) => $required ? "required|$field" : "sometimes|$field";
        return $request->validate($this->rules($rule));
    }

    private function rules(callable $rule = null): array  //  ← Auxiliar
    {
        $rule = $rule ?: fn($f) => "required|$f";

        return [
            'origin'       => $rule('string|max:255'),
            'destination'  => $rule('string|max:255'),
            'vehicle_type' => $rule('string|max:255'),
            'weight'       => $rule('numeric'),
            'price'        => $rule('numeric'),
        ];
    }

        /* ============ ELIMINAR VARIOS ============ */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:pricings,id',
        ]);

        $ids      = $request->input('ids');
        $quantity = count($ids);

        DB::transaction(function () use ($ids) {
            Pricing::whereIn('id', $ids)->delete();
        });

        return response()->json([
            'message' => "Se eliminaron {$quantity} registros.",
            'ids'     => $ids
        ], 200);
    }
}
