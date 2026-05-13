# 🗂️ ÍNDICE DE MÓDULOS DEL SISTEMA CONALCA

Esta carpeta contiene un informe individual y detallado para cada módulo del menú lateral del sistema. Cada README explica:

- **¿Para qué es?** — propósito del módulo
- **¿Qué hace?** — funcionalidades
- **¿Cómo funciona?** — flujos paso a paso
- **¿Cómo actúa en el sistema?** — integraciones con otros módulos
- **Componentes del código** — rutas, controladores, modelos, vistas
- **Reglas de negocio**
- **Permisos por rol**
- **Mantenimiento y operación**
- **Archivos clave**
- **Documentación adicional relacionada**

---

## 📋 ÍNDICE COMPLETO

### Módulos principales del menú

| # | Carpeta | Módulo | Categoría |
|---|---------|--------|-----------|
| 01 | [01-Dashboard/](01-Dashboard/) | 📊 Dashboard | Vista principal |
| 02 | [02-Clientes/](02-Clientes/) | 👥 Clientes | CRM |
| 03 | [03-Documentos/](03-Documentos/) | 📄 Documentos | Repositorio |
| 04 | [04-Calendario/](04-Calendario/) | 📅 Calendario | Productividad |
| 05 | [05-Buzon/](05-Buzon/) | 📧 Buzón | Comunicación |

### Análisis (menú desplegable)

| # | Carpeta | Submódulo | Función |
|---|---------|-----------|---------|
| 06 | [06-Analisis-Rutas-Transporte/](06-Analisis-Rutas-Transporte/) | 🗺️ Rutas de Transporte | Mapa + analytics geo |
| 07 | [07-Analisis-Llamadas-ElevenLabs/](07-Analisis-Llamadas-ElevenLabs/) | 📞 Llamadas ElevenLabs | KPIs comerciales |
| 08 | [08-Analisis-Auditoria-Llamadas/](08-Analisis-Auditoria-Llamadas/) | 🔍 Auditoría Llamadas | Auditoría operativa |

### Pricing

| # | Carpeta | Módulo |
|---|---------|--------|
| 09 | [09-Pricing/](09-Pricing/) | 💰 Pricing — motor de tarifas |

### Gestión (menú desplegable)

| # | Carpeta | Submódulo |
|---|---------|-----------|
| 10 | [10-Gestion-Metas/](10-Gestion-Metas/) | 🎯 Metas comerciales |
| 11 | [11-Gestion-Novedades-Alertas/](11-Gestion-Novedades-Alertas/) | 🔔 Novedades & Alertas |
| 12 | [12-Gestion-Conductores/](12-Gestion-Conductores/) | 🚛 Conductores |
| 13 | [13-Gestion-Panel-Porcentajes/](13-Gestion-Panel-Porcentajes/) | % Panel de Porcentajes |
| 14 | [14-Gestion-Tara/](14-Gestion-Tara/) | 📦 Tara |
| 15 | [15-Gestion-Vehiculos/](15-Gestion-Vehiculos/) | 🚚 Vehículos |
| 16 | [16-Gestion-Esquema-Seguridad/](16-Gestion-Esquema-Seguridad/) | 🛡️ Esquema de Seguridad ⭐ |

### Administración

| # | Carpeta | Módulo |
|---|---------|--------|
| 17 | [17-Control-Usuarios/](17-Control-Usuarios/) | 👨‍💼 Control de Usuarios |

---

## 🎯 ORDEN RECOMENDADO DE LECTURA

### Para entender el negocio
1. **01-Dashboard** — vista general
2. **02-Clientes** — quiénes son nuestros clientes
3. **09-Pricing** — cómo cobramos
4. **12-Gestion-Conductores** — quiénes operan
5. **07-Analisis-Llamadas-ElevenLabs** — cómo medimos

### Para entender el flujo operativo
1. **02-Clientes** — entrada del cliente
2. **09-Pricing** — propuesta de precio
3. **12-Gestion-Conductores** — quién hace el viaje
4. **08-Analisis-Auditoria-Llamadas** — control operativo

### Para administrar el sistema
1. **17-Control-Usuarios** — gestión de accesos
2. **13-Gestion-Panel-Porcentajes** — configuración comercial
3. **14-Gestion-Tara** — configuración técnica
4. **15-Gestion-Vehiculos** — mapeo Arcángel
5. **16-Gestion-Esquema-Seguridad** — reglas de seguridad ⭐

