#!/usr/bin/env python3
"""
Punto de entrada para el servidor MCP de Conalca
Ejecuta el servidor en modo HTTP para ser usado con nginx
"""

import sys
import os
import asyncio

# Añadir el directorio del proyecto al path
project_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, project_dir)

from conalca_mcp_server.server import run_http_server

if __name__ == "__main__":
    # Argumentos por defecto
    host = "127.0.0.1"
    port = 18840
    root_path = "/mcp"  # Root path por defecto para compatibilidad
    
    # Procesar argumentos de línea de comandos
    if len(sys.argv) > 1:
        port = int(sys.argv[1])
    if len(sys.argv) > 2:
        root_path = sys.argv[2]
    
    # Ejecutar servidor
    asyncio.run(run_http_server(host=host, port=port, root_path=root_path))