# 🖥️ Comandos de Consola para Gestión de Llamadas con API Arcángel

## 📋 Descripción

Sistema de comandos de consola Laravel para gestionar llamadas de conductores utilizando la API de Arcángel. Permite consultar localidades, registrar llamadas y listar llamadas pendientes directamente desde la terminal.

---

## 📦 Comandos Disponibles

### 1️⃣ Consultar Localidad

Consulta información de localidades por nombre de ciudad.

#### Sintaxis
```bash
php artisan arcangel:consultar-localidad {ciudad} [opciones]
```

#### Parámetros
- `ciudad` (requerido): Nombre de la ciudad a consultar

#### Opciones
- `--formato=table` : Formato de salida (table, json, simple) [default: table]
- `--cache` : Usar caché para la consulta
- `--cache-time=60` : Tiempo de caché en minutos [default: 60]

#### Ejemplos

**Consulta básica:**
```bash
php artisan arcangel:consultar-localidad "Bogotá"
```

**Con caché:**
```bash
php artisan arcangel:consultar-localidad "Medellín" --cache
```

**Formato JSON:**
```bash
php artisan arcangel:consultar-localidad "Cali" --formato=json
```

**Formato simple:**
```bash
php artisan arcangel:consultar-localidad "Barranquilla" --formato=simple
```

**Con caché personalizado:**
```bash
php artisan arcangel:consultar-localidad "Cartagena" --cache --cache-time=120
```

#### Salida Esperada (formato table)
```
🔍 Consultando localidad para: Bogotá
⏳ Procesando...

📍 Resultados para: Bogotá

┌────┬─────────┬────────┬──────────────┬──────────┐
│ ID │ Nombre  │ Código │ Departamento │ País     │
├────┼─────────┼────────┼──────────────┼──────────┤
│ 1  │ Bogotá  │ 11001  │ Cundinamarca │ Colombia │
└────┴─────────┴────────┴──────────────┴──────────┘

Total: 1 localidad(es) encontrada(s)

✅ Consulta completada exitosamente
```

---

### 2️⃣ Registrar Llamada de Conductor

Registra una nueva llamada de conductor en el sistema, consultando automáticamente las localidades de origen y destino.

#### Sintaxis
```bash
php artisan arcangel:registrar-llamada {origen} {destino} {peso} {vehiculo} [opciones]
```

#### Parámetros
- `origen` (requerido): Ciudad de origen
- `destino` (requerido): Ciudad de destino
- `peso` (requerido): Peso de la mercancía en kg
- `vehiculo` (requerido): Tipo de vehículo requerido (Sencillo, Turbo, Tractomula, etc.)

#### Opciones
- `--valor-declarado=` : Valor declarado de la mercancía
- `--cantidad=1` : Cantidad de unidades [default: 1]
- `--tipo-embalaje=Caja` : Tipo de embalaje [default: Caja]
- `--json` : Mostrar respuesta en formato JSON

#### Ejemplos

**Registro básico:**
```bash
php artisan arcangel:registrar-llamada "Bogotá" "Cali" 2000 "Sencillo"
```

**Con valor declarado:**
```bash
php artisan arcangel:registrar-llamada "Medellín" "Barranquilla" 5000 "Turbo" --valor-declarado=10000000
```

**Con cantidad y embalaje:**
```bash
php artisan arcangel:registrar-llamada "Cali" "Bucaramanga" 1500 "Sencillo" --cantidad=5 --tipo-embalaje="Estibas"
```

**Respuesta en JSON:**
```bash
php artisan arcangel:registrar-llamada "Bogotá" "Medellín" 3000 "Tractomula" --json
```

#### Salida Esperada
```
🚛 Registrando nueva llamada de conductor

📍 Origen: Bogotá
📍 Destino: Cali
⚖️  Peso: 2000 kg
🚗 Vehículo: Sencillo
💰 Valor declarado: $10000000
📦 Cantidad: 1
📦 Embalaje: Caja

¿Desea continuar con el registro? (yes/no) [yes]:
> yes

⏳ Procesando registro...
1️⃣  Consultando localidad de origen...
   ✓ Localidad origen encontrada: Bogotá (Código: 11001)
2️⃣  Consultando localidad de destino...
   ✓ Localidad destino encontrada: Cali (Código: 76001)
3️⃣  Registrando llamada en el sistema...

✅ Llamada registrada exitosamente

📋 Resumen del registro:

┌──────────────────────┬────────────────────┐
│ Campo                │ Valor              │
├──────────────────────┼────────────────────┤
│ Id                   │ 123                │
│ Ciudad origen        │ Bogotá             │
│ Ciudad destino       │ Cali               │
│ Peso mercancia       │ 2000               │
│ Vehiculo requerido   │ Sencillo           │
│ Valor declarado      │ 10000000           │
│ Estado               │ pendiente          │
│ Fecha registro       │ 2025-11-16 14:30:00│
└──────────────────────┴────────────────────┘
```

---

### 3️⃣ Listar Llamadas

Lista las llamadas de conductores según el estado especificado.

#### Sintaxis
```bash
php artisan arcangel:listar-llamadas [opciones]
```

#### Opciones
- `--estado=pendiente` : Estado de las llamadas (pendiente, aceptada, rechazada, todas) [default: pendiente]
- `--limite=10` : Número máximo de resultados [default: 10]
- `--formato=table` : Formato de salida (table, json, simple) [default: table]
- `--sin-cache` : No usar caché

