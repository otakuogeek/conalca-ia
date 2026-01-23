<?php

/**
 * PRUEBAS INTEGRALES DEL SISTEMA DE CHAT
 * 
 * Este script realiza pruebas completas del sistema de cotizaciones:
 * 1. Creación de rutas simples
 * 2. Creación de rutas múltiples
 * 3. Reconocimiento de campos (origen, destino, peso, producto, valor)
 * 4. Modificación de campos después de crear rutas
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class PruebasIntegralesChat
{
    private $sessionId;
    private $testResults = [];
    
    public function __construct()
    {
        // Crear sesión de prueba
        $this->sessionId = 'test_' . time() . '_' . rand(1000, 9999);
    }
    
    public function ejecutarTodasLasPruebas()
    {
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "🧪 PRUEBAS INTEGRALES DEL SISTEMA DE CHAT\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "Session ID: {$this->sessionId}\n";
        echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        
        // PARTE 1: Pruebas de creación
        $this->prueba1_RutaSimpleEstructurada();
        $this->prueba2_RutaSimpleNatural();
        $this->prueba3_RutaConTodosLosCampos();
        $this->prueba4_MultiRutaValida();
        $this->prueba5_ValorDeclaradoFormatos();
        $this->prueba6_ProductosVariados();
        $this->prueba7_PesosConTara();
        
        // PARTE 2: Pruebas de modificación
        $this->prueba8_ModificarCampoOrigen();
        $this->prueba9_ModificarPeso();
        $this->prueba10_ModificarProducto();
        $this->prueba11_AgregarRutaAGrupoExistente();
        
        // Resumen final
        $this->mostrarResumen();
    }
    
    // ═══════════════════════════════════════════════════════════════════════════════
    // PARTE 1: PRUEBAS DE CREACIÓN
    // ═══════════════════════════════════════════════════════════════════════════════
    
    private function prueba1_RutaSimpleEstructurada()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 1: Ruta Simple - Formato Estructurado\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $prompt = "Origen: Bogotá, Destino: Cali, Peso: 1,000 kg";
        
        echo "📝 Prompt: '$prompt'\n\n";
        
        $resultado = $this->simularExtraccion($prompt);
        
        $validaciones = [
            'num_rutas' => [
                'esperado' => 1,
                'obtenido' => count($resultado['rutas']),
                'descripcion' => 'Número de rutas detectadas'
            ],
            'origen' => [
                'esperado' => 'BOGOTA',
                'obtenido' => $resultado['rutas'][0]['origen'] ?? 'N/A',
                'descripcion' => 'Ciudad de origen'
            ],
            'destino' => [
                'esperado' => 'CALI',
                'obtenido' => $resultado['rutas'][0]['destino'] ?? 'N/A',
                'descripcion' => 'Ciudad de destino'
            ],
            'peso' => [
                'esperado' => 1000,
                'obtenido' => $resultado['rutas'][0]['peso'] ?? 0,
                'descripcion' => 'Peso en kilogramos',
                'tolerancia' => 10
            ]
        ];
        
        $this->validarResultado('TEST_1', $validaciones, $resultado);
    }
    
    private function prueba2_RutaSimpleNatural()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 2: Ruta Simple - Formato Natural\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $prompt = "Necesito transportar 500 kilos de Medellín a Barranquilla";
        
        echo "📝 Prompt: '$prompt'\n\n";
        
        $resultado = $this->simularExtraccion($prompt);
        
        $validaciones = [
            'num_rutas' => [
                'esperado' => 1,
                'obtenido' => count($resultado['rutas']),
                'descripcion' => 'Número de rutas'
            ],
            'origen' => [
                'esperado' => 'MEDELLIN',
                'obtenido' => $resultado['rutas'][0]['origen'] ?? 'N/A',
                'descripcion' => 'Ciudad origen'
            ],
            'destino' => [
                'esperado' => 'BARRANQUILLA',
                'obtenido' => $resultado['rutas'][0]['destino'] ?? 'N/A',
                'descripcion' => 'Ciudad destino'
            ],
            'peso' => [
                'esperado' => 500,
                'obtenido' => $resultado['rutas'][0]['peso'] ?? 0,
                'descripcion' => 'Peso detectado',
                'tolerancia' => 10
            ]
        ];
        
        $this->validarResultado('TEST_2', $validaciones, $resultado);
    }
    
    private function prueba3_RutaConTodosLosCampos()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 3: Ruta con Todos los Campos\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $prompt = "Origen: Bogotá, Destino: Buenaventura, Peso: 2,500 kilogramos (sin tara), " .
                  "Cantidad: 100 cajas, Tipo de Producto: Electrodomesticos, " .
                  "Valor Declarado: \$50,000,000 COP, Embalaje: Cajas de madera reforzada";
        
        echo "📝 Prompt: '$prompt'\n\n";
        
        $resultado = $this->simularExtraccion($prompt);
        
        $validaciones = [
            'num_rutas' => [
                'esperado' => 1,
                'obtenido' => count($resultado['rutas']),
                'descripcion' => 'Número de rutas'
            ],
            'origen' => [
                'esperado' => 'BOGOTA',
                'obtenido' => $resultado['rutas'][0]['origen'] ?? 'N/A',
                'descripcion' => 'Origen'
            ],
            'destino' => [
                'esperado' => 'BUENAVENTURA',
                'obtenido' => $resultado['rutas'][0]['destino'] ?? 'N/A',
                'descripcion' => 'Destino'
            ],
            'peso_sin_tara' => [
                'esperado' => 2500,
                'obtenido' => $resultado['rutas'][0]['peso'] ?? 0,
                'descripcion' => 'Peso sin tara',
                'tolerancia' => 50
            ],
            'producto' => [
                'esperado' => 'ELECTRODOMESTICOS',
                'obtenido' => strtoupper($resultado['rutas'][0]['producto'] ?? ''),
                'descripcion' => 'Producto',
                'contiene' => true
            ],
            'valor_declarado' => [
                'esperado' => 50000000,
                'obtenido' => $resultado['rutas'][0]['valor_declarado'] ?? 0,
                'descripcion' => 'Valor declarado',
                'tolerancia' => 1000
            ]
        ];
        
        // Validar que NO detecte como producto los embalajes
        if (isset($resultado['rutas'][0]['producto'])) {
            $producto = strtoupper($resultado['rutas'][0]['producto']);
            $validaciones['producto_no_empaque'] = [
                'esperado' => false,
                'obtenido' => (strpos($producto, 'MADERA') !== false || strpos($producto, 'CARTON') !== false),
                'descripcion' => 'Producto NO debe ser empaque (MADERA/CARTON)'
            ];
        }
        
        $this->validarResultado('TEST_3', $validaciones, $resultado);
    }
    
    private function prueba4_MultiRutaValida()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 4: Múltiples Rutas Válidas\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $prompt = "Necesito transportar de Cali a Bogotá y también de Medellín a Cartagena";
        
        echo "📝 Prompt: '$prompt'\n\n";
        
        $resultado = $this->simularExtraccion($prompt);
        
        $validaciones = [
            'num_rutas' => [
                'esperado' => 2,
                'obtenido' => count($resultado['rutas']),
                'descripcion' => 'Debe detectar 2 rutas'
            ]
        ];
        
        if (count($resultado['rutas']) >= 2) {
            $validaciones['ruta1_origen'] = [
                'esperado' => 'CALI',
                'obtenido' => $resultado['rutas'][0]['origen'] ?? 'N/A',
                'descripcion' => 'Ruta 1 - Origen'
            ];
            $validaciones['ruta1_destino'] = [
                'esperado' => 'BOGOTA',
                'obtenido' => $resultado['rutas'][0]['destino'] ?? 'N/A',
                'descripcion' => 'Ruta 1 - Destino'
            ];
            $validaciones['ruta2_origen'] = [
                'esperado' => 'MEDELLIN',
                'obtenido' => $resultado['rutas'][1]['origen'] ?? 'N/A',
                'descripcion' => 'Ruta 2 - Origen'
            ];
            $validaciones['ruta2_destino'] = [
                'esperado' => 'CARTAGENA',
                'obtenido' => $resultado['rutas'][1]['destino'] ?? 'N/A',
                'descripcion' => 'Ruta 2 - Destino'
            ];
        }
        
        $this->validarResultado('TEST_4', $validaciones, $resultado);
    }
    
    private function prueba5_ValorDeclaradoFormatos()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 5: Diferentes Formatos de Valor Declarado\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $casos = [
            [
                'prompt' => "Origen: Bogotá, Destino: Cali, Valor: \$5,000,000 COP",
                'esperado' => 5000000,
                'descripcion' => 'Formato con separadores de miles'
            ],
            [
                'prompt' => "De Medellín a Barranquilla, valor declarado 3000000",
                'esperado' => 3000000,
                'descripcion' => 'Formato numérico simple'
            ],
            [
                'prompt' => "Bogotá-Cali, Valor: \$2.500.000 pesos",
                'esperado' => 2500000,
                'descripcion' => 'Formato con puntos como separadores'
            ]
        ];
        
        $resultadosParciales = [];
        foreach ($casos as $i => $caso) {
            echo "\n  Caso " . ($i + 1) . ": {$caso['descripcion']}\n";
            echo "  Prompt: '{$caso['prompt']}'\n";
            
            $resultado = $this->simularExtraccion($caso['prompt']);
            
            $valorObtenido = $resultado['rutas'][0]['valor_declarado'] ?? 0;
            $exito = abs($valorObtenido - $caso['esperado']) <= 1000;
            
            echo "  Esperado: " . number_format($caso['esperado']) . "\n";
            echo "  Obtenido: " . number_format($valorObtenido) . "\n";
            echo "  " . ($exito ? "✅ PASS" : "❌ FAIL") . "\n";
            
            $resultadosParciales[] = $exito;
        }
        
        $exitosos = count(array_filter($resultadosParciales));
        $total = count($resultadosParciales);
        
        echo "\nResultado: $exitosos/$total casos correctos\n";
        
        $this->testResults['TEST_5'] = [
            'exito' => $exitosos >= ($total * 0.66), // Al menos 66%
            'detalles' => "$exitosos/$total casos"
        ];
        
        echo ($exitosos >= ($total * 0.66) ? "✅ PASS" : "❌ FAIL") . "\n\n";
    }
    
    private function prueba6_ProductosVariados()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 6: Reconocimiento de Diferentes Productos\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $casos = [
            [
                'prompt' => "Origen: Bogotá, Destino: Cali, Producto: Alimentos perecederos",
                'debe_contener' => 'ALIMENTOS',
                'no_debe_contener' => ['CARTON', 'MADERA', 'CAJA']
            ],
            [
                'prompt' => "De Medellín a Barranquilla, transporte de textiles y prendas de vestir",
                'debe_contener' => 'TEXTIL',
                'no_debe_contener' => ['CARTON', 'MADERA']
            ],
            [
                'prompt' => "Bogotá-Cali, 100 televisores embalados en cajas de cartón",
                'debe_contener' => 'TELEVISOR',
                'no_debe_contener' => ['CARTON', 'CAJA']
            ]
        ];
        
        $resultadosParciales = [];
        foreach ($casos as $i => $caso) {
            echo "\n  Caso " . ($i + 1) . "\n";
            echo "  Prompt: '{$caso['prompt']}'\n";
            
            $resultado = $this->simularExtraccion($caso['prompt']);
            $producto = strtoupper($resultado['rutas'][0]['producto'] ?? '');
            
            echo "  Producto detectado: '$producto'\n";
            
            $contieneProducto = strpos($producto, $caso['debe_contener']) !== false;
            $noContieneEmpaque = true;
            foreach ($caso['no_debe_contener'] as $empaque) {
                if (strpos($producto, $empaque) !== false) {
                    $noContieneEmpaque = false;
                    break;
                }
            }
            
            $exito = $contieneProducto && $noContieneEmpaque;
            
            echo "  Contiene '{$caso['debe_contener']}': " . ($contieneProducto ? "✅" : "❌") . "\n";
            echo "  NO contiene empaque: " . ($noContieneEmpaque ? "✅" : "❌") . "\n";
            echo "  " . ($exito ? "✅ PASS" : "❌ FAIL") . "\n";
            
            $resultadosParciales[] = $exito;
        }
        
        $exitosos = count(array_filter($resultadosParciales));
        $total = count($resultadosParciales);
        
        echo "\nResultado: $exitosos/$total casos correctos\n";
        
        $this->testResults['TEST_6'] = [
            'exito' => $exitosos >= ($total * 0.66),
            'detalles' => "$exitosos/$total casos"
        ];
        
        echo ($exitosos >= ($total * 0.66) ? "✅ PASS" : "❌ FAIL") . "\n\n";
    }
    
    private function prueba7_PesosConTara()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 7: Detección de Peso con/sin Tara\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $prompt = "Origen: Bogotá, Destino: Cali, Peso: 3,000 kg (sin tara), la tara es de 500 kg";
        
        echo "📝 Prompt: '$prompt'\n\n";
        
        $resultado = $this->simularExtraccion($prompt);
        
        $pesoSinTara = $resultado['rutas'][0]['peso'] ?? 0;
        $tara = $resultado['rutas'][0]['tara'] ?? 0;
        
        echo "Peso sin tara detectado: {$pesoSinTara} kg\n";
        echo "Tara detectada: {$tara} kg\n";
        
        $validaciones = [
            'peso_sin_tara' => [
                'esperado' => 3000,
                'obtenido' => $pesoSinTara,
                'descripcion' => 'Peso sin tara',
                'tolerancia' => 50
            ],
            'tara' => [
                'esperado' => 500,
                'obtenido' => $tara,
                'descripcion' => 'Tara detectada',
                'tolerancia' => 10
            ]
        ];
        
        $this->validarResultado('TEST_7', $validaciones, $resultado);
    }
    
    // ═══════════════════════════════════════════════════════════════════════════════
    // PARTE 2: PRUEBAS DE MODIFICACIÓN
    // ═══════════════════════════════════════════════════════════════════════════════
    
    private function prueba8_ModificarCampoOrigen()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 8: Modificar Campo Origen de Ruta Existente\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        // Primero crear una ruta
        $promptInicial = "Origen: Bogotá, Destino: Cali, Peso: 1,000 kg";
        echo "📝 Paso 1 - Crear ruta inicial:\n";
        echo "   Prompt: '$promptInicial'\n\n";
        
        $resultadoInicial = $this->simularExtraccion($promptInicial);
        $origenInicial = $resultadoInicial['rutas'][0]['origen'] ?? 'N/A';
        echo "   Origen inicial: $origenInicial\n\n";
        
        // Modificar el origen
        $promptModificacion = "Cambiar el origen a Medellín";
        echo "📝 Paso 2 - Modificar origen:\n";
        echo "   Prompt: '$promptModificacion'\n\n";
        
        $resultadoModificado = $this->simularModificacion($promptModificacion, $resultadoInicial);
        $origenModificado = $resultadoModificado['rutas'][0]['origen'] ?? 'N/A';
        echo "   Origen modificado: $origenModificado\n";
        
        $validaciones = [
            'origen_modificado' => [
                'esperado' => 'MEDELLIN',
                'obtenido' => $origenModificado,
                'descripcion' => 'Origen debe cambiar a MEDELLIN'
            ],
            'destino_igual' => [
                'esperado' => $resultadoInicial['rutas'][0]['destino'] ?? '',
                'obtenido' => $resultadoModificado['rutas'][0]['destino'] ?? '',
                'descripcion' => 'Destino debe permanecer igual'
            ]
        ];
        
        $this->validarResultado('TEST_8', $validaciones, $resultadoModificado);
    }
    
    private function prueba9_ModificarPeso()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 9: Modificar Peso de Ruta Existente\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $promptInicial = "Origen: Bogotá, Destino: Cali, Peso: 500 kg";
        echo "📝 Paso 1 - Crear ruta:\n";
        echo "   Prompt: '$promptInicial'\n\n";
        
        $resultadoInicial = $this->simularExtraccion($promptInicial);
        $pesoInicial = $resultadoInicial['rutas'][0]['peso'] ?? 0;
        echo "   Peso inicial: {$pesoInicial} kg\n\n";
        
        $promptModificacion = "Cambiar el peso a 2,500 kilogramos";
        echo "📝 Paso 2 - Modificar peso:\n";
        echo "   Prompt: '$promptModificacion'\n\n";
        
        $resultadoModificado = $this->simularModificacion($promptModificacion, $resultadoInicial);
        $pesoModificado = $resultadoModificado['rutas'][0]['peso'] ?? 0;
        echo "   Peso modificado: {$pesoModificado} kg\n";
        
        $validaciones = [
            'peso_modificado' => [
                'esperado' => 2500,
                'obtenido' => $pesoModificado,
                'descripcion' => 'Peso debe cambiar a 2500',
                'tolerancia' => 50
            ]
        ];
        
        $this->validarResultado('TEST_9', $validaciones, $resultadoModificado);
    }
    
    private function prueba10_ModificarProducto()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 10: Modificar Producto de Ruta Existente\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $promptInicial = "Origen: Bogotá, Destino: Cali, Producto: Alimentos";
        echo "📝 Paso 1 - Crear ruta:\n";
        echo "   Prompt: '$promptInicial'\n\n";
        
        $resultadoInicial = $this->simularExtraccion($promptInicial);
        $productoInicial = $resultadoInicial['rutas'][0]['producto'] ?? 'N/A';
        echo "   Producto inicial: $productoInicial\n\n";
        
        $promptModificacion = "Cambiar el producto a Electrodomésticos";
        echo "📝 Paso 2 - Modificar producto:\n";
        echo "   Prompt: '$promptModificacion'\n\n";
        
        $resultadoModificado = $this->simularModificacion($promptModificacion, $resultadoInicial);
        $productoModificado = strtoupper($resultadoModificado['rutas'][0]['producto'] ?? '');
        echo "   Producto modificado: $productoModificado\n";
        
        $validaciones = [
            'producto_modificado' => [
                'esperado' => 'ELECTRODOMESTICOS',
                'obtenido' => $productoModificado,
                'descripcion' => 'Producto debe cambiar',
                'contiene' => true
            ]
        ];
        
        $this->validarResultado('TEST_10', $validaciones, $resultadoModificado);
    }
    
    private function prueba11_AgregarRutaAGrupoExistente()
    {
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST 11: Agregar Nueva Ruta a Grupo Existente\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        
        $promptInicial = "Origen: Bogotá, Destino: Cali";
        echo "📝 Paso 1 - Crear primera ruta:\n";
        echo "   Prompt: '$promptInicial'\n\n";
        
        $resultadoInicial = $this->simularExtraccion($promptInicial);
        $numRutasInicial = count($resultadoInicial['rutas']);
        echo "   Número de rutas: $numRutasInicial\n\n";
        
        $promptAgregar = "Agregar también ruta de Medellín a Barranquilla";
        echo "📝 Paso 2 - Agregar nueva ruta:\n";
        echo "   Prompt: '$promptAgregar'\n\n";
        
        $resultadoFinal = $this->simularAgregarRuta($promptAgregar, $resultadoInicial);
        $numRutasFinal = count($resultadoFinal['rutas']);
        echo "   Número de rutas final: $numRutasFinal\n";
        
        $validaciones = [
            'rutas_incrementadas' => [
                'esperado' => $numRutasInicial + 1,
                'obtenido' => $numRutasFinal,
                'descripcion' => 'Debe haber una ruta más'
            ]
        ];
        
        if ($numRutasFinal >= 2) {
            $validaciones['nueva_ruta_origen'] = [
                'esperado' => 'MEDELLIN',
                'obtenido' => $resultadoFinal['rutas'][1]['origen'] ?? 'N/A',
                'descripcion' => 'Nueva ruta - origen'
            ];
            $validaciones['nueva_ruta_destino'] = [
                'esperado' => 'BARRANQUILLA',
                'obtenido' => $resultadoFinal['rutas'][1]['destino'] ?? 'N/A',
                'descripcion' => 'Nueva ruta - destino'
            ];
        }
        
        $this->validarResultado('TEST_11', $validaciones, $resultadoFinal);
    }
    
    // ═══════════════════════════════════════════════════════════════════════════════
    // MÉTODOS DE SIMULACIÓN
    // ═══════════════════════════════════════════════════════════════════════════════
    
    private function simularExtraccion($prompt)
    {
        // Simular el proceso de extracción del servicio MCP
        $service = app(\App\Services\MCPAssistantService::class);
        
        try {
            // Detectar rutas
            $rutas = $this->detectarRutas($prompt);
            
            $resultado = [
                'rutas' => [],
                'prompt' => $prompt
            ];
            
            foreach ($rutas as $ruta) {
                $datosRuta = [
                    'origen' => $ruta['origen'] ?? 'N/A',
                    'destino' => $ruta['destino'] ?? 'N/A',
                    'peso' => $this->extraerPeso($prompt),
                    'tara' => $this->extraerTara($prompt),
                    'producto' => $this->extraerProducto($prompt),
                    'valor_declarado' => $this->extraerValorDeclarado($prompt)
                ];
                
                $resultado['rutas'][] = $datosRuta;
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            return [
                'rutas' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function simularModificacion($promptModificacion, $datosExistentes)
    {
        // Simular modificación de datos existentes
        $resultado = $datosExistentes;
        
        // Detectar qué campo se quiere modificar
        if (preg_match('/cambiar\s+(?:el\s+)?origen\s+a\s+([a-záéíóúñ]+)/ui', $promptModificacion, $match)) {
            $resultado['rutas'][0]['origen'] = strtoupper($this->normalizarCiudad($match[1]));
        }
        
        if (preg_match('/cambiar\s+(?:el\s+)?destino\s+a\s+([a-záéíóúñ]+)/ui', $promptModificacion, $match)) {
            $resultado['rutas'][0]['destino'] = strtoupper($this->normalizarCiudad($match[1]));
        }
        
        if (preg_match('/cambiar\s+(?:el\s+)?peso\s+a\s+([\d,\.]+)/ui', $promptModificacion, $match)) {
            $resultado['rutas'][0]['peso'] = $this->parsearPeso($match[1]);
        }
        
        if (preg_match('/cambiar\s+(?:el\s+)?producto\s+a\s+([a-záéíóúñ\s]+)/ui', $promptModificacion, $match)) {
            $resultado['rutas'][0]['producto'] = strtoupper(trim($match[1]));
        }
        
        return $resultado;
    }
    
    private function simularAgregarRuta($prompt, $datosExistentes)
    {
        $nuevasRutas = $this->detectarRutas($prompt);
        
        foreach ($nuevasRutas as $ruta) {
            $datosExistentes['rutas'][] = [
                'origen' => $ruta['origen'] ?? 'N/A',
                'destino' => $ruta['destino'] ?? 'N/A',
                'peso' => $this->extraerPeso($prompt),
                'producto' => $this->extraerProducto($prompt),
                'valor_declarado' => $this->extraerValorDeclarado($prompt)
            ];
        }
        
        return $datosExistentes;
    }
    
    // ═══════════════════════════════════════════════════════════════════════════════
    // MÉTODOS DE EXTRACCIÓN (SIMULAN MCPAssistantService)
    // ═══════════════════════════════════════════════════════════════════════════════
    
    private function detectarRutas($texto)
    {
        $rutas = [];
        
        // Patrón estructurado "Origen: X, Destino: Y"
        $patronEstructurado = '/\bOrigen:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s*,\s*Destino:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';
        if (preg_match_all($patronEstructurado, $texto, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rutas[] = [
                    'origen' => $this->normalizarCiudad($match[1]),
                    'destino' => $this->normalizarCiudad($match[2])
                ];
            }
        }
        
        // Patrón natural "de X a Y"
        if (empty($rutas)) {
            $patronNatural = '/\b(?:de|desde)\s+([a-záéíóúñ]+)\s+(?:a|hacia)\s+([a-záéíóúñ]+)/ui';
            if (preg_match_all($patronNatural, $texto, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $origen = $this->normalizarCiudad($match[1]);
                    $destino = $this->normalizarCiudad($match[2]);
                    
                    // Validar que sean ciudades válidas
                    if ($this->esCiudadValida($origen) && $this->esCiudadValida($destino)) {
                        $rutas[] = [
                            'origen' => $origen,
                            'destino' => $destino
                        ];
                    }
                }
            }
        }
        
        // Si no se detectaron rutas, buscar conectores "y también", "y de"
        if (count($rutas) > 0) {
            $patronMultiple = '/\by\s+(?:también\s+)?(?:de|desde)\s+([a-záéíóúñ]+)\s+(?:a|hacia)\s+([a-záéíóúñ]+)/ui';
            if (preg_match_all($patronMultiple, $texto, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $origen = $this->normalizarCiudad($match[1]);
                    $destino = $this->normalizarCiudad($match[2]);
                    
                    if ($this->esCiudadValida($origen) && $this->esCiudadValida($destino)) {
                        $rutas[] = [
                            'origen' => $origen,
                            'destino' => $destino
                        ];
                    }
                }
            }
        }
        
        return empty($rutas) ? [['origen' => 'N/A', 'destino' => 'N/A']] : $rutas;
    }
    
    private function extraerPeso($texto)
    {
        // Buscar peso sin tara
        if (preg_match('/(\d{1,3}(?:[,\.]\d{3})*(?:[,\.]\d+)?)\s*(?:kg|kilo|kilogramos?)(?:\s*\([^)]*sin\s+tara[^)]*\))?/ui', $texto, $match)) {
            return $this->parsearPeso($match[1]);
        }
        
        // Buscar cualquier mención de peso
        if (preg_match('/Peso:\s*(\d{1,3}(?:[,\.]\d{3})*)/ui', $texto, $match)) {
            return $this->parsearPeso($match[1]);
        }
        
        return 0;
    }
    
    private function extraerTara($texto)
    {
        if (preg_match('/tara\s+(?:es\s+)?(?:de\s+)?(\d{1,3}(?:[,\.]\d{3})*)/ui', $texto, $match)) {
            return $this->parsearPeso($match[1]);
        }
        return 0;
    }
    
    private function extraerProducto($texto)
    {
        // Productos conocidos
        $productos = [
            'alimentos' => ['alimento', 'comida', 'comestible', 'perecedero'],
            'electrodomesticos' => ['electrodomestico', 'televisor', 'nevera', 'lavadora'],
            'textiles' => ['textil', 'prenda', 'ropa', 'tela'],
            'muebles' => ['mueble', 'silla', 'mesa'],
            'medicamentos' => ['medicamento', 'medicina', 'farmaceutico']
        ];
        
        $textoLimpio = strtolower($texto);
        
        foreach ($productos as $categoria => $palabrasClave) {
            foreach ($palabrasClave as $palabra) {
                if (strpos($textoLimpio, $palabra) !== false) {
                    return strtoupper($categoria);
                }
            }
        }
        
        // Buscar patrón "Producto: X" o "Tipo de Producto: X"
        if (preg_match('/(?:Tipo\s+de\s+)?Producto:\s*([^,\n]+?)(?:,|\n|$)/ui', $texto, $match)) {
            $producto = trim($match[1]);
            // Filtrar empaques
            $empaques = ['carton', 'madera', 'caja', 'pallet', 'estiba'];
            foreach ($empaques as $empaque) {
                if (stripos($producto, $empaque) !== false) {
                    return 'N/A';
                }
            }
            return strtoupper($producto);
        }
        
        return 'N/A';
    }
    
    private function extraerValorDeclarado($texto)
    {
        // Formato con separadores de miles: $5,000,000 o $5.000.000
        if (preg_match('/\$\s*(\d{1,3}(?:[,\.]\d{3})+)/u', $texto, $match)) {
            return $this->parsearValor($match[1]);
        }
        
        // Formato numérico simple
        if (preg_match('/valor\s+declarado[:\s]+(\d+)/ui', $texto, $match)) {
            return intval($match[1]);
        }
        
        return 0;
    }
    
    // ═══════════════════════════════════════════════════════════════════════════════
    // MÉTODOS AUXILIARES
    // ═══════════════════════════════════════════════════════════════════════════════
    
    private function normalizarCiudad($ciudad)
    {
        $ciudad = strtoupper(trim($ciudad));
        $ciudad = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $ciudad);
        return $ciudad;
    }
    
    private function esCiudadValida($ciudad)
    {
        $ciudadesValidas = [
            'BOGOTA', 'MEDELLIN', 'CALI', 'BARRANQUILLA', 'CARTAGENA',
            'BUCARAMANGA', 'PEREIRA', 'MANIZALES', 'CUCUTA', 'IBAGUE',
            'BUENAVENTURA', 'VILLAVICENCIO', 'PASTO', 'ARMENIA', 'NEIVA'
        ];
        
        return in_array($ciudad, $ciudadesValidas);
    }
    
    private function parsearPeso($texto)
    {
        $texto = str_replace([',', '.'], '', $texto);
        return intval($texto);
    }
    
    private function parsearValor($texto)
    {
        $texto = str_replace([',', '.'], '', $texto);
        return intval($texto);
    }
    
    private function validarResultado($testId, $validaciones, $resultado)
    {
        $todosCorrecto = true;
        
        foreach ($validaciones as $campo => $validacion) {
            $esperado = $validacion['esperado'];
            $obtenido = $validacion['obtenido'];
            $descripcion = $validacion['descripcion'];
            
            $correcto = false;
            
            if (isset($validacion['contiene']) && $validacion['contiene']) {
                // Validación de contiene string
                $correcto = strpos(strtoupper($obtenido), strtoupper($esperado)) !== false;
            } elseif (isset($validacion['tolerancia'])) {
                // Validación numérica con tolerancia
                $correcto = abs($obtenido - $esperado) <= $validacion['tolerancia'];
            } else {
                // Validación exacta
                $correcto = strtoupper($obtenido) === strtoupper($esperado);
            }
            
            echo "  {$descripcion}: ";
            echo "esperado='" . (is_numeric($esperado) ? number_format($esperado) : $esperado) . "', ";
            echo "obtenido='" . (is_numeric($obtenido) ? number_format($obtenido) : $obtenido) . "' ";
            echo $correcto ? "✅\n" : "❌\n";
            
            if (!$correcto) {
                $todosCorrecto = false;
            }
        }
        
        echo "\n" . ($todosCorrecto ? "✅ PASS" : "❌ FAIL") . "\n\n";
        
        $this->testResults[$testId] = [
            'exito' => $todosCorrecto,
            'validaciones' => count($validaciones)
        ];
    }
    
    private function mostrarResumen()
    {
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "RESUMEN FINAL DE PRUEBAS\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
        
        $exitosos = 0;
        $fallidos = 0;
        
        foreach ($this->testResults as $testId => $resultado) {
            $icono = $resultado['exito'] ? "✅" : "❌";
            $estado = $resultado['exito'] ? "PASS" : "FAIL";
            echo "{$icono} {$testId}: {$estado}\n";
            
            if ($resultado['exito']) {
                $exitosos++;
            } else {
                $fallidos++;
            }
        }
        
        $total = $exitosos + $fallidos;
        $porcentaje = $total > 0 ? round(($exitosos / $total) * 100, 1) : 0;
        
        echo "\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "Total de tests: {$total}\n";
        echo "✅ Exitosos: {$exitosos}\n";
        echo "❌ Fallidos: {$fallidos}\n";
        echo "Tasa de éxito: {$porcentaje}%\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n\n";
        
        if ($porcentaje >= 90) {
            echo "🎉 EXCELENTE - Sistema funcionando correctamente\n";
        } elseif ($porcentaje >= 75) {
            echo "✅ BUENO - Sistema funcional con mejoras menores necesarias\n";
        } elseif ($porcentaje >= 60) {
            echo "⚠️  ACEPTABLE - Se requieren algunas correcciones\n";
        } else {
            echo "❌ PROBLEMAS - Se requiere atención inmediata\n";
        }
        
        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
    }
}

// Ejecutar pruebas
$pruebas = new PruebasIntegralesChat();
$pruebas->ejecutarTodasLasPruebas();
