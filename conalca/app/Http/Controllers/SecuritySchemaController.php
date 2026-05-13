<?php

namespace App\Http\Controllers;

use App\Models\SecuritySchemaProduct;
use App\Models\SecuritySchemaPriceRange;
use App\Models\SecuritySchemaMeasure;
use App\Models\SecuritySchemaClientAssignment;
use App\Models\SecuritySchemaUserPriceRange;
use App\Models\SecuritySchemaUserMeasure;
use App\Models\Product;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecuritySchemaController extends Controller
{
    /**
     * Vista principal del módulo
     */
    public function index()
    {
        return view('security-schema.index');
    }

    /**
     * Obtener todos los datos del esquema (global, creado por Super Admin)
     * Incluye info de rol para controlar UI
     */
    public function getData()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('SUPER ADMIN');

        $products = SecuritySchemaProduct::orderBy('category')->orderBy('name')->get();

        $priceRanges = SecuritySchemaPriceRange::with(['measures'])
            ->orderBy('category')
            ->orderBy('price_from')
            ->get();

        $assignedClients = SecuritySchemaClientAssignment::with('client:id,codigo,cliente,documento')
            ->orderBy('created_at', 'desc')
            ->get();

        // Si es comercial, cargar sus overrides personalizados
        $userOverrides = [];
        if (!$isSuperAdmin) {
            $userOverrides = SecuritySchemaUserPriceRange::with(['measures'])
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('base_range_id');
        }

        return response()->json([
            'products' => $products->groupBy('category'),
            'price_ranges' => $priceRanges->groupBy('category'),
            'assigned_clients' => $assignedClients,
            'is_super_admin' => $isSuperAdmin,
            'user_overrides' => $userOverrides,
        ]);
    }

    // ─── Productos Alto Riesgo (Solo Super Admin) ────────────────

    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');
        $category = $request->input('category', '');
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        // Exclude products already in this specific category
        $alreadyAdded = SecuritySchemaProduct::where('category', $category)
            ->whereNotNull('product_code')
            ->pluck('product_code')
            ->toArray();

        $products = Product::where('producto_nombre', 'LIKE', '%' . $query . '%')
            ->whereNotIn('producto_codigo', $alreadyAdded)
            ->orderBy('producto_nombre')
            ->limit(20)
            ->get(['producto_codigo', 'producto_nombre']);

        return response()->json($products);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|integer|exists:products,producto_codigo',
            'category' => 'required|in:alto_riesgo_nivel_1,alto_riesgo_nivel_2,bajo_riesgo,bajo_riesgo_quimicos,no_amparada',
        ]);

        $dbProduct = Product::where('producto_codigo', $validated['product_code'])->firstOrFail();

        $exists = SecuritySchemaProduct::where('product_code', $validated['product_code'])
            ->where('category', $validated['category'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Producto ya agregado en esta categoría'], 422);
        }

        $product = SecuritySchemaProduct::create([
            'user_id' => Auth::id(),
            'name' => $dbProduct->producto_nombre,
            'product_code' => $dbProduct->producto_codigo,
            'category' => $validated['category'],
        ]);

        return response()->json($product, 201);
    }

    public function destroyProduct($id)
    {
        $product = SecuritySchemaProduct::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }

    // ─── Rangos de Precio (Solo Super Admin) ─────────────────────

    public function storePriceRange(Request $request)
    {
        $validated = $request->validate([
            'category'   => 'required|in:alto_riesgo_nivel_1,alto_riesgo_nivel_2,bajo_riesgo,bajo_riesgo_quimicos',
            'price_from' => 'nullable|integer|min:0',
            'price_to'   => 'required|integer|min:1',
            'nacional'   => 'required|array',
            'nacional.gps'                       => 'boolean',
            'nacional.candado_satelital'         => 'boolean',
            'nacional.acompanamiento_vehicular'  => 'integer|min:0|max:10',
            'nacional.acompanamiento_motorizado' => 'integer|min:0|max:10',
            'urbano'     => 'required|array',
            'urbano.gps'                         => 'boolean',
            'urbano.candado_satelital'           => 'boolean',
            'urbano.acompanamiento_vehicular'    => 'integer|min:0|max:10',
            'urbano.acompanamiento_motorizado'   => 'integer|min:0|max:10',
        ]);

        return DB::transaction(function () use ($validated) {
            $range = SecuritySchemaPriceRange::create([
                'user_id'    => Auth::id(),
                'category'   => $validated['category'],
                'price_from' => $validated['price_from'],
                'price_to'   => $validated['price_to'],
            ]);

            foreach (['nacional', 'urbano'] as $scope) {
                SecuritySchemaMeasure::create([
                    'price_range_id'             => $range->id,
                    'scope'                      => $scope,
                    'gps'                        => $validated[$scope]['gps'] ?? false,
                    'candado_satelital'          => $validated[$scope]['candado_satelital'] ?? false,
                    'acompanamiento_vehicular'   => $validated[$scope]['acompanamiento_vehicular'] ?? 0,
                    'acompanamiento_motorizado'  => $validated[$scope]['acompanamiento_motorizado'] ?? 0,
                ]);
            }

            return response()->json($range->load('measures'), 201);
        });
    }

    public function updatePriceRange(Request $request, $id)
    {
        $range = SecuritySchemaPriceRange::findOrFail($id);

        $validated = $request->validate([
            'price_from' => 'nullable|integer|min:0',
            'price_to'   => 'required|integer|min:1',
            'nacional'   => 'required|array',
            'nacional.gps'                       => 'boolean',
            'nacional.candado_satelital'         => 'boolean',
            'nacional.acompanamiento_vehicular'  => 'integer|min:0|max:10',
            'nacional.acompanamiento_motorizado' => 'integer|min:0|max:10',
            'urbano'     => 'required|array',
            'urbano.gps'                         => 'boolean',
            'urbano.candado_satelital'           => 'boolean',
            'urbano.acompanamiento_vehicular'    => 'integer|min:0|max:10',
            'urbano.acompanamiento_motorizado'   => 'integer|min:0|max:10',
        ]);

        return DB::transaction(function () use ($range, $validated) {
            $range->update([
                'price_from' => $validated['price_from'],
                'price_to'   => $validated['price_to'],
            ]);

            foreach (['nacional', 'urbano'] as $scope) {
                SecuritySchemaMeasure::updateOrCreate(
                    ['price_range_id' => $range->id, 'scope' => $scope],
                    [
                        'gps'                       => $validated[$scope]['gps'] ?? false,
                        'candado_satelital'         => $validated[$scope]['candado_satelital'] ?? false,
                        'acompanamiento_vehicular'  => $validated[$scope]['acompanamiento_vehicular'] ?? 0,
                        'acompanamiento_motorizado' => $validated[$scope]['acompanamiento_motorizado'] ?? 0,
                    ]
                );
            }

            return response()->json($range->load('measures'));
        });
    }

    public function destroyPriceRange($id)
    {
        $range = SecuritySchemaPriceRange::findOrFail($id);
        $range->delete();

        return response()->json(['message' => 'Rango eliminado']);
    }

    // ─── Asignación de Clientes (Solo Super Admin) ───────────────

    public function searchClients(Request $request)
    {
        $query = $request->input('q', '');
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $alreadyAssigned = SecuritySchemaClientAssignment::pluck('client_id')->toArray();

        $clients = Client::where(function ($q) use ($query) {
                $q->where('cliente', 'LIKE', '%' . $query . '%')
                  ->orWhere('documento', 'LIKE', '%' . $query . '%')
                  ->orWhere('codigo', 'LIKE', '%' . $query . '%');
            })
            ->whereNotIn('id', $alreadyAssigned)
            ->orderBy('cliente')
            ->limit(20)
            ->get(['id', 'codigo', 'cliente', 'documento']);

        return response()->json($clients);
    }

    public function getAssignedClients()
    {
        $clients = SecuritySchemaClientAssignment::with('client:id,codigo,cliente,documento')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($clients);
    }

    public function assignClient(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $exists = SecuritySchemaClientAssignment::where('client_id', $validated['client_id'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Cliente ya asignado'], 422);
        }

        $assignment = SecuritySchemaClientAssignment::create([
            'client_id'   => $validated['client_id'],
            'assigned_by' => Auth::id(),
        ]);

        $assignment->load('client:id,codigo,cliente,documento');

        return response()->json($assignment, 201);
    }

    public function unassignClient($id)
    {
        $assignment = SecuritySchemaClientAssignment::findOrFail($id);
        $assignment->delete();

        return response()->json(['message' => 'Cliente desasignado']);
    }

    // ─── Endpoint para PricingModal ──────────────────────────────

    public function getSchemaForPricing()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('SUPER ADMIN');

        $products = SecuritySchemaProduct::select('name', 'product_code', 'category')->get();

        $priceRanges = SecuritySchemaPriceRange::with('measures')
            ->get()
            ->groupBy('category');

        // Si es comercial, mezclar sus overrides
        $result = [
            'has_schema' => SecuritySchemaPriceRange::exists(),
            'high_risk_products' => $products->pluck('name')->toArray(),
            'high_risk_product_codes' => $products->pluck('product_code')->filter()->values()->toArray(),
            'products_by_category' => $products->groupBy('category'),
        ];

        foreach (['alto_riesgo_nivel_1', 'alto_riesgo_nivel_2', 'bajo_riesgo', 'bajo_riesgo_quimicos'] as $cat) {
            $ranges = $priceRanges->get($cat, collect())->values();

            if (!$isSuperAdmin) {
                // Aplicar overrides del comercial
                $userOverrides = SecuritySchemaUserPriceRange::with(['measures'])
                    ->where('user_id', $user->id)
                    ->whereIn('base_range_id', $ranges->pluck('id'))
                    ->get()
                    ->keyBy('base_range_id');

                $ranges = $ranges->map(function ($range) use ($userOverrides) {
                    if ($userOverrides->has($range->id)) {
                        $override = $userOverrides->get($range->id);
                        $range->price_from = $override->price_from;
                        $range->price_to = $override->price_to;
                        // Replace measures with user measures
                        if ($override->measures->isNotEmpty()) {
                            $range->setRelation('measures', $override->measures->map(function ($m) {
                                return new SecuritySchemaMeasure([
                                    'scope' => $m->scope,
                                    'gps' => $m->gps,
                                    'candado_satelital' => $m->candado_satelital,
                                    'acompanamiento_vehicular' => $m->acompanamiento_vehicular,
                                    'acompanamiento_motorizado' => $m->acompanamiento_motorizado,
                                ]);
                            }));
                        }
                    }
                    return $range;
                });
            }

            $result[$cat] = $ranges;
        }

        return response()->json($result);
    }

    // ─── Overrides de Rangos para Comerciales ────────────────────

    /**
     * Guardar o actualizar un override de rango por parte del comercial.
     * Validación: los valores nunca pueden ser menores a los del super admin.
     */
    public function storeUserOverride(Request $request)
    {
        $validated = $request->validate([
            'base_range_id' => 'required|integer|exists:security_schema_price_ranges,id',
            'price_from'    => 'nullable|integer|min:0',
            'price_to'      => 'required|integer|min:1',
            'nacional'      => 'required|array',
            'nacional.gps'                       => 'boolean',
            'nacional.candado_satelital'         => 'boolean',
            'nacional.acompanamiento_vehicular'  => 'integer|min:0|max:10',
            'nacional.acompanamiento_motorizado' => 'integer|min:0|max:10',
            'urbano'        => 'required|array',
            'urbano.gps'                         => 'boolean',
            'urbano.candado_satelital'           => 'boolean',
            'urbano.acompanamiento_vehicular'    => 'integer|min:0|max:10',
            'urbano.acompanamiento_motorizado'   => 'integer|min:0|max:10',
        ]);

        $baseRange = SecuritySchemaPriceRange::with('measures')->findOrFail($validated['base_range_id']);

        // Validar que los valores no sean menores al base
        $errors = [];

        if ($validated['price_to'] < $baseRange->price_to) {
            $errors[] = 'El valor "Hasta" no puede ser menor al configurado por el administrador (' . number_format($baseRange->price_to, 0, ',', '.') . ')';
        }

        if ($validated['price_from'] !== null && $baseRange->price_from !== null && $validated['price_from'] < $baseRange->price_from) {
            $errors[] = 'El valor "Desde" no puede ser menor al configurado por el administrador (' . number_format($baseRange->price_from, 0, ',', '.') . ')';
        }

        // Validar medidas de seguridad — nunca menores al base
        foreach (['nacional', 'urbano'] as $scope) {
            $baseMeasure = $baseRange->measures->firstWhere('scope', $scope);
            if (!$baseMeasure) continue;

            $input = $validated[$scope];
            $label = $scope === 'nacional' ? 'Nacionales' : 'Urbanos';

            // Si el admin tiene GPS activado, el comercial no puede desactivarlo
            if ($baseMeasure->gps && !($input['gps'] ?? false)) {
                $errors[] = "Recorridos $label: GPS no puede ser desactivado (requerido por el administrador)";
            }
            if ($baseMeasure->candado_satelital && !($input['candado_satelital'] ?? false)) {
                $errors[] = "Recorridos $label: Candado Satelital no puede ser desactivado (requerido por el administrador)";
            }
            if (($input['acompanamiento_vehicular'] ?? 0) < $baseMeasure->acompanamiento_vehicular) {
                $errors[] = "Recorridos $label: Acompañamiento Vehicular no puede ser menor a {$baseMeasure->acompanamiento_vehicular}";
            }
            if (($input['acompanamiento_motorizado'] ?? 0) < $baseMeasure->acompanamiento_motorizado) {
                $errors[] = "Recorridos $label: Acompañamiento Motorizado no puede ser menor a {$baseMeasure->acompanamiento_motorizado}";
            }
        }

        if (!empty($errors)) {
            return response()->json(['message' => implode('. ', $errors)], 422);
        }

        $user = Auth::user();

        return DB::transaction(function () use ($user, $validated, $baseRange) {
            $override = SecuritySchemaUserPriceRange::updateOrCreate(
                [
                    'user_id'       => $user->id,
                    'base_range_id' => $baseRange->id,
                ],
                [
                    'price_from' => $validated['price_from'],
                    'price_to'   => $validated['price_to'],
                ]
            );

            foreach (['nacional', 'urbano'] as $scope) {
                SecuritySchemaUserMeasure::updateOrCreate(
                    ['user_price_range_id' => $override->id, 'scope' => $scope],
                    [
                        'gps'                       => $validated[$scope]['gps'] ?? false,
                        'candado_satelital'         => $validated[$scope]['candado_satelital'] ?? false,
                        'acompanamiento_vehicular'  => $validated[$scope]['acompanamiento_vehicular'] ?? 0,
                        'acompanamiento_motorizado' => $validated[$scope]['acompanamiento_motorizado'] ?? 0,
                    ]
                );
            }

            return response()->json($override->load('measures'), 200);
        });
    }

    /**
     * Resetear un override del comercial (volver al valor base del admin)
     */
    public function deleteUserOverride($baseRangeId)
    {
        $user = Auth::user();

        $override = SecuritySchemaUserPriceRange::where('user_id', $user->id)
            ->where('base_range_id', $baseRangeId)
            ->first();

        if ($override) {
            $override->delete();
        }

        return response()->json(['message' => 'Override eliminado, se usará el rango del administrador']);
    }
}
