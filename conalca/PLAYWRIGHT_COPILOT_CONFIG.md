# Configuración de Playwright MCP para GitHub Copilot

## ✅ Estado del Servidor

El servidor MCP de Playwright está **INSTALADO y FUNCIONANDO**:

- 🟢 **Estado**: Activo (v1.0.11)
- 🔌 **Puerto local**: 8931
- 🌐 **Puerto Nginx**: 8932
- 📊 **Health check**: http://localhost:8931/health
- 🎯 **Endpoint MCP**: http://localhost:8931/mcp

### Verificar Estado

```bash
# Ver estado del servicio
sudo systemctl status playwright-mcp

# Verificar con curl
curl http://localhost:8931/health

# O usar el comando artisan
php artisan test:playwright health
```

## 🔧 Configuración en GitHub Copilot (VS Code)

### Paso 1: Abrir Configuración de VS Code

Hay dos formas de configurarlo:

**Opción A: Configuración del Workspace** (recomendado para este proyecto)
```bash
# Crear o editar .vscode/settings.json
```

**Opción B: Configuración Global del Usuario**
```
Ctrl+Shift+P (Cmd+Shift+P en Mac) → "Preferences: Open User Settings (JSON)"
```

### Paso 2: Agregar Configuración MCP

Agrega esta configuración en el archivo `settings.json`:

```json
{
  "github.copilot.chat.mcp.servers": {
    "playwright": {
      "url": "http://localhost:8931/mcp",
      "type": "http"
    }
  }
}
```

### Paso 3: Reiniciar VS Code

```bash
# Cerrar y volver a abrir VS Code
# O usar Ctrl+Shift+P → "Developer: Reload Window"
```

### Paso 4: Verificar que Funciona

Abre el chat de GitHub Copilot y pregunta:

```
@playwright navega a https://google.com y toma una captura de pantalla
```

## 🛠️ Herramientas Disponibles

Una vez configurado, podrás usar estas herramientas en Copilot:

| Herramienta | Descripción | Ejemplo |
|-------------|-------------|---------|
| `playwright_navigate` | Navegar a una URL | "Navega a conalcaia.conalca.com.co" |
| `playwright_screenshot` | Captura de pantalla | "Toma una captura de la página" |
| `playwright_click` | Hacer clic en elemento | "Haz clic en el botón 'Crear Cotización'" |
| `playwright_fill` | Llenar formulario | "Llena el campo origen con 'Bogotá'" |
| `playwright_evaluate` | Ejecutar JavaScript | "Evalúa: document.title" |

## 📋 Ejemplo de Uso en Copilot Chat

```
Usuario: @playwright navega a https://conalcaia.conalca.com.co/cotizacion 
         y verifica que el campo "Producto" esté visible

Copilot: [usa playwright_navigate]
         [usa playwright_evaluate para verificar el campo]
         [reporta el resultado]
```

## 🔍 Casos de Uso para CONALCA

### 1. Verificar Formulario de Cotización

```
@playwright navega a conalcaia.conalca.com.co/cotizacion 
y verifica que todos los campos obligatorios estén marcados con asterisco rojo
```

### 2. Probar Flujo Completo

```
@playwright simula el flujo completo de crear una cotización:
1. Navega a /cotizacion
2. Escribe en el chat: "necesito enviar 100kg de café de Bogotá a Medellín"
3. Espera respuesta del bot
4. Verifica que el campo producto se llene automáticamente
5. Toma captura final
```

### 3. Debugging Visual

```
@playwright abre la página de cotizaciones, 
evalúa el contenido de $this->quote_data en Livewire,
y toma una captura mostrando el panel lateral
```

## ⚙️ Configuración Avanzada

### Configuración Completa con Timeout

```json
{
  "github.copilot.chat.mcp.servers": {
    "playwright": {
      "url": "http://localhost:8931/mcp",
      "type": "http",
      "timeout": 60000,
      "description": "Playwright automation for testing CONALCA web interface"
    }
  }
}
```

### Múltiples Servidores MCP

Si tienes otros servidores MCP instalados:

```json
{
  "github.copilot.chat.mcp.servers": {
    "playwright": {
      "url": "http://localhost:8931/mcp",
      "type": "http"
    },
    "otros-servidor": {
      "command": "npx",
      "args": ["-y", "@otro/mcp-server"]
    }
  }
}
```

## 🐛 Troubleshooting

### Error: "Cannot connect to MCP server"

**Solución:**
```bash
# 1. Verificar que el servicio esté corriendo
sudo systemctl status playwright-mcp

# 2. Si no está activo, iniciarlo
sudo systemctl start playwright-mcp

# 3. Verificar logs
sudo journalctl -u playwright-mcp -n 50
```

### Error: "No transport found for sessionId"

Esto es normal si intentas usar curl directamente. MCP requiere que el cliente (VS Code) maneje la sesión SSE automáticamente.

**Solución:** Usa el servicio desde GitHub Copilot, no desde curl/HTTP directo.

### VS Code no reconoce @playwright

**Solución:**
```bash
# 1. Verifica la configuración
cat .vscode/settings.json

# 2. Asegúrate de tener la configuración correcta
# 3. Reinicia VS Code completamente
# 4. Espera unos segundos a que Copilot se inicialice
```

## 📊 Monitoreo

### Ver sesiones activas

```bash
# Método 1: Health endpoint
curl http://localhost:8931/health | jq

# Método 2: Logs del servicio
sudo journalctl -u playwright-mcp -f

# Método 3: Comando artisan
php artisan test:playwright health
```

### Métricas del sistema

El servidor MCP también expone métricas en un puerto dinámico. Consulta los logs para ver el puerto:

```bash
sudo journalctl -u playwright-mcp | grep "Monitoring HTTP server"
# Output: Monitoring HTTP server listening on port 37605

curl http://localhost:37605/metrics
curl http://localhost:37605/health
```

## 🔒 Seguridad

El servidor está configurado para:

- ✅ Escuchar solo en `localhost` (127.0.0.1)
- ✅ No expuesto a redes externas
- ✅ Accesible solo desde la máquina local
- ✅ Ideal para desarrollo y testing

### Para acceso remoto (si es necesario)

Usa SSH tunneling en lugar de exponer el puerto:

```bash
# Desde tu máquina local
ssh -L 8931:localhost:8931 ubuntu@tu-servidor.com

# Ahora puedes conectar a localhost:8931 de forma segura
```

## 📚 Referencias

- Documentación oficial: https://executeautomation.github.io/mcp-playwright/
- Repo GitHub: https://github.com/executeautomation/mcp-playwright
- Model Context Protocol: https://modelcontextprotocol.io/

## ✨ Próximos Pasos

1. **Configurar en VS Code** siguiendo los pasos arriba
2. **Probar** con comandos simples en Copilot Chat
3. **Automatizar** tests de la interfaz de cotizaciones
4. **Crear** scripts de prueba para flujos críticos

---

**¿Necesitas ayuda?** Ejecuta:
```bash
php artisan test:playwright health
```
