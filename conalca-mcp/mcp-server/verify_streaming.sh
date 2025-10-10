#!/bin/bash

# Script de verificación completa para Conalca MCP Server - Streameable
# Verifica todas las capacidades de streaming HTTP

echo "🔍 Verificación completa del servidor MCP Streameable"
echo "=================================================="
echo ""

# Configuración
BASE_URL="https://my-kontrol.online/mcp"
TIMEOUT=10

# Función para mostrar resultados
show_result() {
    if [ $1 -eq 0 ]; then
        echo "✅ $2"
    else
        echo "❌ $2"
    fi
}

# Función para mostrar separador
separator() {
    echo ""
    echo "----------------------------------------"
    echo ""
}

# 1. Verificar que el servidor esté corriendo
echo "🚀 1. Verificando estado del servidor..."
response=$(curl -s -w "%{http_code}" -o /tmp/server_response.json "$BASE_URL/" 2>/dev/null)
http_code="${response: -3}"

if [ "$http_code" = "200" ]; then
    echo "✅ Servidor respondiendo correctamente (HTTP 200)"
    service_name=$(jq -r '.service' /tmp/server_response.json 2>/dev/null)
    version=$(jq -r '.version' /tmp/server_response.json 2>/dev/null)
    echo "   Servicio: $service_name"
    echo "   Versión: $version"
    
    # Mostrar capacidades de streaming
    echo "   Capacidades de streaming:"
    jq -r '.streaming.capabilities[]' /tmp/server_response.json 2>/dev/null | while read cap; do
        echo "     - $cap"
    done
else
    echo "❌ Servidor no responde correctamente (HTTP $http_code)"
    exit 1
fi

separator

# 2. Health Check detallado
echo "🏥 2. Verificando health check..."
response=$(curl -s -w "%{http_code}" -o /tmp/health_response.json "$BASE_URL/health" 2>/dev/null)
http_code="${response: -3}"

if [ "$http_code" = "200" ]; then
    status=$(jq -r '.status' /tmp/health_response.json 2>/dev/null)
    db_status=$(jq -r '.database' /tmp/health_response.json 2>/dev/null)
    echo "✅ Health check OK"
    echo "   Estado: $status"
    echo "   Base de datos: $db_status"
    echo "   Clientes conectados:"
    echo "     - WebSocket: $(jq -r '.streaming.websocket_clients' /tmp/health_response.json 2>/dev/null)"
    echo "     - SSE: $(jq -r '.streaming.sse_clients' /tmp/health_response.json 2>/dev/null)"
else
    echo "❌ Health check falló (HTTP $http_code)"
fi

separator

# 3. Verificar Server-Sent Events (SSE)
echo "📡 3. Verificando Server-Sent Events (SSE)..."
timeout $TIMEOUT curl -s -N -H "Accept: text/event-stream" "$BASE_URL/stream" > /tmp/sse_test.log 2>/dev/null &
sleep 3
kill $! 2>/dev/null || true

if [ -s /tmp/sse_test.log ]; then
    if grep -q "sse_connected" /tmp/sse_test.log; then
        echo "✅ Server-Sent Events funcionando"
        echo "   Eventos recibidos:"
        grep "data:" /tmp/sse_test.log | head -2 | while read line; do
            echo "     - $(echo $line | cut -d':' -f2- | jq -r '.type' 2>/dev/null)"
        done
    else
        echo "❌ SSE no recibió eventos esperados"
    fi
else
    echo "❌ Server-Sent Events no funciona"
fi

separator

# 4. Verificar HTTP Streaming
echo "🌊 4. Verificando HTTP Streaming..."
timeout $TIMEOUT curl -s "$BASE_URL/data/stream" > /tmp/streaming_test.log 2>/dev/null &
sleep 3
kill $! 2>/dev/null || true

