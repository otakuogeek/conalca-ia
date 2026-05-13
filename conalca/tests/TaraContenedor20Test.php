<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * 🧪 TEST: TARA ESPECÍFICA PARA CONTENEDOR DE 20 PIES
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * REGLA DE NEGOCIO:
 * - Contenedor de 20 pies: Tara = 2,300 kg (ÚNICO)
 * - Contenedor de 40/45 pies u otros: Tara = 3,400 kg
 * 
 * Este script prueba específicamente casos de contenedor de 20 pies
 * para verificar que la tara de 2,300 kg se aplique correctamente.
 * 
 * @author Copilot
 * @date 2026-02-05
 * ═══════════════════════════════════════════════════════════════════════════════
 */

namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\MCPAssistantService;

class TaraContenedor20Test
{
    private $passed = 0;
    private $failed = 0;
    
    const TARA_20 = 2300;
    const TARA_40 = 3400;

    /**
     * Ejecutar todas las pruebas
     */
    public function run()
    {
        $this->printHeader();
        
        // Grupo 1: Detección de contenedor 20 en diferentes formatos
        $this->runGroup('1. DETECCIÓN DE CONTENEDOR 20 (Tara debe ser 2300)', [
            ['texto' => '1x20 contenedor', 'esperado' => self::TARA_20],
            ['texto' => '2x20 GP exportación', 'esperado' => self::TARA_20],
            ['texto' => '1x20 OT importación', 'esperado' => self::TARA_20],
            ['texto' => "1x20'HC contenedor", 'esperado' => self::TARA_20],
            ['texto' => 'contenedor de 20 pies', 'esperado' => self::TARA_20],
            ['texto' => 'contenedor 20 pies con carga', 'esperado' => self::TARA_20],
            ['texto' => '(1) 20 PIES exportación', 'esperado' => self::TARA_20],
            ['texto' => 'es 1 contenedor de 20 pies con 14.000 kilogramos', 'esperado' => self::TARA_20],
            ['texto' => 'importación en contenedor de 20 pies', 'esperado' => self::TARA_20],
            ['texto' => 'necesito 1 contenedor de 20 pies sin tara', 'esperado' => self::TARA_20],
        ]);

        // Grupo 2: Cálculos de peso con contenedor 20
        $this->runGroup('2. CÁLCULOS DE PESO CON CONTENEDOR 20', [
            ['peso_base' => 10000, 'texto' => '1x20', 'peso_esperado' => 12300, 'descripcion' => '10 ton + tara 20\''],
            ['peso_base' => 14000, 'texto' => 'contenedor de 20 pies', 'peso_esperado' => 16300, 'descripcion' => '14 ton + tara 20\''],
            ['peso_base' => 15000, 'texto' => '1x20 GP', 'peso_esperado' => 17300, 'descripcion' => '15 ton + tara 20\''],
            ['peso_base' => 18000, 'texto' => '2x20', 'peso_esperado' => 20300, 'descripcion' => '18 ton + tara 20\''],
            ['peso_base' => 20000, 'texto' => 'contenedor 20 pies', 'peso_esperado' => 22300, 'descripcion' => '20 ton + tara 20\''],
            ['peso_base' => 5000, 'texto' => '1x20 OT', 'peso_esperado' => 7300, 'descripcion' => '5 ton + tara 20\''],
        ]);

        // Grupo 3: Verificar que contenedor 40 NO use tara de 2300
        $this->runGroup('3. CONTENEDOR 40 NO DEBE USAR TARA 2300', [
            ['texto' => '1x40 contenedor', 'esperado' => self::TARA_40],
            ['texto' => '2x40 HC importación', 'esperado' => self::TARA_40],
            ['texto' => 'contenedor de 40 pies', 'esperado' => self::TARA_40],
            ['texto' => '1x45 contenedor especial', 'esperado' => self::TARA_40],
        ]);

        // Grupo 4: Casos mixtos (múltiples contenedores)
        $this->runGroup('4. CASOS MIXTOS - PRIMERA DETECCIÓN', [
            ['texto' => '2x40 // 1x20 Retiro: Medellín', 'esperado' => self::TARA_40, 'nota' => 'Primer match es 40'],
            ['texto' => '1x20 // 2x40 Entrega: Bogotá', 'esperado' => self::TARA_20, 'nota' => 'Primer match es 20'],
        ]);

        // Grupo 5: Detección por campo empaque
        $this->runGroup('5. DETECCIÓN POR CAMPO EMPAQUE', [
            ['empaque' => 'CONTENEDOR 20', 'esperado' => self::TARA_20],
            ['empaque' => 'CONTENEDOR  20', 'esperado' => self::TARA_20],
            ['empaque' => 'CONTENEDOR (1) 20 PIES', 'esperado' => self::TARA_20],
            ['empaque' => 'CONTENEDOR 40', 'esperado' => self::TARA_40],
            ['empaque' => 'CONTENEDOR (1) 40 PIES', 'esperado' => self::TARA_40],
        ]);

        // Grupo 6: Ejemplos reales de prompts
        $this->runGroup('6. EJEMPLOS REALES DE PROMPTS CON CONTENEDOR 20', [
            [
                'texto' => 'Hola, necesito cotizar una importación Cartagena – Medellín, es 1 contenedor de 20 pies con 14.000 kilogramos sin tara',
                'esperado' => self::TARA_20,
                'descripcion' => 'Prompt completo de importación'
            ],
            [
                'texto' => '1x20 Retiro: Buenaventura Entrega: Bogotá Peso: 12 ton sin tara',
                'esperado' => self::TARA_20,
                'descripcion' => 'Formato corto con 1x20'
            ],
            [
                'texto' => 'Necesito mover 1 contenedor de 20 pies desde el puerto hasta la bodega, peso 8 toneladas sin tara',
                'esperado' => self::TARA_20,
                'descripcion' => 'Descripción narrativa'
            ],
            [
                'texto' => 'cotización para transporte de contenedor 20 pies, 15000 kg netos',
                'esperado' => self::TARA_20,
                'descripcion' => 'Peso en kg netos'
            ],
            [
                'texto' => '2x40 // 1x20 Retiro: medellín Destino: Cartagena Peso: 15 toneladas sin tara c/u',
                'esperado' => self::TARA_40, // Primera detección es 40
                'descripcion' => 'Multi-ruta con c/u - Primera ruta 40'
            ],
        ]);

        // Grupo 7: Verificación de patrón "c/u" (cada uno)
        $this->runGroup('7. PATRÓN "C/U" (CADA UNO)', $this->testPatronCadaUno());

        $this->printSummary();
    }

