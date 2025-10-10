#!/usr/bin/env python3
"""
Ejemplo de uso del servidor MCP de Conalca desde ElevenLabs
Este script demuestra cómo interactuar con las herramientas MCP
"""

import asyncio
import json
import sys
from pathlib import Path

# Agregar el directório del servidor al path
sys.path.insert(0, str(Path(__file__).parent))

from conalca_mcp_server.database import db_connection
from conalca_mcp_server.models import repository

async def ejemplo_buscar_conductor_por_telefono():
    """Ejemplo: Buscar conductor por teléfono (útil para ElevenLabs)"""
    print("📞 EJEMPLO: Buscar conductor por teléfono")
    print("=" * 50)
    
    # Simular recibir un teléfono de ElevenLabs
    telefono_ejemplo = "+573123456789"  # Cambiar por un teléfono real de tu BD
    
    try:
        await db_connection.initialize()
        
        # Buscar vehículo por teléfono del conductor
        vehicle = await repository.get_vehicle_by_telefono_conductor(telefono_ejemplo)
        
        if vehicle:
            print(f"✅ Conductor encontrado:")
            print(f"   - Nombre: {vehicle.conductor}")
            print(f"   - Teléfono: {vehicle.telefonoconductor}")
            print(f"   - Placa: {vehicle.placa}")
            print(f"   - Propietario: {vehicle.propietario}")
            print(f"   - Marca: {vehicle.marca}")
            print(f"   - Modelo: {vehicle.modelo}")
        else:
            print(f"❌ No se encontró conductor con teléfono: {telefono_ejemplo}")
    
    except Exception as e:
        print(f"❌ Error: {e}")

async def ejemplo_crear_llamada_elevenlabs():
    """Ejemplo: Crear llamada para ElevenLabs"""
    print("\n📱 EJEMPLO: Crear llamada para ElevenLabs")
    print("=" * 50)
    
    try:
        # Obtener una cotización existente
        cotizaciones = await repository.get_cotizaciones(limit=1)
        if not cotizaciones:
            print("❌ No hay cotizaciones disponibles")
            return
        
        cotizacion = cotizaciones[0]
        
        # Crear nueva llamada
        new_id = await repository.create_llamada(
            id_cotizacion=cotizacion.id,
            chofer_id=965,  # ID del chofer
            status='iniciada'
        )
        
        if new_id > 0:
            print(f"✅ Nueva llamada creada con ID: {new_id}")
            print(f"   - Cotización: {cotizacion.id}")
            print(f"   - Ruta: {cotizacion.ciudad_origen} → {cotizacion.ciudad_destino}")
            print(f"   - Valor: {cotizacion.valor}")
            
            # Simular actualización desde ElevenLabs
            await repository.update_llamada_status(
                new_id,
                'en_curso',
                'Llamada iniciada desde ElevenLabs'
            )
            print(f"✅ Status actualizado a 'en_curso'")
            
            return new_id
        else:
            print("❌ Error al crear la llamada")
            
    except Exception as e:
        print(f"❌ Error: {e}")

async def ejemplo_webhook_elevenlabs():
    """Ejemplo: Procesar webhook de ElevenLabs"""
    print("\n🎣 EJEMPLO: Procesar webhook de ElevenLabs")
    print("=" * 50)
    
    try:
        # Obtener una llamada existente
        llamadas = await repository.get_llamadas(limit=1)
        if not llamadas:
            print("❌ No hay llamadas disponibles")
            return
        
        llamada = llamadas[0]
        
        # Simular datos de webhook de ElevenLabs
        webhook_data = {
            "conversation_id": llamada.elevenlabs_conversation_id or "conv_example_123",
            "status": "completada",
            "call_data": {
                "duration": 180,
                "outcome": "success",
                "transcript": "Llamada completada exitosamente",
                "call_cost": 0.05
            }
        }
        
        print(f"📨 Webhook recibido:")
        print(f"   - Conversation ID: {webhook_data['conversation_id']}")
        print(f"   - Nuevo status: {webhook_data['status']}")
        print(f"   - Duración: {webhook_data['call_data']['duration']} segundos")
        
        # Actualizar la llamada con los datos del webhook
        success = await repository.update_llamada_status(
            llamada.id_llamada,
            webhook_data['status'],
            json.dumps(webhook_data['call_data'])
        )
        
        if success:
            print(f"✅ Llamada {llamada.id_llamada} actualizada desde webhook")
        else:
            print(f"❌ Error actualizando llamada desde webhook")
            
    except Exception as e:
        print(f"❌ Error: {e}")

async def ejemplo_consultar_cotizaciones():
    """Ejemplo: Consultar cotizaciones disponibles"""
    print("\n💰 EJEMPLO: Consultar cotizaciones")
    print("=" * 50)
    
    try:
        cotizaciones = await repository.get_cotizaciones(limit=3)
        
        print(f"✅ Cotizaciones disponibles: {len(cotizaciones)}")
        
        for i, cotizacion in enumerate(cotizaciones, 1):
            print(f"\n   [{i}] Cotización #{cotizacion.id}")
            print(f"       - Origen: {cotizacion.ciudad_origen}")
            print(f"       - Destino: {cotizacion.ciudad_destino}")
            print(f"       - Ruta: {cotizacion.ruta}")
            print(f"       - Valor: {cotizacion.valor}")
            print(f"       - Vehículo: {cotizacion.vehiculo_requerido}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

async def main():
    """Ejecuta todos los ejemplos"""
    print("🚀 EJEMPLOS DE USO DEL SERVIDOR MCP CONALCA")
    print("🔗 Integración con ElevenLabs")
    print("=" * 60)
    
    try:
        await db_connection.initialize()
        
        # Ejecutar ejemplos
        await ejemplo_consultar_cotizaciones()
        await ejemplo_buscar_conductor_por_telefono()
        await ejemplo_crear_llamada_elevenlabs()
        await ejemplo_webhook_elevenlabs()
        
        print("\n" + "=" * 60)
        print("✅ Todos los ejemplos ejecutados correctamente")
        print("\n💡 CÓMO USAR DESDE ELEVENLABS:")
        print("   1. Configura el servidor MCP en tu configuración de ElevenLabs")
        print("   2. Usa las herramientas MCP desde tus agentes de IA:")
        print("      - get_vehicle_by_telefono_conductor(telefono)")
        print("      - create_llamada(id_cotizacion, chofer_id)")
        print("      - update_llamada_status(id_llamada, status)")
        print("      - elevenlabs_webhook_handler(conversation_id, status)")
        print("   3. El servidor manejará automáticamente las actualizaciones en tiempo real")
        
    except Exception as e:
        print(f"❌ Error general: {e}")
    
    finally:
        await db_connection.close()

if __name__ == "__main__":
    asyncio.run(main())