#!/usr/bin/env python3
"""
Script de prueba para el servidor MCP de Conalca
Ejecuta pruebas básicas de conectividad y funcionalidad
"""

import asyncio
import sys
import os
import json
from pathlib import Path

# Agregar el directorio del servidor al path
sys.path.insert(0, str(Path(__file__).parent))

from conalca_mcp_server.database import db_connection
from conalca_mcp_server.models import repository

async def test_database_connection():
    """Prueba la conexión a la base de datos"""
    print("🔌 Probando conexión a la base de datos...")
    try:
        await db_connection.initialize()
        print("✅ Conexión exitosa")
        return True
    except Exception as e:
        print(f"❌ Error de conexión: {e}")
        return False

async def test_llamadas_query():
    """Prueba las consultas de llamadas"""
    print("📞 Probando consultas de llamadas...")
    try:
        llamadas = await repository.get_llamadas(limit=5)
        print(f"✅ Obtenidas {len(llamadas)} llamadas")
        if llamadas:
            print(f"   - Primera llamada: ID {llamadas[0].id_llamada}, Status: {llamadas[0].status}")
        return True
    except Exception as e:
        print(f"❌ Error en consulta de llamadas: {e}")
        return False

async def test_cotizaciones_query():
    """Prueba las consultas de cotizaciones"""
    print("💰 Probando consultas de cotizaciones...")
    try:
        cotizaciones = await repository.get_cotizaciones(limit=5)
        print(f"✅ Obtenidas {len(cotizaciones)} cotizaciones")
        if cotizaciones:
            print(f"   - Primera cotización: ID {cotizaciones[0].id}")
        return True
    except Exception as e:
        print(f"❌ Error en consulta de cotizaciones: {e}")
        return False

async def test_vehicles_query():
    """Prueba las consultas de vehículos"""
    print("🚛 Probando consultas de vehículos...")
    try:
        vehicles = await repository.get_vehicles(limit=5)
        print(f"✅ Obtenidos {len(vehicles)} vehículos")
        if vehicles:
            print(f"   - Primer vehículo: Placa {vehicles[0].placa}")
        return True
    except Exception as e:
        print(f"❌ Error en consulta de vehículos: {e}")
        return False

async def test_create_llamada():
    """Prueba la creación de una llamada de test"""
    print("🆕 Probando creación de llamada...")
    try:
        # Obtener una cotización existente
        cotizaciones = await repository.get_cotizaciones(limit=1)
        if not cotizaciones:
            print("❌ No hay cotizaciones disponibles para la prueba")
            return False
        
        # Crear una llamada de prueba con IDs existentes
        new_id = await repository.create_llamada(
            id_cotizacion=cotizaciones[0].id,  # Usar ID de cotización existente
            chofer_id=965,                     # Usar ID de chofer del ejemplo
            status='test'
        )
        
        if new_id > 0:
            print(f"✅ Llamada de prueba creada con ID: {new_id}")
            
            # Actualizar el status
            updated = await repository.update_llamada_status(
                new_id, 
                'test_completed', 
                'Prueba del servidor MCP'
            )
            
            if updated:
                print("✅ Status actualizado exitosamente")
            
            return True
        else:
            print("❌ No se pudo crear la llamada de prueba")
            return False
            
    except Exception as e:
        print(f"❌ Error creando llamada de prueba: {e}")
        return False

async def main():
    """Ejecuta todas las pruebas"""
    print("🧪 Iniciando pruebas del servidor MCP de Conalca")
    print("=" * 50)
    
    tests = [
        test_database_connection,
        test_llamadas_query,
        test_cotizaciones_query,
        test_vehicles_query,
        test_create_llamada
    ]
    
    results = []
    for test in tests:
        try:
            result = await test()
            results.append(result)
            print()
        except Exception as e:
            print(f"❌ Error inesperado en {test.__name__}: {e}")
            results.append(False)
            print()
    
    # Resumen
    print("=" * 50)
    print("📊 Resumen de pruebas:")
    passed = sum(results)
    total = len(results)
    
    print(f"✅ Pruebas exitosas: {passed}/{total}")
    if passed == total:
        print("🎉 ¡Todas las pruebas pasaron!")
        exit_code = 0
    else:
        print("⚠️  Algunas pruebas fallaron")
        exit_code = 1
    
    # Cerrar conexión
    await db_connection.close()
    
    return exit_code

if __name__ == "__main__":
    exit_code = asyncio.run(main())
    sys.exit(exit_code)