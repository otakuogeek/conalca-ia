<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing;
use App\Models\CotizacionModel;
use App\Models\Solicitation;
use Illuminate\Support\Facades\Log;
use App\Models\PercentageSetting;
use Illuminate\Support\Facades\DB;

class PricingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        $pricing = Pricing::create($data);

        // link back to solicitation
        if ($request->filled('solicitation_id')) {
            Solicitation::where('id', $request->solicitation_id)
                ->update([
                    'pricing_id' => $pricing->id,
                    'status'     => 'FINALIZED'
                ]);
        }
        return response()->json($pricing, 201);
    }

    public function update(Request $request, $id)
    {
        $pricing = Pricing::findOrFail($id);
        $pricing->update($request->all());
        return response()->json($pricing);
    }

    public function latestByRoute(Request $request)
    {
        $validated = $request->validate([
            'origin'        => 'required|string',
            'destination'   => 'required|string',
            'cargo_weight'  => 'nullable|numeric|min:0',
            'condition'     => 'nullable|string',
            'is_return'     => 'nullable|boolean',
        ]);

        $capacityMap = DB::table('vehiculos_pricing')
            ->pluck('peso_maximo', 'vehiculo_silogtran');

        $conditionProvided = !empty($validated['condition']);
        $isReturn = !empty($validated['is_return']);

        $query = Pricing::where('origin', $validated['origin'])
            ->where('destination', $validated['destination']);

        // Filter by condition when provided (e.g. IMPORTACION for import routes)
        if ($conditionProvided) {
            $query->where('condition', $validated['condition']);

            // For return routes: only show DEV CONT options
            // For main routes: exclude DEV CONT options
            if ($isReturn) {
                $query->where('type_pricing', 'dev_cont');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('type_pricing')
                       ->orWhere('type_pricing', '!=', 'dev_cont');
                });
            }
        } else {
            // Exclude special-condition pricings from normal route queries
            $query->where(function ($q) {
                $q->whereNull('condition')
                  ->orWhere('condition', '')
                  ->orWhere('condition', 'NACIONAL');
            });
        }

        $raw = $query->orderByDesc('updated_at')      // newest first
            ->orderBy('vehicle_type')        // deterministic
            ->orderByDesc('weight')
            ->get();

        // Fallback: if no condition was provided and nothing matched,
        // retry using all conditions for this route.
        if (!$conditionProvided && $raw->isEmpty()) {
            $fallbackQuery = Pricing::where('origin', $validated['origin'])
                ->where('destination', $validated['destination']);

            if ($isReturn) {
                $fallbackQuery->where('type_pricing', 'dev_cont');
            } else {
                $fallbackQuery->where(function ($q) {
                    $q->whereNull('type_pricing')
                      ->orWhere('type_pricing', '!=', 'dev_cont');
                });
            }

            $raw = $fallbackQuery->orderByDesc('updated_at')
                ->orderBy('vehicle_type')
                ->orderByDesc('weight')
                ->get();
        }

        $filtered = $raw->filter(function ($pricing) use ($validated, $capacityMap) {
            $vehicleKey = strtoupper(trim($pricing->vehicle_type));
            $catalogCapacity = $capacityMap[$vehicleKey] ?? null;

            // overwrite weight so front-end sees the official limit
            $pricing->weight = $catalogCapacity ?: ($pricing->weight ?? null);

            if (
                !empty($validated['cargo_weight']) &&
                $pricing->weight &&
                $validated['cargo_weight'] > $pricing->weight
            ) {
                // discard options that can’t carry the cargo
                return false;
            }
            return true;
        })->values();

        // NEW: keep only the cheapest option per vehicle_type
        $deduped = $filtered
            ->groupBy(fn ($p) => strtoupper(trim($p->vehicle_type)))
            ->map(function ($group) {
                // sort by price asc, tie-break by higher weight
                return $group->sort(function ($a, $b) {
                    $priceCmp = ($a->price ?? INF) <=> ($b->price ?? INF);
                    if ($priceCmp !== 0) {
                        return $priceCmp;
                    }
                    // tie-break: keep the one that carries more
                    return ($b->weight ?? 0) <=> ($a->weight ?? 0);
                })->first();
            })
            ->values();

        return response()->json($deduped);
    }

    public function suggestVehicles(Request $request)
    {
        Log::info('[PricingController] suggestVehicles called', [
            'request_data' => $request->all(),
        ]);

        // 1) Normalize incoming data
        $normalizedRoutes = collect($request->input('routes', []))->map(function ($route) {
            $route['pricings'] = collect($route['pricings'] ?? [])->map(function ($pricing) {
                $pricing['weight'] = $pricing['weight'] === '' ? null : $pricing['weight'];
                return $pricing;
            })->all();
            return $route;
        })->all();

        $request->merge(['routes' => $normalizedRoutes]);

        $validated = $request->validate([
            'routes' => 'required|array',
            'routes.*.ciudad_origen'   => 'required|string',
            'routes.*.ciudad_destino'  => 'required|string',
            'routes.*.peso_mercancia'  => 'nullable|numeric',
            'routes.*.pricings'        => 'array',
            'routes.*.pricings.*.id'            => 'required|integer',  
            'routes.*.pricings.*.vehicle_type' => 'required|string',
            'routes.*.pricings.*.price'        => 'required|numeric',
            'routes.*.pricings.*.weight'       => 'nullable|numeric',
        ]);

        // 2) Inject catalog capacities + drop overweight options
        $capacityMap = DB::table('vehiculos_pricing')
            ->pluck('peso_maximo', 'vehiculo_silogtran');

        $normalizedWithCapacities = collect($validated['routes'])->map(function ($route) use ($capacityMap) {
            $cargoWeight = $route['peso_mercancia'] ?? null;

            $route['pricings'] = collect($route['pricings'] ?? [])->filter(function ($pricing) use ($capacityMap, $cargoWeight) {
                $vehicleKey = strtoupper(trim($pricing['vehicle_type']));
                $catalogCapacity = $capacityMap[$vehicleKey] ?? null;

                $pricing['weight'] = $catalogCapacity ?: ($pricing['weight'] ?? null);

                if ($cargoWeight && $pricing['weight'] && $cargoWeight > $pricing['weight']) {
                    return false; // discard vehicles that cannot carry the load
                }

                return true;
            })->values()->all();

            return $route;
        })->all();

        if (collect($normalizedWithCapacities)->every(fn ($route) => empty($route['pricings']))) {
            return response()->json([
                'suggestions' => [],
                'message' => 'No viable vehicles found for the requested weights.',
            ]);
        }

        $routesPayload = $this->prepareRoutesPayload($normalizedWithCapacities);

        $suggestions = $this->generateRuleBasedSuggestions($routesPayload);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    private function prepareRoutesPayload(array $routes): array
    {
        $payload = [];

        foreach ($routes as $index => $route) {
            $payload[] = [
                'route_index'  => $index,
                'origin'       => $route['ciudad_origen'],
                'destination'  => $route['ciudad_destino'],
                'cargo_weight' => $route['peso_mercancia'] ?? 0,
                'options'      => array_map(function ($pricing) {
                    return [
                        'pricing_id'  => $pricing['id'] ?? null,
                        'vehicle_type' => $pricing['vehicle_type'],
                        'price'        => $pricing['price'],
                        'max_load_kg'  => $pricing['weight'] ?? null,
                    ];
                }, $route['pricings'] ?? []),
            ];
        }

        return $payload;
    }

    public function rentabilityStats(Request $request)
    {
        $validated = $request->validate([
            'origin'      => 'nullable|string',
            'destination' => 'nullable|string',
        ]);

        $baseQuery = CotizacionModel::query()
            ->whereNotNull('porcentaje');

        $queryToUse = $baseQuery->clone();
        $scope = 'global';

        if (!empty($validated['origin']) && !empty($validated['destination'])) {
            $routeQuery = (clone $baseQuery)
                ->where('ciudad_origen', $validated['origin'])
                ->where('ciudad_destino', $validated['destination']);

            if ($routeQuery->exists()) {
                $queryToUse = $routeQuery;
                $scope = 'route';
            }
        }

        $stats = $queryToUse
            ->selectRaw('MIN(porcentaje) as min_porcentaje, MAX(porcentaje) as max_porcentaje, AVG(porcentaje) as avg_porcentaje')
            ->first();

        return response()->json([
            'scope'       => $scope, // "route" if it found data for that route, otherwise "global"
            'min'         => round($stats->min_porcentaje ?? 0, 2),
            'max'         => round($stats->max_porcentaje ?? 0, 2),
            'avg'         => round($stats->avg_porcentaje ?? 0, 2),
        ]);
    }

    public function percentageSettings()
    {
        $setting = PercentageSetting::first();

        if (!$setting) {
            return response()->json([
                'use_custom' => false,
                'min' => null,
                'avg' => null,
                'max' => null,
            ]);
        }

        return response()->json([
            'use_custom' => (bool) $setting->use_custom_percentages,
            'min' => (float) $setting->min_percentage,
            'avg' => (float) $setting->avg_percentage,
            'max' => (float) $setting->max_percentage,
        ]);
    }

    public function vehicleCapacityGuide()
    {
        $records = DB::table('vehiculos_pricing')
            ->select('vehiculo_silogtran', 'tabla_pricing', 'peso_maximo')
            ->orderBy('tabla_pricing')
            ->orderBy('peso_maximo')
            ->get();

        return response()->json([
            'data' => $records,
        ]);
    }

    private function enforceCheapestSuggestions(array $routesPayload, array $aiSuggestions): array
    {
        foreach ($routesPayload as $route) {
            $routeIndex = $route['route_index'];

            $options = $route['options'] ?? [];
            if (empty($options)) {
                continue;
            }

            // Sort ascending by price so index 0 is always the cheapest valid option
            usort($options, fn($a, $b) =>
                ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX)
            );

            $cheapest = $options[0];

            $aiVehicleType = $aiSuggestions[$routeIndex]['vehicle_type'] ?? null;
            $matchedOption = collect($options)->firstWhere('vehicle_type', $aiVehicleType);

            // If AI didn't match any option (or picked a wrong vehicle), force the cheapest
            if (!$matchedOption) {
                $aiSuggestions[$routeIndex] = [
                    'vehicle_type' => $cheapest['vehicle_type'],
                    'pricing_id'   => $cheapest['pricing_id'] ?? null,
                    'reason'       => sprintf(
                        'Selección ajustada automáticamente: %s es la opción válida más económica (%s) para transportar %s kg.',
                        $cheapest['vehicle_type'],
                        number_format($cheapest['price'], 0, ',', '.'),
                        number_format($route['cargo_weight'] ?? 0, 0, ',', '.')
                    ),
                ];
                continue;
            }

            // Even if the vehicle matches, make sure we use the exact pricing row (ID)
            if (($matchedOption['vehicle_type'] ?? null) !== $cheapest['vehicle_type']) {
                // AI picked a more expensive option
                $aiSuggestions[$routeIndex] = [
                    'vehicle_type' => $cheapest['vehicle_type'],
                    'pricing_id'   => $cheapest['pricing_id'] ?? null,
                    'reason'       => sprintf(
                        'Selección ajustada automáticamente: %s es la opción válida más económica (%s) para transportar %s kg.',
                        $cheapest['vehicle_type'],
                        number_format($cheapest['price'], 0, ',', '.'),
                        number_format($route['cargo_weight'] ?? 0, 0, ',', '.')
                    ),
                ];
            } else {
                // AI picked the cheapest vehicle; just attach the specific pricing_id
                $aiSuggestions[$routeIndex]['pricing_id'] = $matchedOption['pricing_id'] ?? null;
            }
        }

        return $aiSuggestions;
    }

    private function generateRuleBasedSuggestions(array $routesPayload): array
    {
        $suggestions = [];

        foreach ($routesPayload as $route) {
            $routeIndex  = $route['route_index'];
            $cargoWeight = $route['cargo_weight'] ?? 0;
            $options     = $route['options'] ?? [];

            if (empty($options)) {
                $suggestions[$routeIndex] = [
                    'vehicle_type' => null,
                    'pricing_id'   => null,
                    'reason'       => 'No hay vehículos disponibles para esta ruta.',
                ];
                continue;
            }

            usort($options, fn ($a, $b) =>
                ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX)
            );

            $bestOption = null;
            foreach ($options as $option) {
                $maxLoad = $option['max_load_kg'] ?? null;

                if ($maxLoad === null || $cargoWeight === null || $cargoWeight <= $maxLoad) {
                    $bestOption = $option;
                    break;
                }
            }

            if (!$bestOption) {
                $suggestions[$routeIndex] = [
                    'vehicle_type' => null,
                    'pricing_id'   => null,
                    'reason'       => sprintf(
                        'Ninguna opción puede transportar %s kg.',
                        number_format($cargoWeight ?? 0, 0, ',', '.')
                    ),
                ];
                continue;
            }

            $priceLabel  = number_format($bestOption['price'], 0, ',', '.');
            $weightLabel = number_format($cargoWeight ?? 0, 0, ',', '.');

            $suggestions[$routeIndex] = [
                'vehicle_type' => $bestOption['vehicle_type'],
                'pricing_id'   => $bestOption['pricing_id'],
                'reason'       => sprintf(
                    'Se elige %s porque transporta %s kg al mejor precio disponible (%s).',
                    $bestOption['vehicle_type'],
                    $weightLabel,
                    $priceLabel
                ),
            ];
        }

        return $this->enforceCheapestSuggestions($routesPayload, $suggestions);
    }
}
