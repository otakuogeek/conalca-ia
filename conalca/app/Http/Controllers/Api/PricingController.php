<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing;
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
        $validated = $request->validate([
            'routes' => 'required|array',
            'routes.*.ciudad_origen'   => 'required|string',
            'routes.*.ciudad_destino'  => 'required|string',
            'routes.*.peso_mercancia'  => 'nullable|numeric',
            'routes.*.pricings'        => 'array',
            'routes.*.pricings.*.vehicle_type' => 'required|string',
            'routes.*.pricings.*.price'        => 'required|numeric',
        ]);

        $prompt = $this->buildPrompt($validated['routes']);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente logístico. Recomienda la mejor opción por ruta y responde SIEMPRE en español usando el siguiente formato JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('[PricingController] OpenAI call failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Could not get vehicle suggestions',
            ], 500);
        }

        $content = $response->choices[0]->message->content ?? '';
        \Log::debug('[PricingController] Raw OpenAI content', ['content' => $content]);

        $suggestions = json_decode($content, true);

        if (!$suggestions || !isset($suggestions['suggestions'])) {
            \Log::warning('[PricingController] Invalid AI response', ['content' => $content]);
            return response()->json([
                'message' => 'Invalid AI response format',
            ], 500);
        }

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
                'weight'      => $route['peso_mercancia'] ?? 0,
                'options'     => array_map(function ($pricing) {
                    return [
                        'vehicle_type' => $pricing['vehicle_type'],
                        'price'        => $pricing['price'],
                    ];
                }, $route['pricings'] ?? []),
            ];
        }

        return 'Given these route options: ' . json_encode($payload) . '. '
            . 'Pick the best vehicle_type for each route considering weight and price. '
            . 'Respond as JSON: { "suggestions": { "<route_index>": { "vehicle_type": "...", "reason": "..." } } }';
    }
}