    /**
     * Test específico para patrón c/u
     */
    private function testPatronCadaUno(): array
    {
        return [
            ['patron' => '15 toneladas sin tara c/u', 'espera_match' => true, 'peso_esperado' => 15000],
            ['patron' => '20 kg cada uno', 'espera_match' => true, 'peso_esperado' => 20],
            ['patron' => '10 toneladas por contenedor', 'espera_match' => true, 'peso_esperado' => 10000],
            ['patron' => '8 ton c/U', 'espera_match' => true, 'peso_esperado' => 8000],
            ['patron' => '5000 kg cada una', 'espera_match' => true, 'peso_esperado' => 5000],
            ['patron' => '15 toneladas sin tara', 'espera_match' => false, 'peso_esperado' => null], // Sin c/u
        ];
    }

    /**
     * Ejecutar un grupo de pruebas
     */
    private function runGroup(string $groupName, array $tests)
    {
        echo "\n" . str_repeat('─', 80) . "\n";
        echo "📁 {$groupName}\n";
        echo str_repeat('─', 80) . "\n";

        foreach ($tests as $test) {
            if (isset($test['peso_base'])) {
                // Test de cálculo de peso
                $this->testCalculoPeso($test);
            } elseif (isset($test['empaque'])) {
                // Test de detección por empaque
                $this->testDeteccionEmpaque($test);
            } elseif (isset($test['patron'])) {
                // Test de patrón c/u
                $this->testPatronCU($test);
            } else {
                // Test de detección de tara
                $this->testDeteccionTara($test);
            }
        }
    }

