#!/bin/bash
#
# Script de Auditoría de Assets - Laravel Conalca
# Verifica que todos los assets, vendors y servicios estén correctamente configurados
#

set -e

CONALCA_PATH="/home/ubuntu/mcp/conalca"
PUBLIC_PATH="$CONALCA_PATH/public"

echo "╔═══════════════════════════════════════════════════════════════════╗"
echo "║          🔍 AUDITORÍA DE ASSETS - LARAVEL CONALCA 🔍            ║"
echo "╚═══════════════════════════════════════════════════════════════════╝"
echo ""
echo "📅 Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# ============================================================================
# 1. VERIFICAR VENDORS
# ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  VENDORS INSTALADOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

REQUIRED_VENDORS=(
    "jquery"
    "sortablejs"
    "leaflet"
    "leaflet-routing-machine"
    "livewire"
    "dropzone"
    "flatpickr"
    "fontawesome-free"
)

vendor_ok=true
for vendor in "${REQUIRED_VENDORS[@]}"; do
    if [ -d "$PUBLIC_PATH/vendor/$vendor" ]; then
        file_count=$(find "$PUBLIC_PATH/vendor/$vendor" -type f 2>/dev/null | wc -l)
        size=$(du -sh "$PUBLIC_PATH/vendor/$vendor" 2>/dev/null | awk '{print $1}')
        echo "   ✅ $vendor ($file_count archivos, $size)"
    else
        echo "   ❌ $vendor - FALTA"
        vendor_ok=false
    fi
done

# ============================================================================
# 2. VERIFICAR ARCHIVOS CRÍTICOS
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  ARCHIVOS CRÍTICOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

CRITICAL_FILES=(
    "vendor/jquery/jquery.min.js"
    "vendor/sortablejs/Sortable.min.js"
    "vendor/sortablejs/jquery-sortable.js"
    "vendor/leaflet/leaflet.min.css"
    "vendor/leaflet/leaflet.min.js"
    "vendor/leaflet-routing-machine/leaflet-routing-machine.css"
    "vendor/leaflet-routing-machine/leaflet-routing-machine.min.js"
    "vendor/dropzone/dropzone.css"
    "vendor/flatpickr/flatpickr.min.css"
    "vendor/flatpickr/flatpickr.min.js"
    "vendor/fontawesome-free/css/all.min.css"
    "vendor/livewire/livewire.min.js"
    "build/manifest.json"
)

files_ok=true
for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$PUBLIC_PATH/$file" ] || [ -L "$PUBLIC_PATH/$file" ]; then
        size=$(du -h "$PUBLIC_PATH/$file" 2>/dev/null | awk '{print $1}')
        echo "   ✅ $file ($size)"
    else
        echo "   ❌ $file - FALTA"
        files_ok=false
    fi
done

# ============================================================================
# 3. VERIFICAR PERMISOS
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  PERMISOS DEL SISTEMA"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

CRITICAL_PATHS=(
    "/home/ubuntu:755"
    "/home/ubuntu/mcp:755"
    "$CONALCA_PATH:755"
    "$PUBLIC_PATH:755"
    "$PUBLIC_PATH/vendor:755"
    "$PUBLIC_PATH/build:755"
    "$CONALCA_PATH/storage:775"
    "$CONALCA_PATH/bootstrap/cache:775"
)

perms_ok=true
for path_perm in "${CRITICAL_PATHS[@]}"; do
    path="${path_perm%:*}"
    expected="${path_perm#*:}"
    
    if [ -e "$path" ]; then
        actual=$(stat -c %a "$path")
        if [ "$actual" = "$expected" ] || [ "$actual" -ge "$expected" ]; then
            echo "   ✅ $path: $actual (esperado: $expected)"
        else
            echo "   ⚠️  $path: $actual (esperado: $expected)"
            perms_ok=false
        fi
    else
        echo "   ❌ $path - NO EXISTE"
        perms_ok=false
    fi
done

# ============================================================================
# 4. VERIFICAR STORAGE SYMLINK
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  STORAGE SYMLINK"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

storage_ok=true
if [ -L "$PUBLIC_PATH/storage" ]; then
    target=$(readlink "$PUBLIC_PATH/storage")
    if [ -d "$target" ]; then
        echo "   ✅ Symlink válido: storage -> $target"
    else
        echo "   ⚠️  Symlink existe pero target no: $target"
        storage_ok=false
    fi
else
    echo "   ❌ Symlink NO existe"
    storage_ok=false
fi

# ============================================================================
# 5. VERIFICAR VITE BUILD
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  VITE BUILD"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

vite_ok=true
if [ -f "$PUBLIC_PATH/build/manifest.json" ]; then
    echo "   ✅ manifest.json existe"
    
    # Contar assets
    asset_count=$(find "$PUBLIC_PATH/build/assets" -type f 2>/dev/null | wc -l)
    build_size=$(du -sh "$PUBLIC_PATH/build" 2>/dev/null | awk '{print $1}')
    echo "   📦 Assets compilados: $asset_count archivos ($build_size)"
    
    # Verificar CSS principal
    main_css=$(grep -o '"resources/css/app.css".*"file": "[^"]*"' "$PUBLIC_PATH/build/manifest.json" | grep -o 'assets/[^"]*' || echo "")
    if [ -n "$main_css" ]; then
        echo "   ✅ CSS principal: $main_css"
    else
        echo "   ❌ CSS principal no encontrado en manifest"
        vite_ok=false
    fi
else
    echo "   ❌ manifest.json NO EXISTE"
    vite_ok=false
fi

# ============================================================================
# 6. VERIFICAR SERVICIOS
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6️⃣  SERVICIOS SYSTEMD"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

SERVICES=(
    "nginx"
    "php8.3-fpm"
    "mariadb"
    "laravel-worker"
    "conalca-mcp-server"
)

services_ok=true
for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$service"; then
        enabled=$(systemctl is-enabled "$service" 2>/dev/null || echo "disabled")
        echo "   ✅ $service: activo ($enabled)"
    else
        echo "   ❌ $service: inactivo"
        services_ok=false
    fi
done

# ============================================================================
# RESUMEN FINAL
# ============================================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RESUMEN DE AUDITORÍA"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

all_ok=true
[ "$vendor_ok" = true ] && echo "   ✅ Vendors" || { echo "   ❌ Vendors"; all_ok=false; }
[ "$files_ok" = true ] && echo "   ✅ Archivos críticos" || { echo "   ❌ Archivos críticos"; all_ok=false; }
[ "$perms_ok" = true ] && echo "   ✅ Permisos" || { echo "   ⚠️  Permisos"; all_ok=false; }
[ "$storage_ok" = true ] && echo "   ✅ Storage symlink" || { echo "   ❌ Storage symlink"; all_ok=false; }
[ "$vite_ok" = true ] && echo "   ✅ Vite build" || { echo "   ❌ Vite build"; all_ok=false; }
[ "$services_ok" = true ] && echo "   ✅ Servicios" || { echo "   ❌ Servicios"; all_ok=false; }

echo ""
if [ "$all_ok" = true ]; then
    echo "🎉 TODAS LAS VERIFICACIONES PASARON EXITOSAMENTE"
    exit 0
else
    echo "⚠️  ALGUNAS VERIFICACIONES FALLARON - REVISAR ARRIBA"
    exit 1
fi
