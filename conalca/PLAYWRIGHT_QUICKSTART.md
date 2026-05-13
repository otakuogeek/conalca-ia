# ⚡ Inicio Rápido - Playwright MCP

## ✅ El servidor ya está configurado y corriendo

```bash
# Verificar estado
php artisan test:playwright health
```

## 🚀 Usar en GitHub Copilot (3 pasos)

### 1. Reiniciar VS Code

```
Ctrl+Shift+P (o Cmd+Shift+P en Mac)
→ "Developer: Reload Window"
```

### 2. Abrir Copilot Chat

```
Ctrl+I (o Cmd+I en Mac)
```

### 3. Probar

```
@playwright navega a https://google.com
```

## 📝 Ejemplos para CONALCA

### Verificar campo producto

```
@playwright navega a https://conalcaia.conalca.com.co/cotizacion 
y verifica que el campo "Tipo producto" tenga un asterisco rojo
```

### Probar el chat

```
@playwright en la página de cotizaciones, 
escribe en el chat: "necesito enviar 100kg de café de Bogotá a Medellín"
luego espera 3 segundos y toma una captura de pantalla
```

### Ver el estado de la página

```
@playwright evalúa: document.title
```

## 🔍 Verificación Rápida

```bash
# Estado del servicio
sudo systemctl status playwright-mcp

# Health check
curl http://localhost:8931/health

# Con artisan
php artisan test:playwright health
```

## 📚 Documentación Completa

- [PLAYWRIGHT_RESUMEN_FINAL.md](PLAYWRIGHT_RESUMEN_FINAL.md) - Todo en un solo lugar
- [PLAYWRIGHT_COPILOT_CONFIG.md](PLAYWRIGHT_COPILOT_CONFIG.md) - Configuración detallada
- [PLAYWRIGHT_MCP_SETUP.md](PLAYWRIGHT_MCP_SETUP.md) - Detalles técnicos

## 🆘 ¿Problemas?

```bash
# Reiniciar el servicio
sudo systemctl restart playwright-mcp

# Ver logs
sudo journalctl -u playwright-mcp -f

# Verificar configuración VS Code
cat .vscode/settings.json
```

---

**TL;DR**: El servidor está listo. Solo recarga VS Code y prueba `@playwright navega a https://google.com` en Copilot Chat.