### Para auditar / SAC
1. **08-Analisis-Auditoria-Llamadas** — auditoría detallada
2. **12-Gestion-Conductores** — gestión de bloqueos
3. **03-Documentos** — documentos del cliente

---

## 🔗 MÓDULOS POR ROL

### SUPER ADMIN
Ve **TODOS** los módulos. Es responsable de:
- Configurar porcentajes, tara, esquemas de seguridad
- Crear usuarios y asignar roles
- Mapear vehículos Arcángel
- Auditar el sistema completo

### JEFE COMERCIAL
- Dashboard, Clientes, Documentos, Calendario, Buzón
- Todos los Análisis
- Pricing
- Metas, Novedades, Panel de Porcentajes, Tara, Esquema de Seguridad
- Control de Usuarios (sólo su línea)

### GERENTE DE CUENTA
- Dashboard, Clientes, Documentos, Calendario, Buzón
- Análisis › Rutas y Llamadas (no Auditoría)
- Metas, Novedades

### ASISTENTE COMERCIAL
- Dashboard, Clientes, Documentos, Calendario, Buzón
- Novedades (propias)

### SAC (Servicio al Cliente)
- Dashboard, Clientes, Documentos, Calendario, Buzón
- Análisis › Llamadas y Auditoría
- Novedades
- Conductores, Tara, Vehículos

### PRICING
- Dashboard, Buzón
- Pricing (acceso completo)

---

## 🔄 FLUJOS QUE CRUZAN VARIOS MÓDULOS

### Flujo 1: Crear cotización con chat IA
Módulos involucrados:
- 02-Clientes (buscar/crear cliente)
- 14-Gestion-Tara (calcular tara si contenedor)
- 13-Gestion-Panel-Porcentajes (aplicar %)
- 16-Gestion-Esquema-Seguridad (activar medidas)
- 09-Pricing (precio base)

### Flujo 2: Llamar conductores
Módulos involucrados:
- 12-Gestion-Conductores (búsqueda + filtros)
- 15-Gestion-Vehiculos (mapeo Arcángel)
- 07/08-Análisis (analytics y auditoría)

### Flujo 3: Solicitud de transporte completa
Módulos involucrados:
- 02-Clientes (cliente final)
- 09-Pricing (precio acordado)
- 16-Gestion-Esquema-Seguridad (medidas obligatorias)
- 03-Documentos (documentos del cliente para Silogtran)
- 05-Buzon (notificación de pasos completados)

### Flujo 4: Cierre de mes comercial
Módulos involucrados:
- 10-Gestion-Metas (evaluación)
- 01-Dashboard (KPIs)
- 11-Gestion-Novedades-Alertas (notificaciones jefe)
- 07-Analisis-Llamadas-ElevenLabs (efectividad)

---

## 📁 OTROS MÓDULOS NO VISIBLES EN MENÚ

Existen funcionalidades que no aparecen en el sidebar pero son parte del sistema:

### Cotizaciones (acceso desde Clientes y desde "Nueva Cotización")
- Rutas: `/quotes`, `/cotizacion`, `/cotizacion-react`
- Ver: `04-Cotizaciones-IA/` en la entrega

### Solicitud de Transporte (acceso desde una cotización aceptada)
- Rutas: `/solicitud/store`, `/api/solicitud-transporte/*`
- Ver: `06-Solicitud-Transporte/` en la entrega

### Servidor MCP (backend del agente de voz)
- Python FastAPI separado
- Ver: `07-MCP-Server/` en la entrega

### APIs externas
- OpenAI, Groq, ElevenLabs, Arcángel, Silogtran, Twilio, BulkGate
- Ver sección 11 del informe principal

---

## 📚 REFERENCIA AL INFORME PRINCIPAL

Para una visión arquitectónica completa (modelos, servicios, jobs, infraestructura), consulta:
- [`../00-INFORME_PRINCIPAL.md`](../00-INFORME_PRINCIPAL.md)

Y los documentos categorizados:
- `../01-Guias-Generales/`
- `../02-Integraciones-Externas/`
- `../03-Sistema-Llamadas-IA/`
- `../04-Cotizaciones-IA/`
- `../05-Conductores-Vehiculos/`
- `../06-Solicitud-Transporte/`
- `../07-MCP-Server/`
- `../08-Correcciones-Fixes/`
- `../09-Manuales-API/`
- `../10-Prompts-Asistente/`

---

**Generado el 2026-05-13 como parte del handover técnico del proyecto CONALCA.**
**Total de módulos documentados:** 17 (cada uno con README individual)
