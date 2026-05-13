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
            'origin'         => 'required|string',
            'destination'    => 'required|string',
            'cargo_weight'   => 'nullable|numeric|min:0',
            'condition'      => 'nullable|string',
            'is_return'      => 'nullable|boolean',
            'container_size' => 'nullable|string|in:20,40',
        ]);

        Log::info('[PricingController] latestByRoute called', [
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'cargo_weight' => $validated['cargo_weight'] ?? null,
            'condition' => $validated['condition'] ?? null,
            'is_return' => $validated['is_return'] ?? null,
            'container_size' => $validated['container_size'] ?? null,
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

        // For IDA-REGRESO return routes: also fetch base IMPORTACION prices
        // so the commercial can see both options side by side
        $baseRaw = collect();
        if ($conditionProvided && $isReturn && str_contains($validated['condition'], 'IDA-REGRESO')) {
            $baseCondition = str_replace(' IDA-REGRESO', '', $validated['condition']);
            $baseQuery = Pricing::where('origin', $validated['origin'])
                ->where('destination', $validated['destination'])
                ->where('condition', $baseCondition)
                ->where('type_pricing', 'dev_cont')
                ->orderByDesc('updated_at')
                ->orderBy('vehicle_type')
                ->orderByDesc('weight')
                ->get();
            $baseRaw = $baseQuery;

            // Tag IDA-REGRESO entries so they appear as separate dropdown options
            $raw->each(function ($pricing) {
                $pricing->vehicle_type = $pricing->vehicle_type . ' (IDA-REGRESO)';
                $pricing->is_round_trip = true;
            });

            // If no IDA-REGRESO results exist, just show the base prices
            if ($raw->isEmpty()) {
                $raw = $baseRaw;
                $baseRaw = collect();
            }
        }

        // Fallback for IDA-REGRESO without base prices already fetched
        if ($conditionProvided && $raw->isEmpty() && !$baseRaw->count() && str_contains($validated['condition'] ?? '', 'IDA-REGRESO')) {
            $baseCondition = str_replace(' IDA-REGRESO', '', $validated['condition']);
            $fallbackQuery = Pricing::where('origin', $validated['origin'])
                ->where('destination', $validated['destination'])
                ->where('condition', $baseCondition);

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

        // Merge base + IDA-REGRESO into a single collection
        if ($baseRaw->count()) {
            $raw = $baseRaw->concat($raw);
        }

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

        // Filter by container size when provided:
        // container_size=20 → only CONTENEDOR 20' + non-container vehicles
        // container_size=40 → only CONTENEDOR 40' + non-container vehicles
        // If no container_size → show all options (frontend handles carga suelta filtering)
        $containerSize = $validated['container_size'] ?? null;
        if ($containerSize) {
            $raw = $raw->filter(function ($pricing) use ($containerSize) {
                $vt = strtoupper(trim($pricing->vehicle_type));
                if (!str_contains($vt, 'CONTENEDOR')) {
                    return true; // non-container types always shown
                }
                return str_contains($vt, $containerSize);
            });
        }

        $filtered = $raw->filter(function ($pricing) use ($validated, $capacityMap) {
            // Strip IDA-REGRESO tag for capacity lookup
            $vehicleKey = strtoupper(trim(str_replace(' (IDA-REGRESO)', '', $pricing->vehicle_type)));
            $catalogCapacity = $capacityMap[$vehicleKey] ?? null;

            // overwrite weight so front-end sees the official limit
            $pricing->weight = $catalogCapacity ?: ($pricing->weight ?? null);

            // For container types: parse weight from 'extra' field when weight is null
            // e.g. "40\" HASTA 25 TON. EXPRESO 2S3" -> 25000 (kg)
            if (
                !$pricing->weight &&
                $pricing->extra &&
                preg_match('/HASTA\s+([\d.,]+)\s*TON/i', $pricing->extra, $matches)
            ) {
                $tonValue = floatval(str_replace(',', '.', $matches[1]));
                $pricing->weight = $tonValue * 1000; // convert tons to kg
            }

            if (
                !empty($validated['cargo_weight']) &&
                $pricing->weight &&
                $validated['cargo_weight'] > $pricing->weight
            ) {
                // discard options that can't carry the cargo
                return false;
            }
            return true;
        })->values();

        // Keep only the best option per vehicle_type (or vehicle_type+extra for containers)
        $deduped = $filtered
            ->groupBy(function ($p) {
                $vt = strtoupper(trim($p->vehicle_type));
                // For container types with weight tiers (extra field), group by
                // vehicle_type + extra to preserve distinct tiers
                if (str_contains($vt, 'CONTENEDOR') && !empty($p->extra)) {
                    return $vt . '|' . strtoupper(trim($p->extra));
                }
                return $vt;
            })
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

        // Sort container options by weight ascending so the dropdown is organized
        // from lightest to heaviest tier (e.g. "hasta 10 TON" before "hasta 12 TON")
        $sorted = $deduped->sortBy(function ($p) {
            $vt = strtoupper(trim($p->vehicle_type));
            // Non-containers first, then containers sorted by weight
            if (!str_contains($vt, 'CONTENEDOR')) {
                return [0, $p->weight ?? 0];
            }
            return [1, $p->weight ?? 0];
        })->values();

        return response()->json($sorted);
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
            'routes.*.pricings.*.extra'        => 'nullable|string',
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
                        'pricing_id'   => $pricing['id'] ?? null,
                        'vehicle_type' => $pricing['vehicle_type'],
                        'price'        => $pricing['price'],
                        'max_load_kg'  => $pricing['weight'] ?? null,
                        'extra'        => $pricing['extra'] ?? null,
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

    /**
     * Sort options by tightest weight fit first (smallest max_load_kg that can carry the cargo),
     * then by price ascending as tiebreaker. This ensures the exact container+weight tier
     * is preferred (e.g. "20 hasta 12 Ton Expreso 2S2" for 11.3 TON cargo).
     */
    private function sortOptionsByBestFit(array $options, float $cargoWeight): array
    {
        usort($options, function ($a, $b) use ($cargoWeight) {
            $aMax = $a['max_load_kg'] ?? PHP_INT_MAX;
            $bMax = $b['max_load_kg'] ?? PHP_INT_MAX;

            // Both must be able to carry the cargo (already filtered upstream),
            // prefer the tightest fit (smallest capacity that still works)
            $aFit = ($aMax >= $cargoWeight) ? $aMax : PHP_INT_MAX;
            $bFit = ($bMax >= $cargoWeight) ? $bMax : PHP_INT_MAX;

            if ($aFit !== $bFit) {
                return $aFit <=> $bFit; // tightest fit first
            }

            // Same capacity tier → prefer cheapest price
            return ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX);
        });

        return $options;
    }

    private function enforceCheapestSuggestions(array $routesPayload, array $aiSuggestions): array
    {
        foreach ($routesPayload as $route) {
            $routeIndex = $route['route_index'];

            $options = $route['options'] ?? [];
            if (empty($options)) {
                continue;
            }

            $cargoWeight = $route['cargo_weight'] ?? 0;

            // Sort by tightest weight fit first, then cheapest price
            $options = $this->sortOptionsByBestFit($options, $cargoWeight);

            $bestOption = $options[0];

            $aiPricingId = $aiSuggestions[$routeIndex]['pricing_id'] ?? null;
            $matchedOption = $aiPricingId
                ? collect($options)->firstWhere('pricing_id', $aiPricingId)
                : null;

            // If AI didn't match any option, force the best-fit option
            if (!$matchedOption) {
                $extraLabel = !empty($bestOption['extra']) ? " ({$bestOption['extra']})" : '';
                $aiSuggestions[$routeIndex] = [
                    'vehicle_type' => $bestOption['vehicle_type'],
                    'pricing_id'   => $bestOption['pricing_id'] ?? null,
                    'reason'       => sprintf(
                        'Selección ajustada automáticamente: %s%s es la opción que mejor corresponde al peso de %s kg (%s).',
                        $bestOption['vehicle_type'],
                        $extraLabel,
                        number_format($cargoWeight, 0, ',', '.'),
                        number_format($bestOption['price'], 0, ',', '.')
                    ),
                ];
                continue;
            }

            // Even if the vehicle matches, check if it's the best-fit option
            if (($matchedOption['pricing_id'] ?? null) !== ($bestOption['pricing_id'] ?? null)) {
                // AI picked a different option than the best fit
                $extraLabel = !empty($bestOption['extra']) ? " ({$bestOption['extra']})" : '';
                $aiSuggestions[$routeIndex] = [
                    'vehicle_type' => $bestOption['vehicle_type'],
                    'pricing_id'   => $bestOption['pricing_id'] ?? null,
                    'reason'       => sprintf(
                        'Selección ajustada automáticamente: %s%s es la opción que mejor corresponde al peso de %s kg (%s).',
                        $bestOption['vehicle_type'],
                        $extraLabel,
                        number_format($cargoWeight, 0, ',', '.'),
                        number_format($bestOption['price'], 0, ',', '.')
                    ),
                ];
            } else {
                // AI picked the best-fit option; keep it
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

            // Sort by tightest weight fit first (exact container+weight tier match),
            // then by cheapest price as tiebreaker.
            // This ensures e.g. "20 hasta 12 Ton Expreso 2S2" is preferred over
            // "20 hasta 25 Ton Expreso 2S3" for 11.3 TON cargo.
            $options = $this->sortOptionsByBestFit($options, $cargoWeight);

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
            $extraLabel  = !empty($bestOption['extra']) ? " ({$bestOption['extra']})" : '';

            $suggestions[$routeIndex] = [
                'vehicle_type' => $bestOption['vehicle_type'],
                'pricing_id'   => $bestOption['pricing_id'],
                'reason'       => sprintf(
                    'Se elige %s%s porque corresponde exactamente al peso de %s kg (%s).',
                    $bestOption['vehicle_type'],
                    $extraLabel,
                    $weightLabel,
                    $priceLabel
                ),
            ];
        }

        return $this->enforceCheapestSuggestions($routesPayload, $suggestions);
    }
}
