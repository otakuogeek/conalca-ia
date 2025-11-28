<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing;
use App\Models\CotizacionModel;
use App\Models\Solicitation;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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
            'origin'      => 'required|string',
            'destination' => 'required|string',
        ]);

        $pricings = Pricing::where('origin', $validated['origin'])
            ->where('destination', $validated['destination'])
            ->orderBy('vehicle_type')
            ->orderByDesc('updated_at')
            ->get()
            ->unique('vehicle_type')
            ->values();

        return response()->json($pricings);
    }

    public function suggestVehicles(Request $request)
    {
        Log::info('[PricingController] suggestVehicles called', [
            'request_data' => $request->all(),
        ]);

        // Normalize "" weights to null before validating
        $normalizedRoutes = collect($request->input('routes', []))->map(function ($route) {
            $route['pricings'] = collect($route['pricings'] ?? [])->map(function ($pricing) {
                $pricing['weight'] = $pricing['weight'] === '' ? null : $pricing['weight'];
                return $pricing;
            })->all();
            return $route;
        })->all();

        // Overwrite the request payload so the validator sees the normalized data
        $request->merge(['routes' => $normalizedRoutes]);

        Log::info('[PricingController] Routes after normalization', [
            'routes' => $normalizedRoutes,
        ]);

        // Validate
        $validated = $request->validate([
            'routes' => 'required|array',
            'routes.*.ciudad_origen'   => 'required|string',
            'routes.*.ciudad_destino'  => 'required|string',
            'routes.*.peso_mercancia'  => 'nullable|numeric',
            'routes.*.pricings'        => 'array',
            'routes.*.pricings.*.vehicle_type' => 'required|string',
            'routes.*.pricings.*.price'        => 'required|numeric',
            'routes.*.pricings.*.weight'       => 'nullable|numeric',
        ]);

        Log::info('[PricingController] Validation passed', ['validated' => $validated]);

        // Build prompt
        $prompt = $this->buildPrompt($validated['routes']);
        Log::info('[PricingController] Prompt built', ['prompt' => $prompt]);

        try {
            Log::info('[PricingController] Sending request to OpenAI', [
                'model' => 'gpt-4o-mini',
                'routes_count' => count($validated['routes']),
            ]);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente logístico. Recomienda la mejor opción por ruta y responde SIEMPRE en español usando el siguiente formato JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            Log::info('[PricingController] OpenAI response received', [
                'response_meta' => [
                    'usage' => $response->usage ?? null,
                    'choices_count' => count($response->choices ?? []),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[PricingController] OpenAI call failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Could not get vehicle suggestions',
            ], 500);
        }

        // Raw content
        $content = $response->choices[0]->message->content ?? '';
        Log::info('[PricingController] Raw OpenAI content', ['content' => $content]);

        $suggestions = json_decode($content, true);
        Log::info('[PricingController] Parsed suggestions', ['suggestions' => $suggestions]);

        if (!$suggestions || !isset($suggestions['suggestions'])) {
            Log::warning('[PricingController] Invalid AI response', ['content' => $content]);
            return response()->json([
                'message' => 'Invalid AI response format',
            ], 500);
        }

        Log::info('[PricingController] Returning suggestions', [
            'suggestions_count' => count($suggestions['suggestions']),
        ]);

        return response()->json($suggestions);
    }

    private function buildPrompt(array $routes): string
    {
        $payload = [];

        foreach ($routes as $index => $route) {
            $payload[] = [
                'route_index' => $index,
                'origin'      => $route['ciudad_origen'],
                'destination' => $route['ciudad_destino'],
                'cargo_weight'=> $route['peso_mercancia'] ?? 0,
                'options'     => array_map(function ($pricing) {
                    return [
                        'vehicle_type' => $pricing['vehicle_type'],
                        'price'        => $pricing['price'],
                        'max_load_kg'  => $pricing['weight'] ?? null,
                    ];
                }, $route['pricings'] ?? []),
            ];
        }

        $routesJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return 'Given these routes and vehicle options (each option includes price and max_load_kg) '
            . 'decide the best vehicle_type for each route so that the cargo weight never exceeds max_load_kg. '
            . 'If the cargo is heavier, prefer the option that minimizes the number of trips or combine multiple trips. '
            . 'Respond as JSON: { "suggestions": { "<route_index>": { "vehicle_type": "...", "reason": "..." } } }. '
            . 'Routes payload: ' . $routesJson;
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
}
