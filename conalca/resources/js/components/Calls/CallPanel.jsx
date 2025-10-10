import React, { useEffect, useState } from 'react';
import { fetchCallStatus, startCallingDriversGroup, selectDriver, startElevenLabsCalls } from '../../services/callService';
import useInterval from '../../hooks/useInterval';
import {
  FaPhoneAlt,
  FaTruckMoving,
  FaUserCheck,
  FaUserTimes,
} from 'react-icons/fa';

const PanelSkeleton = () => (
  <div className="animate-pulse space-y-2">
    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
    <div className="h-4 bg-gray-200 rounded w-1/2"></div>
    <div className="h-4 bg-gray-200 rounded w-full"></div>
  </div>
);

export default function CallPanel({ cotizacion, onModalClose }) {
  const [data, setData] = useState(null);
  const [loadingBtn, setLoadingBtn] = useState(false);
  const [selectingId, setSelectingId] = useState(null);
console.log(data)
  const load = async () => {
    try {
      const res = await fetchCallStatus(cotizacion.id);
      setData(res.data);
    } catch (e) {
      console.error('CallStatus error', e);
    }
  };

  useInterval(load, 5000);
  useEffect(() => { load(); }, []);

  const handleCall = async () => {
    if (!cotizacion.group_cotization_id) {
      console.error('No se encontró group_cotization_id para esta cotización');
      return;
    }
    
    setLoadingBtn(true);
    try {
      // Usar el nuevo sistema de registro de llamadas por grupo
      const response = await startCallingDriversGroup(cotizacion.group_cotization_id);
      
      if (response.data && response.data.success) {
        // Mostrar mensaje de éxito
        console.log('✅ Llamadas registradas exitosamente:', response.data);
        
        // Cerrar el modal padre si existe la función
        if (onModalClose && typeof onModalClose === 'function') {
          onModalClose();
        }
        
        // Mostrar mensaje de éxito después de un pequeño delay para que se cierre el modal
        setTimeout(async () => {
          // Mostrar mensaje de éxito con SweetAlert si está disponible
          if (window.Swal) {
            const result = await window.Swal.fire({
              title: '¡Éxito!',
              text: `Llamadas registradas exitosamente: ${response.data.summary.total_drivers_called} conductores para ${response.data.total_cotizaciones} cotizaciones. ¿Desea iniciar las llamadas ahora?`,
              icon: 'success',
              showCancelButton: true,
              confirmButtonText: 'Iniciar Llamadas',
              cancelButtonText: 'Solo Registrar',
              confirmButtonColor: '#f97316',
              cancelButtonColor: '#6b7280'
            });

            if (result.isConfirmed) {
              await startRealCalls();
            }
          }
          // Fallback: mostrar notificación si existe la función
          else if (window.showNotification) {
            window.showNotification(
              `Llamadas registradas: ${response.data.summary.total_drivers_called} conductores para ${response.data.total_cotizaciones} cotizaciones`,
              'success'
            );
            // En fallback, iniciar llamadas automáticamente después de un delay
            setTimeout(() => startRealCalls(), 2000);
          }
          // Fallback final: alert simple
          else {
            const userWantsToCall = confirm(`¡Éxito! Llamadas registradas: ${response.data.summary.total_drivers_called} conductores para ${response.data.total_cotizaciones} cotizaciones. ¿Desea iniciar las llamadas ahora?`);
            if (userWantsToCall) {
              await startRealCalls();
            }
          }
        }, 300); // 300ms de delay para que se cierre el modal primero
      } else {
        throw new Error(response.data?.message || 'Error al registrar llamadas');
      }
      
      await load();
    } catch (e) {
      console.error('Error registrando llamadas:', e);
      
      // Mostrar error con SweetAlert si está disponible
      if (window.Swal) {
        window.Swal.fire({
          title: 'Error',
          text: 'Error al registrar las llamadas. Por favor, intenta de nuevo.',
          icon: 'error',
          confirmButtonText: 'Aceptar',
          confirmButtonColor: '#f97316'
        });
      }
      // Fallback: mostrar notificación si existe la función
      else if (window.showNotification) {
        window.showNotification('Error al registrar las llamadas', 'error');
      }
      // Fallback final: alert simple
      else {
        alert('Error al registrar las llamadas. Por favor, intenta de nuevo.');
      }
    } finally {
      setLoadingBtn(false);
    }
  };

  // Función para iniciar llamadas reales con ElevenLabs
  const startRealCalls = async () => {
    try {
      console.log('🔥 Iniciando llamadas reales con ElevenLabs para cotización:', cotizacion.id);
      
      // Mostrar loader mientras se inician las llamadas
      if (window.Swal) {
        window.Swal.fire({
          title: 'Iniciando Llamadas...',
          text: 'Conectando con los conductores vía ElevenLabs',
          icon: 'info',
          allowOutsideClick: false,
          didOpen: () => {
            window.Swal.showLoading()
          }
        });
      }

      const response = await startElevenLabsCalls(cotizacion.id);
      
      if (response.data && response.data.success) {
        console.log('✅ Llamadas ElevenLabs iniciadas exitosamente:', response.data);
        
        if (window.Swal) {
          window.Swal.fire({
            title: '¡Llamadas Iniciadas!',
            text: `Se iniciaron ${response.data.calls_initiated} llamadas exitosamente. ${response.data.failed_calls > 0 ? `${response.data.failed_calls} llamadas fallaron.` : ''}`,
            icon: 'success',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f97316'
          });
        }
        
        // Recargar datos para reflejar los cambios
        await load();
      } else {
        throw new Error(response.data?.message || 'Error al iniciar llamadas');
      }

    } catch (e) {
      console.error('Error iniciando llamadas ElevenLabs:', e);
      
      if (window.Swal) {
        window.Swal.fire({
          title: 'Error al Iniciar Llamadas',
          text: 'No se pudieron iniciar las llamadas con ElevenLabs. Las llamadas siguen registradas en el sistema.',
          icon: 'warning',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#f97316'
        });
      } else {
        alert('Error al iniciar llamadas con ElevenLabs. Las llamadas siguen registradas en el sistema.');
      }
    }
  };

  const handleSelectDriver = async (driverId) => {
    setSelectingId(driverId);
    try {
      await selectDriver(cotizacion.id, driverId);
      await load();
    } catch (e) {
      console.error('selectDriver error', e);
      // Aquí puedes mostrar un toast o mensaje de error si quieres
    } finally {
      setSelectingId(null);
    }
  };

  if (!data) return (
    <div className="bg-white/90 p-4 rounded-xl border border-gray-200 shadow">
      <PanelSkeleton/>
    </div>
  );

  return (
    <div className="bg-white/90 p-4 rounded-xl border border-gray-200 shadow space-y-3">
      <h3 className="text-lg font-semibold text-orange-600 flex items-center gap-2">
        <FaTruckMoving/>  Registro de Llamadas
      </h3>

      <p className="text-sm"><strong>Prompt:</strong> {data.prompt}</p>
      <p className="text-sm">
        <strong>Tipo de vehículo:</strong> {data.vehicle_type}
      </p>
      <p className="text-sm">
        <strong>Total conductores:</strong> {data.total_to_call}
      </p>

      <button
        onClick={handleCall}
        disabled={loadingBtn}
        className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
      >
        <FaPhoneAlt className={loadingBtn ? 'animate-ping' : ''}/>
        {loadingBtn ? 'Registrando…' : 'Registrar Llamadas'}
      </button>

      <div>
        <h4 className="font-medium mb-1 flex items-center gap-1">
          <FaUserCheck/> Aceptaron
        </h4>
        {data.accepted.length ? (
          <ul className="list-disc list-inside text-sm space-y-1">
            {data.accepted.map(d => (
              <li key={d.id} className="flex items-center justify-between gap-2">
                <span>{d.name} – {d.phone}</span>

                {data.selected_driver_id === d.id ? (
                  <span className="text-green-600 text-xs font-medium">
                    Seleccionado
                  </span>
                ) : (
                  <button
                    onClick={() => handleSelectDriver(d.id)}
                    disabled={!!selectingId}
                    className="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {selectingId === d.id ? 'Guardando…' : 'Elegir este conductor'}
                  </button>
                )}
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-sm text-gray-500 flex items-center gap-1">
            <FaUserTimes/> Ninguno aún
          </p>
        )}
      </div>
    </div>
  );
}