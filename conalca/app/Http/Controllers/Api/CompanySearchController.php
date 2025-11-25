<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanySearchController extends Controller
{
    /**
     * Buscar empresas en tiempo real
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->get('q');
            
            // Validar que hay un término de búsqueda
            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                return response()->json([
                    'success' => true,
                    'companies' => [],
                    'message' => 'Ingresa al menos 2 caracteres para buscar'
                ]);
            }

            Log::info('Búsqueda de empresa', ['term' => $searchTerm]);

            // Buscar en todas las tablas relevantes de empresas
            $companies = collect();

            // Buscar en CotizacionModel (empresas que ya han solicitado cotizaciones)
            $cotizacionCompanies = DB::table('cotizacion_models')
                ->select([
                    'company_name as name',
                    'nit',
                    'direccion as address',
                    'telefono as phone',
                    'email',
                    'ciudad_origen as city',
                    DB::raw("'cotizacion' as source"),
                    DB::raw('COUNT(*) as quote_count')
                ])
                ->where('company_name', 'LIKE', "%{$searchTerm}%")
                ->whereNotNull('company_name')
                ->where('company_name', '!=', '')
                ->groupBy('company_name', 'nit', 'direccion', 'telefono', 'email', 'ciudad_origen')
                ->orderBy('quote_count', 'desc')
                ->limit(10)
                ->get();

            $companies = $companies->merge($cotizacionCompanies);

            // Buscar en la tabla clients (si existe)
            if (DB::getSchemaBuilder()->hasTable('clients')) {
                $clientCompanies = DB::table('clients')
                    ->select([
                        'name',
                        'nit',
                        'address',
                        'phone',
                        'email',
                        'city',
                        DB::raw("'client' as source"),
                        DB::raw('1 as quote_count')
                    ])
                    ->where('name', 'LIKE', "%{$searchTerm}%")
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->limit(5)
                    ->get();

                $companies = $companies->merge($clientCompanies);
            }

            // Filtrar duplicados y formatear resultados
            $uniqueCompanies = $companies
                ->groupBy('name')
                ->map(function ($group) {
                    $company = $group->first();
                    return [
                        'id' => $company->nit ?: uniqid(),
                        'name' => $company->name,
                        'nit' => $company->nit,
                        'address' => $company->address,
                        'phone' => $company->phone,
                        'email' => $company->email,
                        'city' => $company->city,
                        'source' => $company->source,
                        'quote_count' => $company->quote_count ?? 0
                    ];
                })
                ->sortByDesc('quote_count')
                ->take(8)
                ->values();

            return response()->json([
                'success' => true,
                'companies' => $uniqueCompanies,
                'total' => $uniqueCompanies->count(),
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de empresas', [
                'error' => $e->getMessage(),
                'search_term' => $searchTerm ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'companies' => []
            ], 500);
        }
    }
}