    /**
     * Test de patrón c/u (cada uno)
     */
    private function testPatronCU(array $test)
    {
        $patron = $test['patron'];
        $esperaMatch = $test['espera_match'];
        $pesoEsperado = $test['peso_esperado'];
        
        // Usar el mismo patrón regex que está en processMultipleRoutes
        $regex = '/(\d+(?:[.,]\d+)?)\s*(?:toneladas?|ton|kg|kilos?)\s+(?:sin\s+tara\s+)?(?:c\/u|c\/U|cada\s+un[oa]?|por\s+(?:cada\s+)?(?:contenedor|ruta))/ui';
        $matches = preg_match($regex, $patron, $matchResult);
        
        $detecto = $matches === 1;
        $pesoDetectado = null;
        
        if ($detecto && isset($matchResult[1])) {
            $pesoRaw = str_replace(['.', ','], ['', '.'], $matchResult[1]);
            $pesoDetectado = (float)$pesoRaw;
            if (preg_match('/toneladas?|ton/ui', $patron)) {
                $pesoDetectado *= 1000;
            }
        }
        
        $passed = ($detecto === $esperaMatch);
        if ($esperaMatch && $detecto && $pesoEsperado !== null) {
            $passed = $passed && ($pesoDetectado == $pesoEsperado);
        }
        
        $this->recordResult($passed);
        
        $icon = $passed ? '✅' : '❌';
        $status = $passed ? 'OK' : 'FALLO';
        
        echo "  {$icon} Patrón: \"{$patron}\"\n";
        if ($esperaMatch) {
            echo "     └─ Peso esperado: {$pesoEsperado} kg, Detectado: " . ($pesoDetectado ?? 'N/A') . " kg [{$status}]\n";
        } else {
            echo "     └─ No debe detectar c/u: " . ($detecto ? 'DETECTÓ ❌' : 'No detectó ✅') . " [{$status}]\n";
        }
    }

    /**
     * Test de detección de tara
     */
    private function testDeteccionTara(array $test)
    {
        $texto = $test['texto'];
        $esperado = $test['esperado'];
        $nota = $test['nota'] ?? '';
        $descripcion = $test['descripcion'] ?? substr($texto, 0, 50) . '...';
        
        $tara = $this->callGetTaraByContenedor($texto);
        
        $passed = $tara === $esperado;
        $this->recordResult($passed);
        
        $icon = $passed ? '✅' : '❌';
        $status = $passed ? 'OK' : 'FALLO';
        
        echo "  {$icon} Texto: \"{$descripcion}\"\n";
        echo "     └─ Tara esperada: {$esperado} kg, Obtenida: {$tara} kg [{$status}]";
        if ($nota) echo " ({$nota})";
        echo "\n";
    }

    /**
     * Test de cálculo de peso
     */
    private function testCalculoPeso(array $test)
    {
        $pesoBase = $test['peso_base'];
        $texto = $test['texto'];
        $pesoEsperado = $test['peso_esperado'];
        $descripcion = $test['descripcion'];
        
        $tara = $this->callGetTaraByContenedor($texto);
        $pesoCalculado = $pesoBase + $tara;
        
        $passed = $pesoCalculado === $pesoEsperado;
        $this->recordResult($passed);
        
        $icon = $passed ? '✅' : '❌';
        $status = $passed ? 'OK' : 'FALLO';
        
        echo "  {$icon} {$descripcion}\n";
        echo "     └─ {$pesoBase} kg + {$tara} kg (tara) = {$pesoCalculado} kg";
        echo " [Esperado: {$pesoEsperado} kg] [{$status}]\n";
    }

    /**
     * Test de detección por empaque
     */
    private function testDeteccionEmpaque(array $test)
    {
        $empaque = $test['empaque'];
        $esperado = $test['esperado'];
        
        // Simular la lógica de detección por empaque
        $tara = 3400; // Default
        if (preg_match('/CONTENEDOR\s*20|20\s*pies/i', $empaque)) {
            $tara = 2300;
        }
        
        $passed = $tara === $esperado;
        $this->recordResult($passed);
        
        $icon = $passed ? '✅' : '❌';
        $status = $passed ? 'OK' : 'FALLO';
        
        echo "  {$icon} Empaque: \"{$empaque}\"\n";
        echo "     └─ Tara esperada: {$esperado} kg, Obtenida: {$tara} kg [{$status}]\n";
    }

