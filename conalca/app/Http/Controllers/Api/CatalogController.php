<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\City;
use App\Models\Seller;
use App\Models\User;
use App\Models\Product;
use App\Models\Packing;
use App\Models\VehicleClass;
use App\Models\Bodywork;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogController extends Controller
{

    protected function normalize($text) {
        $t = strtoupper(trim($text));
        $t = iconv('UTF-8', 'ASCII//TRANSLIT', $t);
        $t = preg_replace('/[^A-Z0-9\s]/', '', $t);
        $t = preg_replace('/\s+/', ' ', $t);
        return $t;
    }

    protected function similarity($a, $b) {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        similar_text($a, $b, $percent);
        $lev = levenshtein($a, $b);
        $len = max(strlen($a), strlen($b));
        $levScore = $len ? (1 - min($lev, $len) / $len) * 100 : 0;
        return ($percent * 0.6) + ($levScore * 0.4);
    }

    protected function topSimilar($query, $items, $field, $limit = 10) {
        $qNorm = $this->normalize($query);
        $scored = collect($items)->map(function($item) use ($field, $qNorm) {
            $score = $this->similarity($qNorm, data_get($item, $field, ''));
            $item->score = $score;
            return $item;
        })->sortByDesc('score')->values()->take($limit);
        return $scored;
    }

    public function clientes(Request $r) {
        $q = trim($r->input('q', ''));
        $limit = 15;
        if ($q === '') {
            return response()->json([]);
        }

        $cacheKey = 'catalog:clientes:' . $q;
        
        try {
            $results = Cache::remember($cacheKey, 60, function () use ($q, $limit) {
                return $this->searchClientsLogic($q, $limit);
            });
        } catch (\Exception $e) {
            Log::error("Error accediendo a caché de clientes: " . $e->getMessage());
            $results = $this->searchClientsLogic($q, $limit);
        }

        return response()->json($results);
    }

    private function searchClientsLogic($q, $limit) {
        $isNumeric = preg_match('/^\d+$/', $q) === 1;
        $col = ['id', 'codigo', 'documento', 'cliente'];
        $res = collect();

        if ($isNumeric) {
            $exact = Client::where('codigo', $q)->first($col);
            $others = Client::where(function($query) use ($q) {
                            $query->where('cliente', 'like', "%$q%")
                                  ->orWhere('codigo', 'like', "%$q%")
                                  ->orWhere('documento', 'like', "%$q%");
                        })
                        ->when($exact, fn($query) => $query->where('codigo', '!=', $q))
                        ->limit($limit - ($exact ? 1 : 0))
                        ->get($col);
            if ($exact) $res->push($exact);
            foreach ($others as $c) {
                if (!$res->firstWhere('codigo', $c->codigo)) {
                    $res->push($c);
                }
            }
        } else {
            // Exacto por nombre/documento/código
            $exact = Client::where('cliente', $q)
                        ->orWhere('codigo', $q)
                        ->orWhere('documento', $q)
                        ->get($col);

            // Empieza por
            $startsWith = Client::where('cliente', 'like', "$q%")
                                ->limit(20)
                                ->get($col);

            // Contiene la frase (prioridad principal solicitada)
            $contains = Client::where('cliente', 'like', "%$q%")
                              ->limit(50)
                              ->get($col);

            // Tokens individuales del término (ej. "conalca sas" → ["conalca","sas"])
            $tokens = collect(preg_split('/\s+/', trim($q)))
                        ->filter(fn($t) => strlen($t) >= 3)
                        ->unique()
                        ->values();

            $byTokens = collect();
            foreach ($tokens as $t) {
                $chunk = Client::where('cliente', 'like', "%$t%")
                               ->limit(30)
                               ->get($col);
                $byTokens = $byTokens->concat($chunk);
            }

            $res = collect()
                ->concat($exact)
                ->concat($startsWith)
                ->concat($contains)
                ->concat($byTokens);

            // Si aún no hay resultados, ofrecer similares SOLO basados en la frase consultada (no aleatorios)
            if ($res->isEmpty()) {
                $subset = Client::where('cliente', 'like', "%$q%")
                                ->orWhere('cliente', 'like', substr($q, 0, 3) . "%")
                                ->limit(120)
                                ->get($col);
                $res = $this->topSimilar($q, $subset, 'cliente', $limit);
            }
        }

        if ($res->isEmpty()) {
            Log::info('[CLIENTES] Búsqueda sin resultados', ['q' => $q]);
        }
        return $res->unique('codigo')->values()->take($limit);
    }

    public function ciudades(Request $r) {
        $q = trim($r->input('q', ''));
        $limit = 15;
        if ($q === '') return [];

        $cacheKey = 'catalog:ciudades:' . $q;
        
        try {
            return Cache::remember($cacheKey, 60, function () use ($q, $limit) {
                return $this->searchCitiesLogic($q, $limit);
            });
        } catch (\Exception $e) {
            Log::error("Error accediendo a caché de ciudades: " . $e->getMessage());
            return $this->searchCitiesLogic($q, $limit);
        }
    }

    private function searchCitiesLogic($q, $limit) {
        $isNumeric = preg_match('/^\d+$/', $q) === 1;
        $select = ['ciudad_codigodane', 'municipio_nombre', 'departamento_nombre', 'ciudad_nombre'];

        if ($isNumeric) {
            // Si es numérico, prioridad total al código DANE exacto
            $exact = City::where('estado_nombre', 'ACTIVO')
                        ->where('ciudad_codigodane', $q)
                        ->get($select);
            
            $startsWith = City::where('estado_nombre', 'ACTIVO')
                            ->where('ciudad_codigodane', 'like', "$q%")
                            ->where('ciudad_codigodane', '!=', $q)
                            ->limit($limit)
                            ->get($select);
            
            return $exact->concat($startsWith)->take($limit);
        }

        // Búsqueda por texto: exacto, empieza por, contiene, tokens
        $exact = City::where('estado_nombre', 'ACTIVO')
                    ->where(function($query) use ($q) {
                            $query->where('municipio_nombre', $q)
                                ->orWhere('ciudad_nombre', $q);
                    })->get($select);

        // Si hay exactos, devolverlos inmediatamente sin buscar ruido adicional
        if ($exact->isNotEmpty()) {
            return $exact->unique('ciudad_codigodane')->values();
        }

        $startsWith = City::where('estado_nombre', 'ACTIVO')
                        ->where(function($query) use ($q) {
                            $query->where('municipio_nombre', 'like', "$q%")
                                    ->orWhere('ciudad_nombre', 'like', "$q%");
                        })->limit(10)->get($select);

        $contains = City::where('estado_nombre', 'ACTIVO')
                        ->where(function($query) use ($q) {
                            $query->where('municipio_nombre', 'like', "%$q%")
                                    ->orWhere('ciudad_nombre', 'like', "%$q%");
                        })->limit(30)->get($select);

        // Tokens individuales para búsquedas compuestas (ej "medellin antioquia")
        $tokens = collect(preg_split('/\s+/', $q))
                    ->filter(fn($t) => strlen($t) >= 3)
                    ->unique()
                    ->values();

        $byTokens = collect();
        if ($tokens->count() > 1) {
            foreach ($tokens as $t) {
                    $chunk = City::where('estado_nombre', 'ACTIVO')
                                ->where(function($query) use ($t) {
                                    $query->where('municipio_nombre', 'like', "%$t%")
                                        ->orWhere('departamento_nombre', 'like', "%$t%");
                                })->limit(10)->get($select);
                    $byTokens = $byTokens->concat($chunk);
            }
        }

        $res = $exact->concat($startsWith)
                        ->concat($contains)
                        ->concat($byTokens)
                        ->unique('ciudad_codigodane');

        // Fallback de similitud si está muy vacío
        if ($res->isEmpty()) {
            $subset = City::where('estado_nombre', 'ACTIVO')
                            ->limit(100) // Traer un subset razonable para comparar
                            ->get($select);
            $res = $this->topSimilar($q, $subset, 'municipio_nombre', $limit);
        }

        if ($res->isEmpty()) {
            Log::info('[CIUDADES] Búsqueda sin resultados', ['q' => $q]);
        }

        return $res->values()->take($limit);
    }

    public function vendedores(Request $r) {
        $q = $r->input('q', '');

        // Buscar usuarios con rol 'ASISTENTE COMERCIAL' que tengan documento
        $usuarios_comerciales = User::role('ASISTENTE COMERCIAL')
                    ->whereNotNull('documento')
                    ->where('documento', '!=', '')
                    ->where(function($query) use ($q) {
                        $query->where('name', 'like', "%$q%")
                              ->orWhere('documento', 'like', "%$q%")
                              ->orWhere('email', 'like', "%$q%");
                    })
                    ->limit(15)
                    ->get(['id', 'documento', 'name', 'email'])
                    ->map(function($user) {
                        return [
                            'Codigo' => $user->id,
                            'Documento' => $user->documento, 
                            'Nombre' => $user->name
                        ];
                    });

        $finalResults = $usuarios_comerciales;

        // Si no hay comerciales o la búsqueda está vacía, también incluir la tabla sellers como fallback
        if ($usuarios_comerciales->isEmpty() || empty($q)) {
            $sellers_fallback = Seller::where('Nombre', 'like', "%$q%")
                        ->orWhere('Codigo', 'like', "%$q%")
                        ->orWhere('Documento', 'like', "%$q%")
                        ->limit(5)
                        ->get(['Codigo', 'Documento', 'Nombre']);
            
            // Combinar resultados (comerciales primero)
            $finalResults = $usuarios_comerciales->concat($sellers_fallback)->take(15);
        }

        if ($finalResults->isEmpty() && !empty($q)) {
            Log::info('[VENDEDORES] Búsqueda sin resultados: q="' . $q . '"');
        }

        return $finalResults;
    }

    public function productos(Request $r) {
        $q = $r->input('q', '');

        return Product::where('producto_nombre', 'like', "%$q%")
                    ->orWhere('producto_codigo', 'like', "%$q%")
                    ->orWhere('producto_codigo_ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get([
                        'producto_codigo',
                        'producto_codigo_ministerio',
                        'producto_nombre',
                        'tippro_nombre',
                        'producto_fechacreacion',
                        'natcar_nombre',
                        'usuario_nombre'
                    ]);
    }

    public function empaques(Request $r) {
        $q = $r->input('q', '');

        $result = Packing::where('Nombre', 'like', "%$q%")
                    ->orWhere('Codigo', 'like', "%$q%")
                    ->orWhere('Codigo Ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get(['Codigo', 'Codigo Ministerio', 'Nombre']);

        \Log::info('[EMPAQUES] Búsqueda con q="'.$q.'", resultados: ' . $result->count());
        
        return $result;
    }

    public function clasesVehiculo(Request $r) {
        $q = $r->input('q', '');

        return VehicleClass::where('Nombre', 'like', "%$q%")
                        ->orWhere('Codigo', 'like', "%$q%")
                        ->limit(15)
                        ->get(['Codigo', 'Nombre']);
    }

    public function carrocerias(Request $r) {
        $q = $r->input('q', '');

        return Bodywork::where('Nombre', 'like', "%$q%")
                    ->orWhere('Codigo', 'like', "%$q%")
                    ->orWhere('Codigo Ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get(['Codigo', 'Codigo Ministerio', 'Nombre']);
    }

}
