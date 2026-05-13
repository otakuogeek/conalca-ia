<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Normaliza texto: elimina acentos, convierte a mayúsculas
     */
    private function normalizeText($text)
    {
        $text = mb_strtoupper(trim($text));
        
        // Reemplazar caracteres acentuados
        $acentos = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü', 'á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'];
        $sinAcentos = ['A', 'E', 'I', 'O', 'U', 'N', 'U', 'A', 'E', 'I', 'O', 'U', 'N', 'U'];
        
        return str_replace($acentos, $sinAcentos, $text);
    }

    public function index(Request $request)
    {
        // Cachear productos por 24h — catálogo estático
        $products = Cache::remember('catalog_products', 60 * 60 * 24, function () {
            return Product::orderBy('producto_nombre')->get([
                'producto_codigo',
                'producto_nombre',
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ])->header('Cache-Control', 'public, max-age=3600');
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'error' => 'Query parameter is required',
                'productos' => []
            ]);
        }

        // Normalizar query: quitar acentos, mayúsculas
        $queryNormalized = $this->normalizeText($query);
        
        // Extraer palabras clave principales (ignorar "de", "con", "para", etc.)
        $palabrasIgnorar = ['DE', 'CON', 'PARA', 'EN', 'EL', 'LA', 'LOS', 'LAS', 'UN', 'UNA'];
        $palabras = array_filter(explode(' ', $queryNormalized), function($p) use ($palabrasIgnorar) {
            return strlen($p) > 2 && !in_array($p, $palabrasIgnorar);
        });
        $palabrasClave = array_values($palabras);
        
        \Log::info('🔍 Búsqueda de producto', [
            'query_original' => $query,
            'query_normalized' => $queryNormalized,
            'palabras_clave' => $palabrasClave
        ]);
        
        // 1. Buscar coincidencia EXACTA (insensible a mayúsculas/acentos)
        $exactMatch = Product::whereRaw('UPPER(producto_nombre) = ?', [$queryNormalized])
            ->first([
                'producto_codigo',
                'producto_nombre',
            ]);

        if ($exactMatch) {
            \Log::info('✅ Producto encontrado - COINCIDENCIA EXACTA', [
                'query' => $query,
                'producto' => $exactMatch->producto_nombre,
                'codigo' => $exactMatch->producto_codigo
            ]);
            
            return response()->json([
                'success' => true,
                'match_type' => 'exact',
                'productos' => [[
                    'codigo' => $exactMatch->producto_codigo,
                    'nombre' => $exactMatch->producto_nombre,
                ]]
            ]);
        }

        // 2. Buscar coincidencia PARCIAL por palabras clave
        $queryBuilder = Product::query();
        
        // Buscar por cada palabra clave (todas deben estar presentes)
        foreach ($palabrasClave as $palabra) {
            $queryBuilder->whereRaw('UPPER(producto_nombre) LIKE ?', ['%' . $palabra . '%']);
        }
        
        $partialMatch = $queryBuilder
            ->orderByRaw("CASE 
                WHEN UPPER(producto_nombre) = ? THEN 1
                WHEN UPPER(producto_nombre) LIKE ? THEN 2
                ELSE 3
            END", [$queryNormalized, $queryNormalized . '%'])
            ->limit(5)
            ->get([
                'producto_codigo',
                'producto_nombre',
            ]);

        if ($partialMatch->isNotEmpty()) {
            \Log::info('✅ Producto encontrado - Coincidencia parcial', [
                'query' => $query,
                'palabras_usadas' => $palabrasClave,
                'sugerencias_count' => $partialMatch->count(),
                'primera_sugerencia' => $partialMatch->first()->producto_nombre
            ]);
            
            $formattedProducts = $partialMatch->map(function ($product) {
                return [
                    'codigo' => $product->producto_codigo,
                    'nombre' => $product->producto_nombre,
                ];
            });

            return response()->json([
                'success' => true,
                'match_type' => 'partial',
                'message' => "No se encontró '{$query}' exacto. Se muestran {$partialMatch->count()} sugerencias similares.",
                'productos' => $formattedProducts,
            ]);
        }

        // 3. No se encontró nada
        \Log::warning('❌ Producto NO encontrado - Sin sugerencias', [
            'query' => $query
        ]);

        return response()->json([
            'success' => false,
            'match_type' => 'none',
            'message' => "No se encontró ningún producto similar a '{$query}' en el catálogo.",
            'productos' => []
        ]);
    }
}
