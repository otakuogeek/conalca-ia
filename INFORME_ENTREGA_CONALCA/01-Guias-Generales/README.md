# 🚀 Sistema Conalca IA - Gestión de Transporte con Inteligencia Artificial

Sistema completo de gestión de transportes con integración de IA, desarrollado para Conalca.

## 📁 Estructura del Proyecto

### `/conalca` - Aplicación Principal Laravel
Sistema web completo de gestión de transporte con:
- ✅ **ChatBot con OpenAI GPT-4**: Asistente inteligente para cotizaciones
- ✅ **Integración ElevenLabs**: Sistema de llamadas automáticas a conductores
- ✅ **Integración Silogtran**: Sincronización con sistema externo de transporte
- ✅ **Wizard de 6 pasos**: Creación guiada de solicitudes de transporte
- ✅ **Auto-llenado inteligente**: Formularios que se completan mediante chat
- ✅ **Gestión de rutas y cotizaciones**: Sistema completo de administración

**Stack Tecnológico:**
- Laravel 10.48.29
- PHP 8.3-FPM
- React/JSX + Vite
- MySQL
- Tailwind CSS + DaisyUI
- Nginx

### `/conalca-mcp` - MCP Server (Model Context Protocol)
Servidor MCP personalizado con herramientas específicas para el sistema.

## 🎯 Funcionalidades Principales

### 1. ChatBot Inteligente
- Auto-completa formularios mediante conversación natural
- Reconoce comandos como "llena todo con ejemplos"
- Normaliza datos automáticamente para Silogtran
- Interfaz conversacional con EventBus

### 2. Sistema de Llamadas Automatizadas
- Integración con ElevenLabs para llamadas por voz
- Registro y seguimiento de conductores
- Sistema de aceptación/rechazo de viajes
- Gestión de llamadas en cola

### 3. Integración Silogtran
- Normalización automática de datos
- Validación de campos según especificaciones
- Manejo de errores con estado "pendiente_sincronizacion"
- Códigos DIVIPOLA automáticos

## 📋 Requisitos

- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL >= 8.0
- Nginx
- Git

## 🚀 Instalación

### 1. Clonar repositorio
\`\`\`bash
git clone <repo-url>
cd conalca
\`\`\`

### 2. Instalar dependencias PHP
\`\`\`bash
composer install
\`\`\`

### 3. Configurar entorno
\`\`\`bash
cp .env.example .env
php artisan key:generate
\`\`\`

### 4. Configurar base de datos en `.env`
\`\`\`env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_transport
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
\`\`\`

### 5. Migrar base de datos
\`\`\`bash
php artisan migrate
\`\`\`

### 6. Instalar dependencias Node.js
\`\`\`bash
npm install
\`\`\`

### 7. Compilar assets
\`\`\`bash
npm run build
\`\`\`

### 8. Configurar permisos
\`\`\`bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
\`\`\`

## 🔧 Configuración de APIs

### OpenAI
Agregar en `.env`:
\`\`\`env
OPENAI_API_KEY=sk-proj-...
\`\`\`

### ElevenLabs
\`\`\`env
ELEVENLABS_API_KEY=sk_...
ELEVENLABS_AGENT_ID=...
\`\`\`

### Silogtran
\`\`\`env
SILOGTRAN_BASE_URL=https://conalca.colombiasoftware.net
SILOGTRAN_USER=...
SILOGTRAN_PASSWORD=...
\`\`\`

## 📚 Documentación

- [Integración Silogtran Exitosa](conalca/INTEGRACION_SILOGTRAN_EXITOSA.md)
- [Prompts de Prueba para Chat](conalca/PROMPTS_PRUEBA_CHAT.md)
- [Guía Completa Herramientas MCP](conalca-mcp/GUIA_COMPLETA_HERRAMIENTAS_MCP.md)
- [Sistema de Llamadas ElevenLabs](conalca/SISTEMA_COLA_LLAMADAS.md)

## 🎯 Datos de Prueba

**Cliente válido en Silogtran:**
- Código: 1846
- Nombre: AALPE LOGISTICA SAS

**Ciudades principales:**
- Bogotá: 11001
- Medellín: 05001
- Cali: 76001

**Centro de costos válido:**
- CONALCA BOGOTA

## 🧪 Testing

### Prompt rápido para ChatBot:
\`\`\`
Llena todo con ejemplos usando cliente AALPE LOGISTICA de Bogotá a Medellín
\`\`\`

### Verificar integración Silogtran:
\`\`\`bash
php test_cliente_1846.php
\`\`\`

## 📝 Comandos Útiles

\`\`\`bash
# Limpiar cachés
php artisan optimize:clear

# Compilar assets en desarrollo
npm run dev

# Compilar assets para producción
npm run build

# Reiniciar servicios
sudo systemctl restart php8.3-fpm nginx

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
\`\`\`

## 🎉 Estado del Proyecto

- ✅ ChatBot funcionando 100%
- ✅ Auto-llenado de formularios operativo
- ✅ Integración Silogtran completada y validada
- ✅ Sistema de llamadas ElevenLabs activo
- ✅ Normalización de datos implementada
- ✅ Wizard de 6 pasos completo

## 📞 Contacto

Sistema desarrollado para **Conalca**
Fecha: Octubre 2025

---

**¡Sistema 100% funcional y listo para producción!** 🚀
