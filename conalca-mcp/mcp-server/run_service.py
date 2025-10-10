#!/usr/bin/env python3
"""
Script para ejecutar Conalca MCP Server como servicio systemd
"""
import asyncio
import sys
import os

# Agregar el directorio del proyecto al path
sys.path.insert(0, '/root/conalca-mcp/mcp-server')

from conalca_mcp_server.server_mcp_compliant import run_http_server

if __name__ == "__main__":
    # Configuración para el servicio
    port = 18840
    root_path = "/mcp"
    
    print(f"🚀 Iniciando Conalca MCP Server como servicio systemd")
    print(f"   Puerto: {port}")
    print(f"   Root Path: {root_path}")
    print(f"   Protocolo: JSON-RPC 2.0 compatible con ElevenLabs")
    
    try:
        asyncio.run(run_http_server(port=port, root_path=root_path))
    except KeyboardInterrupt:
        print("🛑 Servidor detenido por usuario")
    except Exception as e:
        print(f"❌ Error en servidor: {e}")
        sys.exit(1)