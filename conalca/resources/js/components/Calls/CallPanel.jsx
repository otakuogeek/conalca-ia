import React, { useEffect, useState } from 'react';
import { fetchCallStatus, startCallingDriversGroup, selectDriver, startElevenLabsCalls, buscarConductores } from '../../services/callService';
import useInterval from '../../hooks/useInterval';
import {
  FaPhoneAlt,
  FaTruckMoving,
  FaUserCheck,
  FaUserTimes,
  FaInfoCircle,
  FaClock,
  FaSpinner,
  FaCheckCircle,
  FaExclamationTriangle,
} from 'react-icons/fa';
import DriversModal from '../CotizacionInicial/DriversModal';
import PreviewDriversModal from './PreviewDriversModal';

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
  const [showDriversModal, setShowDriversModal] = useState(false);
  const [driversSearchData, setDriversSearchData] = useState(null);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [previewDriversData, setPreviewDriversData] = useState(null);
  const [isSearchingDrivers, setIsSearchingDrivers] = useState(false);
  const [previewSearchError, setPreviewSearchError] = useState('');
  const [startingCalls, setStartingCalls] = useState(false);

  const acceptedDrivers = data?.accepted || [];
  const maybeDrivers = data?.maybe || [];

  // Verifica si la fecha de cargue ya pasó (compara solo año/mes/día)
  const fechaCargueVencida = (() => {
    const raw = cotizacion?.fecha_hora_descargue_cargue || cotizacion?.fecha_cargue;
    if (!raw) return false;
    const d = new Date(String(raw).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return false;
    const hoy = new Date();
    const fechaCargueDia = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const hoyDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    return fechaCargueDia.getTime() < hoyDia.getTime();
  })();

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
    
    if (!cotizacion.id) {
      console.error('No se encontró ID de cotización');
      return;
    }
    
    setPreviewSearchError('');
    setPreviewDriversData(null);
    setShowPreviewModal(true);
    setIsSearchingDrivers(true);
    setLoadingBtn(true);

    try {
      // PASO 1: Buscar conductores disponibles en Arcángel
      console.log('🔍 Buscando conductores disponibles...');
      const searchResponse = await buscarConductores(cotizacion.id, 0, 50);
      
      if (!searchResponse.data || !searchResponse.data.success) {
        throw new Error(searchResponse.data?.message || 'Error al buscar conductores');
      }
      
      const conductoresData = searchResponse.data.data;
      console.log(`✅ Encontrados ${conductoresData.total} conductores`);
      
      // PASO 2: Mostrar vista previa de conductores
      setPreviewDriversData(conductoresData);
      
    } catch (e) {
      console.error('Error buscando conductores:', e);

      setPreviewSearchError(
        e?.response?.data?.message || 'Error al buscar conductores. Por favor, intenta de nuevo.'
      );
    } finally {
      setIsSearchingDrivers(false);
      setLoadingBtn(false);
    }
  };

  // Nueva función que se ejecuta cuando se confirma en el modal de vista previa
  const confirmAndRegisterCalls = async () => {
    setShowPreviewModal(false);
    setLoadingBtn(true);
    setStartingCalls(true);
    
    try {
      // PASO 3: Registrar las llamadas en el sistema
      console.log('📞 Registrando llamadas en el sistema...');
      const response = await startCallingDriversGroup(cotizacion.group_cotization_id);
      
      if (response.data && response.data.success) {
        console.log('✅ Llamadas registradas exitosamente:', response.data);
        
        // PASO 4: Mostrar modal con los conductores encontrados
        setDriversSearchData(previewDriversData);
        setShowDriversModal(true);
        
        // Cerrar el modal padre si existe la función
        if (onModalClose && typeof onModalClose === 'function') {
          onModalClose();
        }
        
        // Iniciar llamadas reales inmediatamente después del registro
        await startRealCalls();
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
      setStartingCalls(false);
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
        console.log('✅ Llamadas ElevenLabs procesadas:', response.data);
        
        if (window.Swal) {
          // Si no hay llamadas pendientes
          if (response.data.llamadas_programadas === 0) {
            window.Swal.fire({
              title: 'Sin Llamadas Pendientes',
              text: 'No hay llamadas pendientes para esta cotización. Verifica que hayas asignado conductores.',
              icon: 'info',
              confirmButtonText: 'Entendido',
              confirmButtonColor: '#3b82f6'
            });
          } else {
            // Llamadas iniciadas correctamente
            window.Swal.fire({
              title: '¡Llamadas Iniciadas!',
              text: `Se iniciaron ${response.data.calls_initiated || response.data.llamadas_programadas} llamadas exitosamente. ${response.data.failed_calls > 0 ? `${response.data.failed_calls} llamadas fallaron.` : ''}`,
              icon: 'success',
              confirmButtonText: 'Entendido',
              confirmButtonColor: '#f97316'
            });
          }
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

  const openDriverDetails = (driverId) => {
    const url = `/conductor-details/${driverId}`;
    window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
  };

  const executionSummary = data?.call_execution?.summary;
  const recentCalls = data?.call_execution?.recent_calls || [];

  const getQueueStatusLabel = (call) => {
    if (call.queue_status === 'completed') return 'Completada';
    if (call.queue_status === 'failed') return 'Fallida';
    if (call.queue_status === 'cancelled') return 'Cancelada';
    if (call.queue_status === 'processing') return 'Procesando';
    if (call.queue_status === 'pending') return 'En cola';
    return 'Sin estado';
  };

  const getQueueStatusClasses = (call) => {
    if (call.queue_status === 'completed') return 'bg-green-100 text-green-700 border-green-200';
    if (call.queue_status === 'failed') return 'bg-red-100 text-red-700 border-red-200';
    if (call.queue_status === 'cancelled') return 'bg-slate-100 text-slate-700 border-slate-200';
    if (call.queue_status === 'processing') return 'bg-amber-100 text-amber-700 border-amber-200';
    if (call.queue_status === 'pending') return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-gray-100 text-gray-600 border-gray-200';
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
        <strong>Total llamadas Ejecutadas:</strong> {driversSearchData?.total || data.total_to_call}
        {driversSearchData && (
          <span className="ml-2 text-xs text-green-600">
            (Actualizado desde Arcángel)
          </span>
        )}
      </p>

      <div className="flex gap-2">
        <button
          onClick={handleCall}
          disabled={loadingBtn || startingCalls || fechaCargueVencida}
          title={fechaCargueVencida ? 'No se puede llamar: la fecha de cargue ya pasó' : undefined}
          className="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
        >
          <FaPhoneAlt className={(loadingBtn || startingCalls) ? 'animate-ping' : ''}/>
          {loadingBtn
            ? 'Buscando…'
            : startingCalls
              ? 'Realizando…'
              : fechaCargueVencida
                ? 'Fecha de cargue vencida'
                : 'Realizar Llamada'}
        </button>
      </div>

      {fechaCargueVencida && (
        <p className="text-xs text-red-600 flex items-center gap-1">
          <FaExclamationTriangle /> La fecha de cargue ya pasó. No se pueden realizar llamadas.
        </p>
      )}

      {executionSummary && executionSummary.registered > 0 && (
        <div className="rounded-xl border border-orange-200 bg-orange-50/60 p-3 space-y-3">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h4 className="font-semibold text-gray-800 flex items-center gap-2">
                <FaClock className="text-orange-600" />
                Estado del Envio de Llamadas
              </h4>
              <p className="text-xs text-gray-600">
                Ultima actualizacion: {data.call_execution?.last_updated || 'N/A'}
              </p>
            </div>
            <div className="text-right">
              <div className="text-lg font-bold text-orange-700">{executionSummary.progress_percentage}%</div>
              <div className="text-xs text-gray-600">
                {executionSummary.finished} de {executionSummary.registered} procesadas
              </div>
            </div>
          </div>

          <div className="h-2 w-full rounded-full bg-white overflow-hidden border border-orange-100">
            <div
              className="h-full bg-gradient-to-r from-orange-500 to-orange-600 transition-all duration-500"
              style={{ width: `${executionSummary.progress_percentage}%` }}
            />
          </div>

          <div className="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
            <div className="rounded-lg bg-white border border-gray-200 px-3 py-2">
              <div className="text-gray-500">Registradas</div>
              <div className="font-semibold text-gray-800">{executionSummary.registered}</div>
            </div>
            <div className="rounded-lg bg-white border border-blue-200 px-3 py-2">
              <div className="text-blue-600">En cola</div>
              <div className="font-semibold text-blue-800">{executionSummary.pending}</div>
            </div>
            <div className="rounded-lg bg-white border border-amber-200 px-3 py-2">
              <div className="text-amber-600">Procesando</div>
              <div className="font-semibold text-amber-800">{executionSummary.processing}</div>
            </div>
            <div className="rounded-lg bg-white border border-green-200 px-3 py-2">
              <div className="text-green-600">Completadas</div>
              <div className="font-semibold text-green-800">{executionSummary.completed}</div>
            </div>
            <div className="rounded-lg bg-white border border-red-200 px-3 py-2">
              <div className="text-red-600">Fallidas</div>
              <div className="font-semibold text-red-800">{executionSummary.failed}</div>
            </div>
          </div>

          <div className="flex items-center gap-2 text-xs text-gray-700">
            {executionSummary.active ? (
              <>
                <FaSpinner className="text-orange-600 animate-spin" />
                El sistema sigue enviando llamadas y actualizando estados en tiempo real.
              </>
            ) : executionSummary.completed > 0 ? (
              <>
                <FaCheckCircle className="text-green-600" />
                El procesamiento termino. Revisa abajo el resultado por conductor.
              </>
            ) : (
              <>
                <FaExclamationTriangle className="text-amber-600" />
                Las llamadas fueron registradas, pero aun no hay actividad confirmada.
              </>
            )}
          </div>
        </div>
      )}

      {recentCalls.length > 0 && (
        <div>
          <h4 className="font-medium mb-2 flex items-center gap-1 text-gray-800">
            <FaPhoneAlt className="text-orange-600" /> Seguimiento de Llamadas
          </h4>
          <ul className="space-y-2 max-h-64 overflow-y-auto pr-1">
            {recentCalls.map((call) => (
              <li key={call.id_llamada} className="rounded-lg border border-gray-200 bg-white px-3 py-2">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="text-sm font-semibold text-gray-800 truncate">{call.driver_name}</div>
                    <div className="text-xs text-gray-600 truncate">
                      {call.driver_phone || 'Sin telefono'}
                      {call.placa ? ` · ${call.placa}` : ''}
                      {call.batch_number ? ` · Lote ${call.batch_number}` : ''}
                    </div>
                    <div className="text-xs text-gray-500 mt-0.5">
                      {call.global_position
                        ? `Llamada en lote ${call.batch_number}, posición ${call.batch_position}`
                        : (call.call_notes || call.failure_reason || 'Esperando actualizacion del proceso')
                      }
                    </div>
                  </div>
                  <div className={`shrink-0 rounded-full border px-2 py-1 text-xs font-medium ${getQueueStatusClasses(call)}`}>
                    {getQueueStatusLabel(call)}
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      <div>
        <h4 className="font-medium mb-1 flex items-center gap-1">
          <FaUserCheck/> Aceptaron
          {acceptedDrivers.length > 0 && (
            <span className="text-xs text-gray-500 ml-1">
              ({data.total_accepted || acceptedDrivers.length})
            </span>
          )}
        </h4>
        {acceptedDrivers.length ? (
          <ul className="space-y-2">
            {acceptedDrivers.slice(0, 7).map(d => (
              <li key={d.id} className="flex items-center justify-between gap-2 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                <div className="flex flex-col min-w-0">
                  <span className="text-sm font-semibold text-gray-800 truncate">{d.name}</span>
                  <span className="text-xs text-gray-600">
                    📞 {d.phone}
                    {d.placa && d.placa !== 'Sin placa' && (
                      <span className="ml-2">🚛 {d.placa}</span>
                    )}
                  </span>
                  {d.tipo_vehiculo && (
                    <span className="text-xs text-gray-500">{d.tipo_vehiculo}</span>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  {/* Botón de información */}
                  <button
                    onClick={() => openDriverDetails(d.id)}
                    title="Ver detalles completos del conductor"
                    className="text-blue-600 hover:text-blue-800 hover:bg-blue-100 p-2 rounded-full transition-colors"
                  >
                    <FaInfoCircle className="text-lg" />
                  </button>

                  {/* Botón de selección */}
                  {data.selected_driver_id === d.id ? (
                    <span className="text-green-600 text-xs font-medium whitespace-nowrap bg-green-100 px-2 py-1 rounded-full">
                      ✓ Seleccionado
                    </span>
                  ) : (
                    <button
                      onClick={() => handleSelectDriver(d.id)}
                      disabled={!!selectingId}
                      className="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {selectingId === d.id ? 'Guardando…' : 'Elegir'}
                    </button>
                  )}
                </div>
              </li>
            ))}
            {(data.total_accepted || acceptedDrivers.length) > 7 && (
              <p className="text-xs text-gray-500 text-center mt-1">
                Mostrando 7 de {data.total_accepted} conductores que aceptaron
              </p>
            )}
          </ul>
        ) : (
          <p className="text-sm text-gray-500 flex items-center gap-1">
            <FaUserTimes/> Ninguno aún
          </p>
        )}
      </div>

      <div>
        <h4 className="font-medium mb-1 flex items-center gap-1 text-amber-800">
          <FaClock/> Tal vez / Seguimiento
          {maybeDrivers.length > 0 && (
            <span className="text-xs ml-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">
              ({data.total_maybe || maybeDrivers.length})
            </span>
          )}
        </h4>
        {maybeDrivers.length ? (
          <ul className="space-y-2">
            {maybeDrivers.slice(0, 7).map(d => (
              <li key={d.id} className="flex items-center justify-between gap-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                <div className="flex flex-col min-w-0">
                  <span className="text-sm font-semibold text-gray-800 truncate">{d.name}</span>
                  <span className="text-xs text-gray-600">
                    📞 {d.phone}
                    {d.placa && d.placa !== 'Sin placa' && (
                      <span className="ml-2">🚛 {d.placa}</span>
                    )}
                  </span>
                  {d.tipo_vehiculo && (
                    <span className="text-xs text-gray-500">{d.tipo_vehiculo}</span>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  <span className="text-xs px-2 py-1 rounded-full bg-amber-200 text-amber-900 font-medium whitespace-nowrap">
                    En seguimiento
                  </span>
                  <button
                    onClick={() => openDriverDetails(d.id)}
                    title="Ver detalles completos del conductor"
                    className="text-blue-600 hover:text-blue-800 hover:bg-blue-100 p-2 rounded-full transition-colors"
                  >
                    <FaInfoCircle className="text-lg" />
                  </button>
                </div>
              </li>
            ))}
            {(data.total_maybe || maybeDrivers.length) > 7 && (
              <p className="text-xs text-gray-500 text-center mt-1">
                Mostrando 7 de {data.total_maybe} conductores en seguimiento
              </p>
            )}
          </ul>
        ) : (
          <p className="text-sm text-amber-700 flex items-center gap-1">
            <FaClock/> Ninguno aún
          </p>
        )}
      </div>

      {/* Modal de búsqueda de conductores */}
      {showDriversModal && driversSearchData && (
        <DriversModal
          isOpen={showDriversModal}
          onClose={() => setShowDriversModal(false)}
          cotizacionId={cotizacion.id}
          cotizacionData={{
            ciudad_origen: data.ciudad_origen || cotizacion.ciudad_origen,
            ciudad_destino: data.ciudad_destino || cotizacion.ciudad_destino,
            tipo_vehiculo: data.vehicle_type,
            conductores: driversSearchData.conductores || [],
            total: driversSearchData.total || 0,
          }}
        />
      )}

      {/* Modal de vista previa de conductores */}
      {showPreviewModal && (
        <PreviewDriversModal
          isOpen={showPreviewModal}
          onClose={() => {
            setShowPreviewModal(false);
            setLoadingBtn(false);
            setIsSearchingDrivers(false);
            setPreviewSearchError('');
          }}
          onConfirm={confirmAndRegisterCalls}
          onRetry={handleCall}
          isLoading={isSearchingDrivers}
          errorMessage={previewSearchError}
          drivers={previewDriversData?.conductores || []}
          totalDrivers={previewDriversData?.total || 0}
          searchMeta={previewDriversData?.source_stats || null}
          cotizacionInfo={{
            ciudad_origen: data?.ciudad_origen || cotizacion.ciudad_origen,
            ciudad_destino: data?.ciudad_destino || cotizacion.ciudad_destino,
            vehicle_type: data?.vehicle_type || cotizacion.vehiculo_requerido,
          }}
        />
      )}
    </div>
  );
}