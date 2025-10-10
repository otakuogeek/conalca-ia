<?php

namespace App\Http\Middleware;

use App\Helpers\ProductHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class NormalizeProductCodes
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Campos que pueden contener códigos de productos a normalizar
        $productFields = [
            'producto_codigo',
            'producto',
            'product_code',
            'productCode'
        ];

        // Verificar si la petición contiene algún campo de producto
        $needsNormalization = false;
        foreach ($productFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                
                // Solo normalizar si no es numérico
                if (!is_numeric($value) && is_string($value)) {
                    $normalizedCode = ProductHelper::normalizeProductCode($value);
                    
                    if ($normalizedCode !== null) {
                        $request->merge([$field => $normalizedCode]);
                        $needsNormalization = true;
                        
                        Log::info("NormalizeProductCodes: Normalizado campo '$field' de '$value' a '$normalizedCode'");
                    }
                }
            }
        }

        // Si se normalizó algún campo, logearlo para debugging
        if ($needsNormalization) {
            Log::info("NormalizeProductCodes: Petición normalizada", [
                'url' => $request->url(),
                'method' => $request->method(),
                'normalized_fields' => array_intersect_key($request->all(), array_flip($productFields))
            ]);
        }

        return $next($request);
    }
}