#### Ejemplos

**Listar pendientes (por defecto):**
```bash
php artisan arcangel:listar-llamadas
```

**Listar aceptadas:**
```bash
php artisan arcangel:listar-llamadas --estado=aceptada
```

**Listar todas con límite:**
```bash
php artisan arcangel:listar-llamadas --estado=todas --limite=20
```

**Formato simple:**
```bash
php artisan arcangel:listar-llamadas --formato=simple
```

**Sin caché:**
```bash
php artisan arcangel:listar-llamadas --sin-cache
```

**Formato JSON:**
```bash
php artisan arcangel:listar-llamadas --estado=todas --formato=json
```

#### Salida Esperada (formato table)
```
📋 Listando llamadas de conductores
Estado: PENDIENTE
Límite: 10

⏳ Consultando llamadas...

┌────┬─────────┬──────────────┬───────────┬────────────┬──────────────┬─────────────────┐
│ ID │ Origen  │ Destino      │ Peso (kg) │ Vehículo   │ Estado       │ Fecha           │
├────┼─────────┼──────────────┼───────────┼────────────┼──────────────┼─────────────────┤
│ 1  │ Bogotá  │ Cali         │ 2000      │ Sencillo   │ 🟡 pendiente │ 16/11/2025 14:30│
│ 2  │ Medellín│ Barranquilla │ 5000      │ Turbo      │ 🟡 pendiente │ 16/11/2025 15:00│
│ 3  │ Cali    │ Bucaramanga  │ 1500      │ Sencillo   │ 🟡 pendiente │ 16/11/2025 15:30│
└────┴─────────┴──────────────┴───────────┴────────────┴──────────────┴─────────────────┘

Total: 3 llamada(s) encontrada(s)

✅ Consulta completada
```

#### Salida Esperada (formato simple)
```
1. [1] Bogotá → Cali (pendiente)
2. [2] Medellín → Barranquilla (pendiente)
3. [3] Cali → Bucaramanga (pendiente)

Total: 3 llamada(s)
```

---

## 🎨 Iconos de Estado

Los estados de las llamadas se muestran con iconos de colores:

- 🟡 **pendiente**: Llamada registrada, esperando aceptación
- 🟢 **aceptada**: Llamada aceptada por un conductor
- 🔴 **rechazada**: Llamada rechazada
- 🔵 **en_proceso**: Llamada en proceso de ejecución
- ⚪ **otros**: Otros estados

---

## 🔧 Configuración

Los comandos utilizan el servicio `ArcangelService` que se configura en:

### Archivo: `config/arcangel.php`

```php
return [
    'mode' => env('ARCANGEL_MODE', 'production'),
    'base_url' => env('ARCANGEL_BASE_URL', 'https://arcangel.conalca.com.co/api/'),
    'api_key' => env('ARCANGEL_API_KEY'),
    'timeout' => env('ARCANGEL_TIMEOUT', 30),
];
```

### Variables de entorno (.env)

```env
ARCANGEL_MODE=production
ARCANGEL_BASE_URL=https://arcangel.conalca.com.co/api/
ARCANGEL_API_KEY=tu_api_key_aqui
ARCANGEL_TIMEOUT=30
```

---

## 📝 Logs

Todos los comandos registran eventos importantes en el log de Laravel:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Buscar logs específicos de Arcángel
grep "arcangel:" storage/logs/laravel.log
```

---

## ⚠️ Manejo de Errores

Los comandos manejan diferentes tipos de errores:

### Error de conexión
```
❌ Error al consultar la API de Arcángel
Mensaje: cURL error 28: Operation timed out
```

### Localidad no encontrada
```
❌ No se encontró la localidad de origen: Ciudad Inexistente
```

### API no disponible
```
❌ Error al registrar la llamada
Mensaje: API de Arcángel no disponible
```

---

## 🚀 Integración con Scripts

Los comandos pueden ser integrados en scripts de bash:

```bash
#!/bin/bash

# Script para registrar múltiples llamadas
ciudades_origen=("Bogotá" "Medellín" "Cali")
ciudades_destino=("Barranquilla" "Cartagena" "Bucaramanga")

for i in {0..2}; do
    php artisan arcangel:registrar-llamada \
        "${ciudades_origen[$i]}" \
        "${ciudades_destino[$i]}" \
        "2000" \
        "Sencillo" \
        --valor-declarado=5000000
done
```

---

## 📊 Tareas Programadas

Puedes programar los comandos en `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Listar llamadas pendientes cada hora
    $schedule->command('arcangel:listar-llamadas --estado=pendiente')
             ->hourly()
             ->appendOutputTo(storage_path('logs/llamadas-pendientes.log'));
}
```

---

## 🔍 Troubleshooting

### Comando no encontrado
```bash
# Limpiar caché de comandos
php artisan cache:clear
php artisan config:clear

# Verificar que el comando esté registrado
php artisan list | grep arcangel
```

### Errores de permisos
```bash
# Dar permisos a storage y bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 📚 Documentación Adicional

- [Documentación API Arcángel](DOCUMENTACION_ARCANGEL_API.md)
- [Servicio ArcangelService](app/Services/ArcangelService.php)
- [Configuración Arcángel](config/arcangel.php)
