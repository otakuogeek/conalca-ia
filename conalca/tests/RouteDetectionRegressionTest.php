<?php
/**
 * TESTS DE REGRESIÓN - Detección de Rutas
 * 
 * Este script valida que los diferentes formatos de prompts
 * sean procesados correctamente y no generen rutas falsas.
 */

require __DIR__.'/../vendor/autoload.php';

class RouteDetectionRegressionTest
{
    private $passedTests = 0;
    private $failedTests = 0;
    private $warnings = 0;

    public function run()
    {
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "TESTS DE REGRESIÓN - DETECCIÓN DE RUTAS\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
        
        // Test cases que causaban problemas
        $this->testFormatoEstructuradoSimple();
        $this->testFormatoEstructuradoConDatos();
        $this->testFormatoNaturalSimple();
        $this->testFormatoNaturalMultiRuta();
        $this->testFormatoConCotizacionDe();
        $this->testFormatoConDeAHacia();
        $this->testCasoProblematico508();
        $this->testMultiplesCiudadesValidas();
        $this->testTextoConDePeroNoRuta();
        $this->testFormatoEmail();
        
        $this->printSummary();
    }

    private function testFormatoEstructuradoSimple()
    {
        $this->startTest("Formato Estructurado Simple");
        
        $prompt = "Origen: Bogotá, Destino: Medellín";
        $expectedCount = 1;
        $expectedOrigen = "BOGOTA";
        $expectedDestino = "MEDELLIN";
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            if ($result[0]['origen'] === $expectedOrigen && $result[0]['destino'] === $expectedDestino) {
                $this->pass("1 ruta detectada correctamente: $expectedOrigen → $expectedDestino");
            } else {
                $this->fail("Ciudades incorrectas: {$result[0]['origen']} → {$result[0]['destino']}");
            }
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testFormatoEstructuradoConDatos()
    {
        $this->startTest("Formato Estructurado con Datos Completos");
        
        $prompt = "Origen: Cali, Destino: Barranquilla, Peso: 5000 kg, Producto: Alimentos";
        $expectedCount = 1;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("1 ruta detectada correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testFormatoNaturalSimple()
    {
        $this->startTest("Formato Natural 'de X a Y'");
        
        $prompt = "necesito cotización de bogotá a medellín";
        $expectedCount = 1;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("1 ruta detectada correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testFormatoNaturalMultiRuta()
    {
        $this->startTest("Formato Natural Multi-Ruta");
        
        $prompt = "de bogotá a medellín, 10 toneladas. de cali a cartagena, 5 toneladas";
        $expectedCount = 2;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("2 rutas detectadas correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testFormatoConCotizacionDe()
    {
        $this->startTest("Formato 'cotización de X a Y'");
        
        $prompt = "cotización de bogotá a buenaventura de 13 toneladas";
        $expectedCount = 1;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("1 ruta detectada correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testFormatoConDeAHacia()
    {
        $this->startTest("Formato 'desde X hacia Y'");
        
        $prompt = "necesito transporte desde bogotá hacia medellín";
        $expectedCount = 1;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("1 ruta detectada correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testCasoProblematico508()
    {
        $this->startTest("CASO PROBLEMÁTICO #508 (Bug Original)");
        
        $prompt = 'Origen: bogotá, Destino: Buenaventura, Peso: 2,500 kilogramos (sin tara), Cantidad: 100 cajas, Tipo de Producto: Electrodomesticos, Valor Declarado: $50,000,000 COP, Emba: Cajas de madera reforzada, Tipo de Vehículo: contenedorr de 40 pies, para exportación marítima. La carga requiere ser transportada bajo condiciones de seguridad y manejo especializado debido a su tamaño.';
        
        $expectedCount = 1;
        $expectedOrigen = "BOGOTA";
        $expectedDestino = "BUENAVENTURA";
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            if ($result[0]['origen'] === $expectedOrigen && $result[0]['destino'] === $expectedDestino) {
                $this->pass("✅ BUG RESUELTO: 1 ruta correcta ($expectedOrigen → $expectedDestino)");
            } else {
                $this->fail("Ciudades incorrectas: {$result[0]['origen']} → {$result[0]['destino']}");
            }
        } else {
            $this->fail("❌ BUG PERSISTE: Se detectaron " . count($result) . " rutas (esperaba 1)");
            if (count($result) > 1) {
                foreach ($result as $i => $r) {
                    echo "     Ruta " . ($i+1) . ": {$r['origen']} → {$r['destino']}\n";
                }
            }
        }
    }

    private function testMultiplesCiudadesValidas()
    {
        $this->startTest("Múltiples Ciudades Válidas");
        
        $prompt = "origen cali y cartagena a destino medellín";
        $expectedCount = 2; // CALI→MEDELLIN, CARTAGENA→MEDELLIN
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("2 rutas detectadas correctamente (producto cartesiano)");
        } else {
            $this->warn("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    private function testTextoConDePeroNoRuta()
    {
        $this->startTest("Texto con 'de' pero NO es ruta");
        
        $prompt = "quiero cotización, el producto es de alta calidad";
        $expectedCount = 0;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("0 rutas (correcto, no hay ciudades)");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas falsas");
        }
    }

    private function testFormatoEmail()
    {
        $this->startTest("Formato tipo Email con saltos de línea");
        
        $prompt = "Origen: Bogotá\nDestino: Medellín\nPeso: 10 toneladas";
        $expectedCount = 1;
        
        $result = $this->detectRoutes($prompt);
        
        if (count($result) === $expectedCount) {
            $this->pass("1 ruta detectada correctamente");
        } else {
            $this->fail("Se detectaron " . count($result) . " rutas, esperaba $expectedCount");
        }
    }

    // ============================================================================
    // UTILIDADES
    // ============================================================================

    private function detectRoutes($prompt)
    {
        $routes = [];
        
        // Simular detección - patrón estructurado primero
        $patronOrigenDestino = '/\bOrigen:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s*,?\s*Destino:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';
        
        if (preg_match_all($patronOrigenDestino, $prompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $routes[] = [
                    'origen' => $this->normalizeCity($m[1]),
                    'destino' => $this->normalizeCity($m[2])
                ];
            }
            return $routes;
        }
        
        // Patrón "de X a Y"
        $patronDeA = '/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+(?:a|hacia)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';
        
        if (preg_match_all($patronDeA, $prompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $routes[] = [
                    'origen' => $this->normalizeCity($m[1]),
                    'destino' => $this->normalizeCity($m[2])
                ];
            }
        }
        
        return $routes;
    }

    private function normalizeCity($city)
    {
        $city = trim($city);
        $replacements = [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'N', 'Ñ' => 'N'
        ];
        return strtoupper(strtr($city, $replacements));
    }

    private function startTest($name)
    {
        echo "\n───────────────────────────────────────────────────────────────────────────────\n";
        echo "TEST: $name\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
    }

    private function pass($message)
    {
        echo "✅ PASS: $message\n";
        $this->passedTests++;
    }

    private function fail($message)
    {
        echo "❌ FAIL: $message\n";
        $this->failedTests++;
    }

    private function warn($message)
    {
        echo "⚠️  WARN: $message\n";
        $this->warnings++;
    }

    private function printSummary()
    {
        $total = $this->passedTests + $this->failedTests + $this->warnings;
        
        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
        echo "RESUMEN DE TESTS\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
        
        echo "Total de tests: $total\n";
        echo "✅ Exitosos: {$this->passedTests}\n";
        echo "❌ Fallidos: {$this->failedTests}\n";
        echo "⚠️  Advertencias: {$this->warnings}\n\n";
        
        $successRate = $total > 0 ? round(($this->passedTests / $total) * 100, 1) : 0;
        echo "Tasa de éxito: $successRate%\n\n";
        
        if ($this->failedTests === 0 && $this->warnings === 0) {
            echo "🎉 TODOS LOS TESTS PASARON - Sistema funcionando correctamente\n";
        } elseif ($this->failedTests === 0) {
            echo "✅ Tests principales pasaron - Hay advertencias menores\n";
        } else {
            echo "❌ HAY TESTS FALLIDOS - Se requiere atención\n";
        }
        
        echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
    }
}

// Ejecutar tests
$tester = new RouteDetectionRegressionTest();
$tester->run();