    /**
     * Llamar a getTaraByContenedor usando reflection
     */
    private function callGetTaraByContenedor(string $text): int
    {
        $reflection = new \ReflectionClass(MCPAssistantService::class);
        $method = $reflection->getMethod('getTaraByContenedor');
        $method->setAccessible(true);
        return $method->invoke(null, $text);
    }

    /**
     * Registrar resultado
     */
    private function recordResult(bool $passed)
    {
        if ($passed) {
            $this->passed++;
        } else {
            $this->failed++;
        }
    }

    /**
     * Imprimir header
     */
    private function printHeader()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  🧪 TEST: TARA ESPECÍFICA PARA CONTENEDOR DE 20 PIES                           ║\n";
        echo "╠════════════════════════════════════════════════════════════════════════════════╣\n";
        echo "║  REGLA DE NEGOCIO:                                                             ║\n";
        echo "║  • Contenedor 20 pies → Tara = 2,300 kg  ← ÚNICO CON TARA DIFERENTE            ║\n";
        echo "║  • Contenedor 40/45 pies → Tara = 3,400 kg                                     ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n📅 Fecha: " . date('Y-m-d H:i:s') . "\n";
    }

    /**
     * Imprimir resumen
     */
    private function printSummary()
    {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;
        
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  📊 RESUMEN DE PRUEBAS                                                         ║\n";
        echo "╠════════════════════════════════════════════════════════════════════════════════╣\n";
        printf("║  Total: %-3d │ ✅ Pasaron: %-3d │ ❌ Fallaron: %-3d │ Éxito: %6.1f%%            ║\n", 
               $total, $this->passed, $this->failed, $percentage);
        echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
        
        if ($this->failed === 0) {
            echo "\n🎉 ¡TODAS LAS PRUEBAS DE CONTENEDOR 20 PASARON!\n";
            echo "   La tara de 2,300 kg se aplica correctamente a contenedores de 20 pies.\n\n";
        } else {
            echo "\n⚠️  HAY PRUEBAS FALLIDAS - La tara de 2,300 kg NO se está aplicando correctamente.\n";
            echo "   Revisar la función getTaraByContenedor() en MCPAssistantService.php\n\n";
        }

        // Tabla de referencia
        echo "┌──────────────────────────────────────────────────────────────────────────────┐\n";
        echo "│  📋 TABLA DE REFERENCIA - CÁLCULOS DE PESO CON CONTENEDOR 20                │\n";
        echo "├───────────────┬─────────────┬────────────────────────────────────────────────┤\n";
        echo "│ Peso Neto     │ + Tara 2300 │ = Peso Total                                   │\n";
        echo "├───────────────┼─────────────┼────────────────────────────────────────────────┤\n";
        echo "│  5,000 kg     │  + 2,300    │ =  7,300 kg                                    │\n";
        echo "│ 10,000 kg     │  + 2,300    │ = 12,300 kg                                    │\n";
        echo "│ 12,000 kg     │  + 2,300    │ = 14,300 kg                                    │\n";
        echo "│ 14,000 kg     │  + 2,300    │ = 16,300 kg  ← Ejemplo del usuario             │\n";
        echo "│ 15,000 kg     │  + 2,300    │ = 17,300 kg                                    │\n";
        echo "│ 18,000 kg     │  + 2,300    │ = 20,300 kg                                    │\n";
        echo "│ 20,000 kg     │  + 2,300    │ = 22,300 kg                                    │\n";
        echo "└───────────────┴─────────────┴────────────────────────────────────────────────┘\n\n";
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// EJECUTAR PRUEBAS
// ═══════════════════════════════════════════════════════════════════════════════

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Ejecutar pruebas
$test = new TaraContenedor20Test();
$test->run();
