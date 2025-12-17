#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de prueba para verificar el nuevo flujo con llamadas_conductores
Versión: 2.0
Fecha: Diciembre 5, 2025
"""

import asyncio
import json
from conalca_mcp_server.database import Database
from conalca_mcp_server.models import repository

async def test_get_conductor_by_conversation_id():
    """
    Test 1: Verificar que get_conductor_by_conversation_id funciona correctamente
    """
    print("\n" + "="*80)
    print("TEST 1: get_conductor_by_conversation_id()")
    print("="*80)
    
    # Primero, obtener una llamada existente para probar
    query = """
    SELECT elevenlabs_conversation_id, id_cotizacion 
    FROM llamadas 
    WHERE elevenlabs_conversation_id IS NOT NULL 
    LIMIT 1
    """
    
    try:
        results = await repository.db.execute_query(query, ())
        
        if not results or len(results) == 0:
            print("❌ No se encontraron llamadas con conversation_id")
            print("   Crear primero una llamada de prueba desde la aplicación")
            return False
        
        conversation_id = results[0]['elevenlabs_conversation_id']
        cotizacion_id = results[0]['id_cotizacion']
        
        print(f"✓ Probando con conversation_id: {conversation_id}")
        print(f"✓ Cotización asociada: {cotizacion_id}")
        
        # Llamar al método
        conductor_data = await repository.get_conductor_by_conversation_id(conversation_id)
        
        if conductor_data:
            print("\n✅ ÉXITO: Conductor encontrado")
            print(f"   Identificador único: {conductor_data.get('identificador_unico')}")
            print(f"   Nombre: {conductor_data.get('nombre_conductor')}")
            print(f"   Teléfono: {conductor_data.get('telefono')}")
            print(f"   Placa: {conductor_data.get('placa')}")
            print(f"   Tipo vehículo: {conductor_data.get('tipo_vehiculo')}")
            print(f"   Ciudad actual: {conductor_data.get('ciudad_actual')}")
            print(f"   Origen: {conductor_data.get('ciudad_origen')}")
            print(f"   Destino: {conductor_data.get('ciudad_destino')}")
            print(f"   Mercancía: {conductor_data.get('mercancia')}")
            print(f"   Score: {conductor_data.get('score')}")
            print(f"   Estado llamada: {conductor_data.get('estado_llamada')}")
            return True
        else:
            print("\n⚠️ ADVERTENCIA: No se encontró conductor pendiente")
            print("   Posibles causas:")
            print("   1. No hay conductores en estado 'pendiente' para esta cotización")
            print("   2. Todos los conductores están marcados como no disponibles")
            print("   3. No se han guardado conductores en llamadas_conductores")
            
            # Verificar si existen conductores para esta cotización
            query_check = """
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN estado_llamada = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                   SUM(CASE WHEN disponible = 1 THEN 1 ELSE 0 END) as disponibles
            FROM llamadas_conductores 
            WHERE cotizacion_id = %s
            """
            check_results = await repository.db.execute_query(query_check, (cotizacion_id,))
            
            if check_results:
                print(f"\n   Estadísticas para cotización {cotizacion_id}:")
                print(f"   - Total conductores: {check_results[0]['total']}")
                print(f"   - Pendientes: {check_results[0]['pendientes']}")
                print(f"   - Disponibles: {check_results[0]['disponibles']}")
            
            return False
            
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        return False


async def test_save_driver_decision_new():
    """
    Test 2: Verificar que save_driver_decision_new funciona correctamente
    """
    print("\n" + "="*80)
    print("TEST 2: save_driver_decision_new()")
    print("="*80)
    
    # Buscar un conductor pendiente
    query = """
    SELECT identificador_unico, nombre_conductor, telefono, cotizacion_id
    FROM llamadas_conductores 
    WHERE estado_llamada = 'pendiente' 
      AND disponible = 1
    LIMIT 1
    """
    
    try:
        results = await repository.db.execute_query(query, ())
        
        if not results or len(results) == 0:
            print("❌ No se encontraron conductores pendientes para probar")
            print("   Ejecutar primero una búsqueda de conductores desde la aplicación")
            return False
        
        conductor = results[0]
        identificador_unico = conductor['identificador_unico']
        nombre = conductor['nombre_conductor']
        
        print(f"✓ Probando con conductor: {nombre}")
        print(f"✓ Identificador único: {identificador_unico}")
        
        # Guardar decisión de prueba (rechaza)
        conversation_id_test = "test_conv_12345"
        decision = 0  # Rechaza
        notas = "Prueba automática - rechaza"
        
        print(f"\n→ Guardando decisión: {decision} (rechaza)")
        
        success = await repository.save_driver_decision_new(
            identificador_unico=identificador_unico,
            conversation_id=conversation_id_test,
            decision=decision,
            notas=notas
        )
        
        if success:
            print("\n✅ ÉXITO: Decisión guardada correctamente")
            
            # Verificar el cambio
            query_verify = """
            SELECT estado_llamada, call_id, respuesta_llamada, notas, fecha_llamada
            FROM llamadas_conductores 
            WHERE identificador_unico = %s
            """
            verify_results = await repository.db.execute_query(query_verify, (identificador_unico,))
            
            if verify_results:
                updated = verify_results[0]
                print(f"\n   Estado actualizado:")
                print(f"   - estado_llamada: {updated['estado_llamada']}")
                print(f"   - call_id: {updated['call_id']}")
                print(f"   - respuesta_llamada: {updated['respuesta_llamada']}")
                print(f"   - notas: {updated['notas']}")
                print(f"   - fecha_llamada: {updated['fecha_llamada']}")
                
                # Revertir cambio (volver a pendiente para no afectar pruebas futuras)
                print("\n→ Revirtiendo cambio para mantener estado original...")
                query_revert = """
                UPDATE llamadas_conductores 
                SET estado_llamada = 'pendiente',
                    call_id = NULL,
                    respuesta_llamada = NULL,
                    notas = NULL,
                    fecha_llamada = NULL
                WHERE identificador_unico = %s
                """
                await repository.db.execute_update(query_revert, (identificador_unico,))
                print("✓ Estado revertido a 'pendiente'")
            
            return True
        else:
            print("\n❌ ERROR: No se pudo guardar la decisión")
            return False
            
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        return False


async def test_update_estado_llamada_conductor():
    """
    Test 3: Verificar que update_estado_llamada_conductor funciona correctamente
    """
    print("\n" + "="*80)
    print("TEST 3: update_estado_llamada_conductor()")
    print("="*80)
    
    # Buscar un conductor pendiente
    query = """
    SELECT identificador_unico, nombre_conductor
    FROM llamadas_conductores 
    WHERE estado_llamada = 'pendiente' 
      AND disponible = 1
    LIMIT 1
    """
    
    try:
        results = await repository.db.execute_query(query, ())
        
        if not results or len(results) == 0:
            print("❌ No se encontraron conductores pendientes para probar")
            return False
        
        conductor = results[0]
        identificador_unico = conductor['identificador_unico']
        nombre = conductor['nombre_conductor']
        
        print(f"✓ Probando con conductor: {nombre}")
        print(f"✓ Identificador único: {identificador_unico}")
        
        # Actualizar a "en_progreso"
        print(f"\n→ Actualizando estado a 'en_progreso'")
        
        success = await repository.update_estado_llamada_conductor(
            identificador_unico=identificador_unico,
            estado_llamada='en_progreso',
            call_id='test_call_67890',
            respuesta_llamada='Llamada en curso',
            notas='Prueba de actualización de estado'
        )
        
        if success:
            print("✅ ÉXITO: Estado actualizado correctamente")
            
            # Verificar
            query_verify = """
            SELECT estado_llamada, call_id, respuesta_llamada, notas
            FROM llamadas_conductores 
            WHERE identificador_unico = %s
            """
            verify_results = await repository.db.execute_query(query_verify, (identificador_unico,))
            
            if verify_results:
                updated = verify_results[0]
                print(f"\n   Valores actualizados:")
                print(f"   - estado_llamada: {updated['estado_llamada']}")
                print(f"   - call_id: {updated['call_id']}")
                print(f"   - respuesta_llamada: {updated['respuesta_llamada']}")
                print(f"   - notas: {updated['notas']}")
                
                # Revertir
                print("\n→ Revirtiendo cambio...")
                query_revert = """
                UPDATE llamadas_conductores 
                SET estado_llamada = 'pendiente',
                    call_id = NULL,
                    respuesta_llamada = NULL,
                    notas = NULL,
                    fecha_llamada = NULL
                WHERE identificador_unico = %s
                """
                await repository.db.execute_update(query_revert, (identificador_unico,))
                print("✓ Estado revertido a 'pendiente'")
            
            return True
        else:
            print("❌ ERROR: No se pudo actualizar el estado")
            return False
            
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        return False


async def test_statistics():
    """
    Test 4: Mostrar estadísticas de la tabla llamadas_conductores
    """
    print("\n" + "="*80)
    print("TEST 4: Estadísticas de llamadas_conductores")
    print("="*80)
    
    try:
        # Estadísticas generales
        query_stats = """
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado_llamada = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado_llamada = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
            SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado_llamada = 'fallida' THEN 1 ELSE 0 END) as fallidas,
            SUM(CASE WHEN estado_llamada = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN disponible = 1 THEN 1 ELSE 0 END) as disponibles,
            COUNT(DISTINCT cotizacion_id) as cotizaciones_unicas
        FROM llamadas_conductores
        """
        
        results = await repository.db.execute_query(query_stats, ())
        
        if results:
            stats = results[0]
            print("\n📊 Estadísticas Generales:")
            print(f"   Total de registros: {stats['total']}")
            print(f"   Cotizaciones únicas: {stats['cotizaciones_unicas']}")
            print(f"\n   Estados de llamadas:")
            print(f"   - Pendientes: {stats['pendientes']}")
            print(f"   - En progreso: {stats['en_progreso']}")
            print(f"   - Completadas: {stats['completadas']}")
            print(f"   - Fallidas: {stats['fallidas']}")
            print(f"   - Canceladas: {stats['canceladas']}")
            print(f"\n   Disponibles: {stats['disponibles']}")
            
            # Conductores recientes
            query_recent = """
            SELECT identificador_unico, nombre_conductor, telefono, tipo_vehiculo, 
                   estado_llamada, created_at
            FROM llamadas_conductores 
            ORDER BY created_at DESC 
            LIMIT 5
            """
            recent_results = await repository.db.execute_query(query_recent, ())
            
            if recent_results:
                print("\n📋 Últimos 5 conductores registrados:")
                for i, conductor in enumerate(recent_results, 1):
                    print(f"\n   {i}. {conductor['nombre_conductor']}")
                    print(f"      - Teléfono: {conductor['telefono']}")
                    print(f"      - Vehículo: {conductor['tipo_vehiculo']}")
                    print(f"      - Estado: {conductor['estado_llamada']}")
                    print(f"      - Fecha: {conductor['created_at']}")
            
            return True
        else:
            print("⚠️ No hay datos en la tabla llamadas_conductores")
            return False
            
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        return False


async def main():
    """
    Ejecutar todos los tests
    """
    print("\n" + "="*80)
    print("INICIANDO PRUEBAS DEL NUEVO FLUJO CON LLAMADAS_CONDUCTORES")
    print("="*80)
    
    # Conectar a la base de datos
    try:
        await repository.db.connect()
        print("✓ Conexión a base de datos establecida")
    except Exception as e:
        print(f"❌ Error al conectar a la base de datos: {str(e)}")
        return
    
    # Ejecutar tests
    results = {
        "test_1_get_conductor": False,
        "test_2_save_decision": False,
        "test_3_update_estado": False,
        "test_4_statistics": False
    }
    
    try:
        results["test_1_get_conductor"] = await test_get_conductor_by_conversation_id()
        results["test_2_save_decision"] = await test_save_driver_decision_new()
        results["test_3_update_estado"] = await test_update_estado_llamada_conductor()
        results["test_4_statistics"] = await test_statistics()
        
        # Resumen final
        print("\n" + "="*80)
        print("RESUMEN DE PRUEBAS")
        print("="*80)
        
        total_tests = len(results)
        passed_tests = sum(1 for v in results.values() if v)
        
        print(f"\nTests ejecutados: {total_tests}")
        print(f"Tests exitosos: {passed_tests}")
        print(f"Tests fallidos: {total_tests - passed_tests}")
        
        print("\nDetalle:")
        for test_name, result in results.items():
            status = "✅ PASS" if result else "❌ FAIL"
            print(f"  {status} - {test_name}")
        
        if passed_tests == total_tests:
            print("\n🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!")
            print("   El sistema está listo para producción.")
        else:
            print("\n⚠️ ALGUNOS TESTS FALLARON")
            print("   Revisar los errores antes de usar en producción.")
        
    finally:
        await repository.db.close()
        print("\n✓ Conexión a base de datos cerrada")
        print("\n" + "="*80)
        print("FIN DE PRUEBAS")
        print("="*80 + "\n")


if __name__ == "__main__":
    asyncio.run(main())