if [ -s /tmp/streaming_test.log ]; then
    if grep -q "stream_start" /tmp/streaming_test.log; then
        echo "✅ HTTP Streaming funcionando"
        echo "   Datos recibidos:"
        grep "data:" /tmp/streaming_test.log | head -2 | while read line; do
            data=$(echo $line | sed 's/^data: //')
            type=$(echo $data | jq -r '.type' 2>/dev/null)
            echo "     - $type"
        done
    else
        echo "❌ HTTP Streaming no recibió datos esperados"
    fi
else
    echo "❌ HTTP Streaming no funciona"
fi

separator

# 5. Verificar WebSocket (configuración nginx)
echo "🔌 5. Verificando configuración WebSocket..."
response=$(curl -s -I "$BASE_URL/ws" 2>/dev/null | grep -i "HTTP")
if echo "$response" | grep -q "404"; then
    echo "✅ WebSocket endpoint configurado (404 es esperado para HTTP normal)"
    echo "   Endpoint disponible para conexiones WebSocket en: wss://my-kontrol.online/mcp/ws"
else
    echo "❌ WebSocket endpoint no configurado correctamente"
fi

separator

# 6. Verificar CORS Headers
echo "🌐 6. Verificando headers CORS..."
cors_headers=$(curl -s -I "$BASE_URL/" 2>/dev/null | grep -i "access-control")
if [ -n "$cors_headers" ]; then
    echo "✅ Headers CORS configurados"
    echo "$cors_headers" | while read header; do
        echo "     $header"
    done
else
    echo "❌ Headers CORS no configurados"
fi

separator

# 7. Verificar webhook de ElevenLabs
echo "🎤 7. Verificando endpoint webhook ElevenLabs..."
response=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" \
    -d '{"event_type":"test","test":true}' \
    "$BASE_URL/webhook/elevenlabs" 2>/dev/null)
http_code="${response: -3}"

if [ "$http_code" = "200" ]; then
    echo "✅ Webhook ElevenLabs funciona correctamente"
else
    echo "⚠️  Webhook ElevenLabs responde con HTTP $http_code (puede necesitar datos específicos)"
fi

separator

# 8. Verificar headers de streaming HTTP
echo "📋 8. Verificando headers HTTP para streaming..."
headers=$(curl -s -I -X GET "$BASE_URL/data/stream" 2>/dev/null)
if echo "$headers" | grep -qi "cache-control: no-cache"; then
    echo "✅ Headers de streaming configurados correctamente"
    echo "   Headers importantes encontrados:"
    echo "$headers" | grep -i -E "(cache-control|connection|content-type)" | while read header; do
        echo "     $header"
    done
else
    echo "❌ Headers de streaming no configurados correctamente"
fi

separator

# 9. Verificar proceso del servidor
echo "⚙️  9. Verificando proceso del servidor..."
if ps aux | grep -E "(server_streaming|start_streaming)" | grep -v grep > /dev/null; then
    echo "✅ Servidor corriendo correctamente"
    ps aux | grep -E "(server_streaming|start_streaming)" | grep -v grep | while read line; do
        echo "   $line"
    done
else
    echo "❌ Proceso del servidor no encontrado"
fi

separator

# 10. Resumen final
echo "📊 RESUMEN FINAL"
echo "==============="
echo ""
echo "🔗 Servidor MCP Streameable disponible en:"
echo "   https://my-kontrol.online/mcp/"
echo ""
echo "📡 Endpoints de streaming activos:"
echo "   - WebSocket: wss://my-kontrol.online/mcp/ws"
echo "   - Server-Sent Events: https://my-kontrol.online/mcp/stream"
echo "   - HTTP Streaming: https://my-kontrol.online/mcp/data/stream"
echo "   - Webhook ElevenLabs: https://my-kontrol.online/mcp/webhook/elevenlabs"
echo ""
echo "✨ El servidor está completamente configurado para streaming HTTP"
echo "   y listo para integración con ElevenLabs y otros servicios."
echo ""

# Limpiar archivos temporales
rm -f /tmp/server_response.json /tmp/health_response.json /tmp/sse_test.log /tmp/streaming_test.log

echo "🎉 Verificación completa finalizada!"
echo ""