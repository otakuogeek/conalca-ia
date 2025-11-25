<?php

namespace App\Http\Controllers;

use App\Models\VehicleOwnerHolderDriver;
use App\Models\VehicleClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConductorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Solo permitir acceso a SUPER ADMIN y SAC
            if (!$user || (!$user->hasRole('SUPER ADMIN') && !$user->hasRole('SAC'))) {
                abort(403, 'No tienes permisos para acceder a este módulo.');
            }
            
            return $next($request);
        });
    }

    /**
     * Mostrar la página principal de conductores
     */
    public function index()
    {
        // Cargar las clases de vehículos para el formulario
        $vehicleClasses = VehicleClass::all(['Codigo', 'Nombre']);
        
        return view('conductores.index', compact('vehicleClasses'));
    }

    /**
     * Obtener clases de vehículos para AJAX
     */
    public function getVehicleClasses()
    {
        try {
            $vehicleClasses = VehicleClass::all(['Codigo', 'Nombre']);
            return response()->json($vehicleClasses);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo clases de vehículos: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Obtener todos los conductores (API)
     */
    public function getConductores(Request $request)
    {
        try {
            \Log::info('Iniciando getConductores API');
            
            $query = VehicleOwnerHolderDriver::query();
            
            // Búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('Conductor', 'LIKE', "%{$search}%")
                      ->orWhere('Cedula', 'LIKE', "%{$search}%")
                      ->orWhere('Placa', 'LIKE', "%{$search}%")
                      ->orWhere('Telefonoconductor', 'LIKE', "%{$search}%");
                });
            }

            // Filtros adicionales
            if ($request->has('estado') && !empty($request->estado)) {
                $query->where('Estado', $request->estado);
            }

            if ($request->has('ciudad') && !empty($request->ciudad)) {
                $query->where('Ciudad conductor', 'LIKE', "%{$request->ciudad}%");
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Contar total antes de paginar
            $total = $query->count();
            \Log::info("Total de conductores encontrados: {$total}");

            // Paginación
            $perPage = $request->get('per_page', 15);
            $conductores = $query->paginate($perPage);
            
            \Log::info('Conductores paginados:', $conductores->toArray());

            return response()->json($conductores);
            
        } catch (\Exception $e) {
            \Log::error('Error en getConductores: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage(),
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1
            ], 500);
        }
    }

    /**
     * Obtener un conductor específico
     */
    public function show($id)
    {
        $conductor = VehicleOwnerHolderDriver::findOrFail($id);
        return response()->json($conductor);
    }

    /**
     * Crear un nuevo conductor - SOLO CREAR, NUNCA ACTUALIZAR
     */
    public function store(Request $request)
    {
        Log::info('=== INICIANDO STORE CONDUCTOR ===');
        Log::info('Datos recibidos para crear conductor: ' . json_encode($request->all()));

        // PROTECCIÓN MULTICAPA CONTRA DUPLICACIÓN
        
        // 1. Crear un identificador único para esta solicitud específica
        $requestId = md5(json_encode($request->all()) . microtime(true));
        $cacheKey = 'creating_conductor_' . md5(json_encode($request->only(['Cedula', 'Placa'])));
        $lockKey = 'lock_conductor_' . md5($request->input('Cedula', 'no_cedula'));
        
        Log::info("Request ID: {$requestId}");
        Log::info("Cache Key: {$cacheKey}");
        Log::info("Lock Key: {$lockKey}");
        
        // 2. Verificar cache de solicitudes recientes
        if (Cache::has($cacheKey)) {
            Log::warning('Solicitud duplicada detectada en cache, ignorando: ' . $cacheKey);
            return response()->json([
                'error' => 'Solicitud duplicada detectada'
            ], 429);
        }

        // 3. Implementar lock atómico para prevenir race conditions
        $lockAcquired = Cache::add($lockKey, $requestId, 10); // Lock por 10 segundos
        
        if (!$lockAcquired) {
            Log::warning('No se pudo adquirir lock, solicitud concurrente detectada: ' . $lockKey);
            return response()->json([
                'error' => 'Solicitud concurrente detectada'
            ], 429);
        }

        // 4. Marcar que estamos procesando esta solicitud
        Cache::put($cacheKey, $requestId, 30);

        try {
            // 5. TRANSACCIÓN ATÓMICA para toda la operación
            return DB::transaction(function () use ($request, $cacheKey, $lockKey, $requestId) {
                
                Log::info('Iniciando transacción atómica para crear conductor');
                
                // 6. Verificación final en BD antes de crear
                if (!empty($request->input('Cedula'))) {
                    $existingConductor = VehicleOwnerHolderDriver::where('Cedula', $request->input('Cedula'))->first();
                    if ($existingConductor) {
                        Log::warning('Conductor con cédula ya existe en BD: ' . $request->input('Cedula'));
                        
                        // Crear ValidationException correctamente
                        $validator = Validator::make($request->all(), []);
                        $validator->errors()->add('Cedula', 'The cedula has already been taken.');
                        throw new ValidationException($validator);
                    }
                }

                // 6.5. Validación específica para campo Modelo (debe ser un año válido)
                if (!empty($request->input('Modelo'))) {
                    $modelo = $request->input('Modelo');
                    if (!is_numeric($modelo) || $modelo < 1900 || $modelo > 2030) {
                        Log::warning('Modelo inválido recibido: ' . $modelo);
                        
                        $validator = Validator::make($request->all(), []);
                        $validator->errors()->add('Modelo', 'El modelo debe ser un año válido entre 1900 y 2030.');
                        throw new ValidationException($validator);
                    }
                    
                    // Convertir a entero para asegurar tipo correcto
                    $request->merge(['Modelo' => (int) $modelo]);
                    Log::info('Modelo validado y convertido a entero: ' . $request->input('Modelo'));
                }

                // 6.6. Validación específica para campo Cedula (límite de INT en MySQL)
                if (!empty($request->input('Cedula'))) {
                    $cedula = $request->input('Cedula');
                    if (!is_numeric($cedula) || $cedula < 1 || $cedula > 2147483647) {
                        Log::warning('Cédula inválida recibida (fuera de rango): ' . $cedula);
                        
                        $validator = Validator::make($request->all(), []);
                        $validator->errors()->add('Cedula', 'La cédula debe ser un número válido menor a 2,147,483,647.');
                        throw new ValidationException($validator);
                    }
                    
                    // Convertir a entero para asegurar tipo correcto
                    $request->merge(['Cedula' => (int) $cedula]);
                    Log::info('Cédula validada y convertida a entero: ' . $request->input('Cedula'));
                }

                // 6.7. Validación específica para Documentopropietario (mismo límite)
                if (!empty($request->input('Documentopropietario'))) {
                    $documento = $request->input('Documentopropietario');
                    if (!is_numeric($documento) || $documento < 1 || $documento > 2147483647) {
                        Log::warning('Documento propietario inválido recibido (fuera de rango): ' . $documento);
                        
                        $validator = Validator::make($request->all(), []);
                        $validator->errors()->add('Documentopropietario', 'El documento del propietario debe ser un número válido menor a 2,147,483,647.');
                        throw new ValidationException($validator);
                    }
                    
                    // Convertir a entero para asegurar tipo correcto
                    $request->merge(['Documentopropietario' => (int) $documento]);
                    Log::info('Documento propietario validado y convertido: ' . $request->input('Documentopropietario'));
                }

                // 7. Filtrar y preparar datos
                $allowedFields = [
                    'Conductor', 'Tipodocumentoconducotor', 'Cedula', 'Telefonoconductor',
                    'Direccion conductor', 'Ciudad conductor', 'Placa', 'Propietario',
                    'Tipodocumentopropietario', 'Documentopropietario', 'Telefonopropietario',
                    'Direccion propietario', 'Ciudad propietario', 'Marca', 'Modelo',
                    'Clasevehiculo', 'Estado'
                ];

                $data = collect($request->only($allowedFields))
                    ->filter(function ($value, $key) {
                        return !is_null($value) && $value !== '';
                    })->toArray();

                Log::info('Datos filtrados para crear conductor: ' . json_encode($data));

                if (empty($data)) {
                    throw new \Exception('No hay datos válidos para crear el conductor');
                }

                // 8. Crear el conductor dentro de la transacción
                $conductor = VehicleOwnerHolderDriver::create($data);

                if (!$conductor) {
                    throw new \Exception('Error al crear el conductor en la base de datos');
                }

                Log::info('✅ Conductor creado exitosamente: ' . json_encode($conductor->toArray()));

                return response()->json([
                    'success' => true,
                    'message' => 'Conductor creado exitosamente',
                    'data' => $conductor
                ], 201);
            });

        } catch (ValidationException $e) {
            Log::error('Errores de validación: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al crear conductor: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
            
        } finally {
            // 9. LIMPIEZA GARANTIZADA - Siempre liberar cache y locks
            try {
                Cache::forget($cacheKey);
                Cache::forget($lockKey);
                Log::info('Cache y locks liberados correctamente');
            } catch (\Exception $cleanupError) {
                Log::error('Error liberando cache: ' . $cleanupError->getMessage());
            }
        }
    }

    /**
     * Actualizar un conductor existente - SOLO ACTUALIZAR, NUNCA CREAR
     */
    public function update(Request $request, $id)
    {
        \Log::info('=== INICIANDO UPDATE CONDUCTOR ===');
        \Log::info("ID del conductor a actualizar: {$id}");
        \Log::info('Datos recibidos para actualizar conductor:', $request->all());
        
        // VERIFICACIÓN CRÍTICA: El conductor DEBE existir
        $conductor = VehicleOwnerHolderDriver::find($id);
        if (!$conductor) {
            \Log::error("❌ Conductor con ID {$id} no encontrado - NO SE PUEDE ACTUALIZAR");
            return response()->json([
                'success' => false,
                'message' => 'Conductor no encontrado'
            ], 404);
        }
        
        \Log::info('✅ Conductor encontrado para actualizar:', $conductor->toArray());
        
        // Validación básica de datos
        $validationRules = [
            'Conductor' => 'nullable|string|max:255',
            'Cedula' => 'nullable|integer|min:1|max:2147483647',
            'Telefonoconductor' => 'nullable|string|max:50',
            'Direccion conductor' => 'nullable|string|max:500',
            'Ciudad conductor' => 'nullable|string|max:255',
            'Placa' => 'nullable|string|max:10',
            'Propietario' => 'nullable|string|max:255',
            'Documentopropietario' => 'nullable|integer|min:1|max:2147483647',
            'Marca' => 'nullable|string|max:100',
            'Modelo' => 'nullable|integer|min:1900|max:2030',
            'Clasevehiculo' => 'nullable|string|max:100',
            'Estado' => 'nullable|in:ACTIVO,INACTIVO,SUSPENDIDO',
        ];

        // Validar cédula única solo si se envía
        if ($request->has('Cedula') && !empty($request->Cedula)) {
            if (VehicleOwnerHolderDriver::cedulaExiste($request->Cedula, $id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otro conductor con esta cédula'
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            \Log::error('Errores de validación en update:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Errores de validación'
            ], 422);
        }

        try {
            // OBTENER SOLO LOS CAMPOS MODIFICABLES
            $fillableFields = $conductor->getFillable();
            $datosParaActualizar = [];
            
            foreach ($request->all() as $campo => $valor) {
                // CRÍTICO: Excluir 'id' y solo incluir campos fillable con valores válidos
                if ($campo !== 'id' && in_array($campo, $fillableFields) && $valor !== null && $valor !== '') {
                    $datosParaActualizar[$campo] = $valor;
                }
            }
            
            \Log::info('Datos filtrados para actualización:', $datosParaActualizar);
            
            if (empty($datosParaActualizar)) {
                \Log::warning('No hay datos válidos para actualizar');
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos válidos para actualizar'
                ], 400);
            }
            
            // USAR TRANSACCIÓN PARA SEGURIDAD
            DB::beginTransaction();
            
            // ACTUALIZAR SOLO EL REGISTRO EXISTENTE
            $updated = $conductor->update($datosParaActualizar);
            
            if (!$updated) {
                DB::rollBack();
                \Log::error('❌ No se pudo actualizar el conductor');
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el conductor'
                ], 500);
            }
            
            // Recargar datos actualizados
            $conductor->refresh();
            
            DB::commit();
            
            \Log::info('✅ Conductor actualizado exitosamente:', $conductor->toArray());
            
            return response()->json([
                'success' => true,
                'message' => 'Conductor actualizado exitosamente',
                'conductor' => $conductor
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Error actualizando conductor: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el conductor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un conductor
     */
    public function destroy($id)
    {
        try {
            $conductor = VehicleOwnerHolderDriver::find($id);
            
            if (!$conductor) {
                return response()->json([
                    'success' => true,
                    'message' => 'El conductor ya fue eliminado o no existe'
                ], 200); // Retornar 200 porque técnicamente el objetivo se cumplió
            }
            
            $conductor->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Conductor eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando conductor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Cambiar estado de un conductor
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:ACTIVO,INACTIVO,SUSPENDIDO'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conductor = VehicleOwnerHolderDriver::findOrFail($id);
            $conductor->update(['Estado' => $request->estado]);
            
            return response()->json([
                'success' => true,
                'message' => 'Estado del conductor actualizado exitosamente',
                'conductor' => $conductor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de conductores
     */
    public function getStats()
    {
        try {
            $stats = [
                'total' => VehicleOwnerHolderDriver::count(),
                'activos' => VehicleOwnerHolderDriver::where('Estado', 'ACTIVO')->count(),
                'inactivos' => VehicleOwnerHolderDriver::where('Estado', 'INACTIVO')->count(),
                'suspendidos' => VehicleOwnerHolderDriver::where('Estado', 'SUSPENDIDO')->count(),
            ];

            \Log::info('Estadísticas calculadas:', $stats);
            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Error calculando estadísticas: ' . $e->getMessage());
            return response()->json([
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'suspendidos' => 0,
            ]);
        }
    }
}
