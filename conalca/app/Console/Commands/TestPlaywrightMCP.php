<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestPlaywrightMCP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:playwright {action=navigate}
                            {--url=https://conalcaia.conalca.com.co/cotizacion : URL a navegar}
                            {--sessionId= : ID de sesión (se genera automáticamente si no se proporciona)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el servidor MCP de Playwright';

    private string $mcpUrl = 'http://localhost:8931/mcp';
    private int $requestId = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $url = $this->option('url');
        $sessionId = $this->option('sessionId') ?? 'session_' . time();

        $this->info("🎭 Playwright MCP Test");
        $this->info("======================");
        $this->newLine();
        
        switch ($action) {
            case 'health':
                $this->testHealth();
                break;
            case 'list-tools':
                $this->listTools($sessionId);
                break;
            case 'navigate':
                $this->navigate($url, $sessionId);
                break;
            case 'full-test':
                $this->fullTest($sessionId);
                break;
            default:
                $this->error("Acción desconocida: {$action}");
                $this->info("Acciones disponibles: health, list-tools, navigate, full-test");
                return 1;
        }

        return 0;
    }

    private function testHealth()
    {
        $this->info("Verificando estado del servicio...");
        
        try {
            $response = Http::get('http://localhost:8931/health');
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Servicio activo");
                $this->table(
                    ['Propiedad', 'Valor'],
                    [
                        ['Estado', $data['status'] ?? 'N/A'],
                        ['Versión', $data['version'] ?? 'N/A'],
                        ['Sesiones activas', $data['activeSessions'] ?? 0],
                    ]
                );
                
                $this->newLine();
                $this->info("🔍 Herramientas MCP disponibles:");
                $this->line("   • playwright_navigate - Navegar a URLs");
                $this->line("   • playwright_screenshot - Tomar capturas");
                $this->line("   • playwright_click - Hacer clic en elementos");
                $this->line("   • playwright_fill - Llenar formularios");
                $this->line("   • playwright_evaluate - Ejecutar JavaScript");
                
                $this->newLine();
                $this->warn("ℹ️  NOTA: MCP usa Server-Sent Events (SSE)");
                $this->line("   Para usar este servicio, necesitas un cliente MCP como:");
                $this->line("   - VS Code GitHub Copilot");
                $this->line("   - Claude Desktop");
                $this->line("   - MCP SDK oficial");
                
                $this->newLine();
                $this->comment("📝 Configuración para GitHub Copilot:");
                $this->line(json_encode([
                    'github.copilot.chat.mcp.servers' => [
                        'playwright' => [
                            'url' => 'http://localhost:8931/mcp',
                            'type' => 'http'
                        ]
                    ]
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
            } else {
                $this->error("❌ Servicio no responde correctamente");
                $this->line("Status: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error al conectar: " . $e->getMessage());
            $this->newLine();
            $this->warn("Solución:");
            $this->line("  1. Verifica que el servicio esté corriendo:");
            $this->line("     sudo systemctl status playwright-mcp");
            $this->newLine();
            $this->line("  2. Si no está corriendo, inícialo:");
            $this->line("     sudo systemctl start playwright-mcp");
            $this->newLine();
            $this->line("  3. Revisa los logs para más detalles:");
            $this->line("     sudo journalctl -u playwright-mcp -n 50");
        }
    }

    private function listTools(string $sessionId)
    {
        $this->info("Listando herramientas disponibles...");
        $this->info("Session ID: {$sessionId}");
        $this->newLine();
        
        $result = $this->mcpCall('tools/list', [], $sessionId);
        
        if ($result['success']) {
            $this->info("✅ Herramientas disponibles:");
            $tools = $result['result']['tools'] ?? [];
            
            foreach ($tools as $tool) {
                $this->line("  • {$tool['name']}: {$tool['description']}");
            }
        } else {
            $this->error("❌ Error: " . json_encode($result['error']));
        }
    }

    private function navigate(string $url, string $sessionId)
    {
        $this->info("Navegando a: {$url}");
        $this->info("Session ID: {$sessionId}");
        $this->newLine();
        
        $result = $this->mcpCall('tools/call', [
            'name' => 'playwright_navigate',
            'arguments' => ['url' => $url]
        ], $sessionId);
        
        if ($result['success']) {
            $this->info("✅ Navegación exitosa");
            $this->line(json_encode($result['result'], JSON_PRETTY_PRINT));
            
            // Guardar sessionId para uso posterior
            $this->info("\nPara continuar con esta sesión, usa: --sessionId={$sessionId}");
        } else {
            $this->error("❌ Error: " . json_encode($result['error']));
        }
    }

    private function fullTest(string $sessionId)
    {
        $this->info("Ejecutando prueba completa...");
        $this->info("Session ID: {$sessionId}");
        $this->newLine();

        // 1. Navegar a cotización
        $this->comment("1️⃣ Navegando a la página de cotizaciones...");
        $result1 = $this->mcpCall('tools/call', [
            'name' => 'playwright_navigate',
            'arguments' => ['url' => 'https://conalcaia.conalca.com.co/cotizacion']
        ], $sessionId);
        
        if (!$result1['success']) {
            $this->error("❌ Error en navegación: " . json_encode($result1['error']));
            return;
        }
        $this->info("✅ Página cargada");
        
        // Esperar 2 segundos
        $this->comment("⏳ Esperando 2 segundos...");
        sleep(2);
        
        // 2. Tomar captura
        $this->comment("2️⃣ Tomando captura de pantalla...");
        $result2 = $this->mcpCall('tools/call', [
            'name' => 'playwright_screenshot',
            'arguments' => [
                'name' => 'cotizacion_' . time() . '.png',
                'fullPage' => true
            ]
        ], $sessionId);
        
        if (!$result2['success']) {
            $this->error("❌ Error en captura: " . json_encode($result2['error']));
            return;
        }
        $this->info("✅ Captura guardada");
        
        // 3. Evaluar página
        $this->comment("3️⃣ Evaluando contenido de la página...");
        $result3 = $this->mcpCall('tools/call', [
            'name' => 'playwright_evaluate',
            'arguments' => [
                'script' => '({ url: window.location.href, title: document.title, ready: document.readyState })'
            ]
        ], $sessionId);
        
        if ($result3['success']) {
            $this->info("✅ Información de la página:");
            $this->line(json_encode($result3['result'], JSON_PRETTY_PRINT));
        }
        
        $this->newLine();
        $this->info("🎉 Prueba completa finalizada!");
        $this->info("Session ID: {$sessionId}");
    }

    private function mcpCall(string $method, array $params, string $sessionId): array
    {
        $this->requestId++;
        
        try {
            $payload = [
                'jsonrpc' => '2.0',
                'id' => $this->requestId,
                'method' => $method,
                'params' => $params,
            ];
            
            Log::info("MCP Request: {$method}", [
                'sessionId' => $sessionId,
                'params' => $params
            ]);
            
            $response = Http::timeout(60)->post("{$this->mcpUrl}?sessionId={$sessionId}", $payload);
            
            $data = $response->json();
            
            Log::info("MCP Response", [
                'method' => $method,
                'response' => $data
            ]);
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error']
                ];
            }
            
            return [
                'success' => true,
                'result' => $data['result'] ?? null,
                'sessionId' => $sessionId,
            ];
        } catch (\Exception $e) {
            Log::error('Playwright MCP call error', [
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
