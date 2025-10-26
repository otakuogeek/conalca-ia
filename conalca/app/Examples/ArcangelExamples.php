<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * EJEMPLOS DE USO - ARCANGEL SERVICE
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Este archivo contiene ejemplos de cómo usar el ArcangelService en diferentes
 * partes de tu aplicación Laravel.
 */

namespace App\Examples;

use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;

class ArcangelExamples
{
    protected ArcangelService $arcangel;

    public function __construct(ArcangelService $arcangel)
    {
        $this->arcangel = $arcangel;
    }

    /**
     * EJEMPLO 1: Consultar datos con GET
     * 
     * Uso en un controlador:
     * $arcangel = app(ArcangelService::class);
     * $clientes = $arcangel->get('clientes');
     */
    public function ejemplo1_consultarClientes()
    {
        try {
            // Consulta simple
            $clientes = $this->arcangel->get('clientes');
            
            // Consulta con parámetros
            $clientesFiltrados = $this->arcangel->get('clientes', [
                'ciudad' => 'Bogotá',
                'estado' => 'activo',
            ]);
            
            // Consulta con caché (se cachea por 60 minutos)
            $clientesConCache = $this->arcangel->get('clientes', [], true, 60);
            
            return [
                'clientes' => $clientes,
                'filtrados' => $clientesFiltrados,
                'con_cache' => $clientesConCache,
            ];
        } catch (\Exception $e) {
            Log::error('Error consultando clientes: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EJEMPLO 2: Crear/Enviar datos con POST
     */
    public function ejemplo2_crearCliente()
    {
        try {
            $nuevoCliente = $this->arcangel->post('clientes', [
                'nombre' => 'Cliente Ejemplo',
                'documento' => '123456789',
                'telefono' => '3001234567',
                'ciudad' => 'Bogotá',
                'email' => 'cliente@ejemplo.com',
            ]);
            
            return $nuevoCliente;
        } catch (\Exception $e) {
            Log::error('Error creando cliente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EJEMPLO 3: Actualizar datos con PUT
     */
    public function ejemplo3_actualizarCliente($clienteId)
    {
        try {
            $clienteActualizado = $this->arcangel->put("clientes/{$clienteId}", [
                'telefono' => '3009876543',
                'email' => 'nuevo@email.com',
            ]);
            
            return $clienteActualizado;
        } catch (\Exception $e) {
            Log::error('Error actualizando cliente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EJEMPLO 4: Eliminar datos con DELETE
     */
    public function ejemplo4_eliminarCliente($clienteId)
    {
        try {
            $resultado = $this->arcangel->delete("clientes/{$clienteId}");
            
            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error eliminando cliente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EJEMPLO 5: Usar en un controlador
     */
    public function ejemplo5_enControlador()
    {
        // En tu controlador:
        /*
        use App\Services\ArcangelService;

        class ClienteController extends Controller
        {
            protected ArcangelService $arcangel;

            public function __construct(ArcangelService $arcangel)
            {
                $this->arcangel = $arcangel;
            }

            public function index()
            {
                try {
                    $clientes = $this->arcangel->get('clientes', [], true, 30);
                    
                    return view('clientes.index', compact('clientes'));
                } catch (\Exception $e) {
                    return back()->with('error', 'Error al cargar clientes: ' . $e->getMessage());
                }
            }

            public function store(Request $request)
            {
                try {
                    $validated = $request->validate([
                        'nombre' => 'required|string|max:255',
                        'documento' => 'required|string',
                        // ... más validaciones
                    ]);

                    $cliente = $this->arcangel->post('clientes', $validated);
                    
                    return redirect()->route('clientes.index')
                        ->with('success', 'Cliente creado exitosamente');
                } catch (\Exception $e) {
                    return back()->with('error', 'Error al crear cliente: ' . $e->getMessage());
                }
            }
        }
        */
    }

    /**
     * EJEMPLO 6: Verificar salud de la API
     */
    public function ejemplo6_healthCheck()
    {
        try {
            $isHealthy = $this->arcangel->healthCheck();
            $info = $this->arcangel->getInfo();
            
            if ($isHealthy) {
                Log::info('API Arcangel disponible', $info);
            } else {
                Log::error('API Arcangel no disponible', $info);
            }
            
            return [
                'healthy' => $isHealthy,
                'info' => $info,
            ];
        } catch (\Exception $e) {
            Log::error('Error verificando salud de API: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EJEMPLO 7: Limpiar caché
     */
    public function ejemplo7_limpiarCache()
    {
        // Limpiar caché de un endpoint específico
        $this->arcangel->clearCache('clientes', ['ciudad' => 'Bogotá']);
        
        // Limpiar toda la caché
        $this->arcangel->clearAllCache();
    }

    /**
     * EJEMPLO 8: Consultar con manejo de errores detallado
     */
    public function ejemplo8_consultaConErrorHandling()
    {
        try {
            $productos = $this->arcangel->get('productos');
            
            if (empty($productos)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron productos',
                ];
            }
            
            return [
                'success' => true,
                'data' => $productos,
            ];
        } catch (\Exception $e) {
            // Manejo específico según el tipo de error
            if (str_contains($e->getMessage(), 'No autorizado')) {
                return [
                    'success' => false,
                    'message' => 'Error de autenticación. Verifica las credenciales de API.',
                ];
            }
            
            if (str_contains($e->getMessage(), 'no encontrado')) {
                return [
                    'success' => false,
                    'message' => 'El endpoint solicitado no existe.',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al consultar productos: ' . $e->getMessage(),
            ];
        }
    }
}
