# Servidor MCP de Playwright - Configuración

## 🎭 ¿Qué es Playwright MCP?

El servidor MCP (Model Context Protocol) de Playwright permite a los modelos de IA interactuar con navegadores web de forma automatizada. Esto incluye:

- ✅ Navegar a sitios web
- ✅ Tomar capturas de pantalla
- ✅ Hacer clic en elementos
- ✅ Llenar formularios
- ✅ Ejecutar JavaScript
- ✅ Probar APIs
- ✅ Validar elementos visuales

## 📦 Instalación Completada

El servidor MCP de Playwright ha sido instalado y configurado en tu sistema:

- **Puerto local**: 8931
- **Puerto Nginx**: 8932
- **URL pública**: http://conalcaia.conalca.com.co:8932/playwright/
- **Servicio systemd**: `playwright-mcp.service`

## 🚀 Comandos Útiles

### Ver estado del servicio
```bash
sudo systemctl status playwright-mcp
```

### Ver logs en tiempo real
```bash
sudo journalctl -u playwright-mcp -f
```

### Reiniciar el servicio
```bash
sudo systemctl restart playwright-mcp
```

### Detener el servicio
```bash
sudo systemctl stop playwright-mcp
```

### Iniciar el servicio
```bash
sudo systemctl start playwright-mcp
```

## 🔧 Uso desde tu Aplicación Laravel

### 1. Configurar el cliente MCP

En tu archivo `.env`, agrega:

```env
PLAYWRIGHT_MCP_URL=http://localhost:8931/mcp
PLAYWRIGHT_MCP_ENABLED=true
```

### 2. Crear un servicio PHP para Playwright

Crea `app/Services/PlaywrightService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaywrightService
{
    private static $mcp_url = 'http://localhost:8931/mcp';
    
    /**
     * Navegar a una URL
     */
    public static function navigate($url, $sessionId = null)
    {
        $sessionId = $sessionId ?? 'session_' . uniqid();
        
        $response = Http::timeout(60)->post(self::$mcp_url . '?sessionId=' . $sessionId, [
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => 'playwright_navigate',
                'arguments' => [
                    'url' => $url
                ]
            ]
        ]);
        
        return [
            'sessionId' => $sessionId,
            'result' => $response->json()
        ];
    }
    
    /**
     * Tomar captura de pantalla
     */
    public static function screenshot($sessionId, $fullPage = false)
    {
        $response = Http::timeout(60)->post(self::$mcp_url . '?sessionId=' . $sessionId, [
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => 'playwright_screenshot',
                'arguments' => [
                    'name' => 'screenshot_' . time() . '.png',
                    'fullPage' => $fullPage
                ]
            ]
        ]);
        
        return $response->json();
    }
    
    /**
     * Hacer clic en un elemento
     */
    public static function click($sessionId, $selector)
    {
        $response = Http::timeout(60)->post(self::$mcp_url . '?sessionId=' . $sessionId, [
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => 'playwright_click',
                'arguments' => [
                    'selector' => $selector
                ]
            ]
        ]);
        
        return $response->json();
    }
    
    /**
     * Llenar un campo de formulario
     */
    public static function fill($sessionId, $selector, $value)
    {
        $response = Http::timeout(60)->post(self::$mcp_url . '?sessionId=' . $sessionId, [
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => 'playwright_fill',
                'arguments' => [
                    'selector' => $selector,
                    'value' => $value
                ]
            ]
        ]);
        
        return $response->json();
    }
}
```

### 3. Ejemplo de uso en un controlador

```php
use App\Services\PlaywrightService;

class TestController extends Controller
{
    public function testWebsite()
    {
        // Navegar a la página
        $nav = PlaywrightService::navigate('https://conalcaia.conalca.com.co/cotizacion');
        $sessionId = $nav['sessionId'];
        
        // Llenar un campo
        PlaywrightService::fill($sessionId, 'input[name="nit"]', '900416879');
        
        // Hacer clic en un botón
        PlaywrightService::click($sessionId, 'button[type="submit"]');
        
        // Tomar captura de pantalla
        $screenshot = PlaywrightService::screenshot($sessionId, true);
        
        return response()->json([
            'success' => true,
            'screenshot' => $screenshot
        ]);
    }
}
```

## 🧪 Prueba Manual

Puedes probar el servidor directamente con curl:

```bash
# Navegar a una página
curl -X POST "http://localhost:8931/mcp?sessionId=test123" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "playwright_navigate",
      "arguments": {
        "url": "https://conalcaia.conalca.com.co"
      }
    }
  }'

# Tomar captura de pantalla
curl -X POST "http://localhost:8931/mcp?sessionId=test123" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "playwright_screenshot",
      "arguments": {
        "name": "screenshot.png"
      }
    }
  }'
```

## 📝 Herramientas Disponibles

El servidor MCP de Playwright incluye las siguientes herramientas:

- `playwright_navigate` - Navegar a una URL
- `playwright_screenshot` - Tomar captura de pantalla
- `playwright_click` - Hacer clic en un elemento
- `playwright_fill` - Llenar un campo de formulario
- `playwright_select` - Seleccionar una opción
- `playwright_hover` - Pasar el mouse sobre un elemento
- `playwright_evaluate` - Ejecutar JavaScript
- `playwright_go_back` - Volver atrás en el historial
- `playwright_go_forward` - Ir adelante en el historial
- `playwright_reload` - Recargar la página

## 🔒 Seguridad

- El servidor MCP solo es accesible desde localhost por defecto
- Para acceso externo, usa el proxy de Nginx en el puerto 8932
- Asegúrate de configurar autenticación si expones el servicio públicamente

## 📚 Documentación Oficial

- [Playwright MCP Server](https://executeautomation.github.io/mcp-playwright/)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [Playwright Documentation](https://playwright.dev/)

## 🐛 Troubleshooting

### El servicio no inicia
```bash
sudo journalctl -u playwright-mcp -n 50
```

### Error de permisos
```bash
sudo chown -R ubuntu:ubuntu /home/ubuntu/conalca/conalca
```

### Reinstalar navegadores de Playwright
```bash
cd /home/ubuntu/conalca/conalca
npx playwright install chromium
```
