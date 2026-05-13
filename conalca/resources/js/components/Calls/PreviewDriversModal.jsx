import React, { useEffect, useMemo, useState } from 'react';
import ReactDOM from 'react-dom';
import {
  FaUser,
  FaPhone,
  FaTruck,
  FaMapMarkerAlt,
  FaTimes,
  FaCheckCircle,
  FaPhoneSlash,
  FaCircleNotch,
  FaClock,
  FaExclamationTriangle,
  FaSearch,
} from 'react-icons/fa';

export default function PreviewDriversModal({
  isOpen,
  onClose,
  onConfirm,
  onRetry,
  drivers,
  totalDrivers,
  searchMeta,
  cotizacionInfo,
  isLoading = false,
  errorMessage = '',
}) {
  if (!isOpen) return null;

  const portalTarget = document.getElementById('modal-root') || document.body;
  const yaLlamados = drivers?.filter(d => d.ya_llamado) || [];
  const [elapsedSeconds, setElapsedSeconds] = useState(0);

  useEffect(() => {
    if (!isLoading) {
      setElapsedSeconds(0);
      return undefined;
    }

    const intervalId = window.setInterval(() => {
      setElapsedSeconds((current) => current + 1);
    }, 1000);

    return () => window.clearInterval(intervalId);
  }, [isLoading]);

  const loadingSteps = useMemo(() => {
    const vehicleType = cotizacionInfo?.vehicle_type || 'vehículos';

    return [
      'Iniciando conexión con Arcángel',
      `Filtrando disponibilidad para ${vehicleType}`,
      'Validando teléfonos y conductores encontrados',
      'Preparando la vista previa para confirmar',
    ];
  }, [cotizacionInfo?.vehicle_type]);

  const activeStepIndex = Math.min(Math.floor(elapsedSeconds / 3), loadingSteps.length - 1);

  const estadoLabel = (estado) => {
    const map = {
      'en_progreso': 'En progreso',
      'completada': 'Completada',
      'fallida': 'Fallida',
      'cancelada': 'Cancelada',
    };
    return map[estado] || estado || 'Llamado';
  };

  const estadoColor = (estado) => {
    const map = {
      'en_progreso': 'bg-yellow-100 text-yellow-800 border-yellow-300',
      'completada': 'bg-green-100 text-green-800 border-green-300',
      'fallida': 'bg-red-100 text-red-800 border-red-300',
      'cancelada': 'bg-gray-100 text-gray-800 border-gray-300',
    };
    return map[estado] || 'bg-purple-100 text-purple-800 border-purple-300';
  };

  return ReactDOM.createPortal(
    <div className="fixed inset-0 overflow-y-auto" style={{ zIndex: 100000 }}>
      {/* Overlay */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-70 transition-opacity"
        onClick={onClose}
        style={{ zIndex: 100000 }}
      ></div>

      {/* Modal */}
      <div className="flex min-h-screen items-center justify-center p-4" style={{ position: 'relative', zIndex: 100001 }}>
        <div className="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
          {/* Header */}
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-2xl font-bold flex items-center gap-2">
                  <FaUser />
                  Vista Previa de Conductores
                </h2>
                <p className="text-orange-100 text-sm mt-1">
                  Revisa los conductores que serán contactados antes de confirmar
                </p>
              </div>
              <button
                onClick={onClose}
                className="text-white hover:bg-white/20 rounded-full p-2 transition-colors"
              >
                <FaTimes className="text-xl" />
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="px-6 py-4 max-h-[calc(90vh-200px)] overflow-y-auto">
            {isLoading ? (
              <div className="space-y-4">
                <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <p className="text-orange-900 font-semibold text-lg flex items-center gap-2">
                        <FaSearch className="text-orange-500" />
                        Buscando conductores en tiempo real
                      </p>
                      <p className="text-orange-700 text-sm mt-1">
                        La búsqueda sigue en curso. El tiempo real no cambia, pero ahora puedes ver el avance del proceso.
                      </p>
                    </div>
                    <div className="flex items-center gap-2 bg-white text-orange-700 border border-orange-200 rounded-full px-3 py-1 text-sm font-semibold">
                      <FaClock />
                      {elapsedSeconds}s
                    </div>
                  </div>
                </div>

                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <h3 className="font-semibold text-blue-900 mb-3 flex items-center gap-2">
                    <FaMapMarkerAlt />
                    Consulta actual
                  </h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                      <span className="text-blue-600 font-medium">Origen:</span>
                      <p className="text-gray-800">{cotizacionInfo?.ciudad_origen || 'N/A'}</p>
                    </div>
                    <div>
                      <span className="text-blue-600 font-medium">Destino:</span>
                      <p className="text-gray-800">{cotizacionInfo?.ciudad_destino || 'N/A'}</p>
                    </div>
                    <div>
                      <span className="text-blue-600 font-medium">Tipo Vehículo:</span>
                      <p className="text-gray-800">{cotizacionInfo?.vehicle_type || 'N/A'}</p>
                    </div>
                  </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <div className="space-y-3">
                    {loadingSteps.map((step, index) => {
                      const isCompleted = index < activeStepIndex;
                      const isActive = index === activeStepIndex;

                      return (
                        <div
                          key={step}
                          className={`flex items-center gap-3 rounded-lg border px-3 py-3 transition-colors ${
                            isCompleted
                              ? 'border-green-200 bg-green-50'
                              : isActive
                                ? 'border-orange-200 bg-orange-50'
                                : 'border-gray-200 bg-gray-50'
                          }`}
                        >
                          <div className="flex-shrink-0">
                            {isCompleted ? (
                              <FaCheckCircle className="text-green-600" />
                            ) : isActive ? (
                              <FaCircleNotch className="text-orange-500 animate-spin" />
                            ) : (
                              <div className="w-4 h-4 rounded-full border-2 border-gray-300" />
                            )}
                          </div>
                          <div className="flex-1">
                            <p className={`text-sm font-medium ${isCompleted ? 'text-green-800' : isActive ? 'text-orange-800' : 'text-gray-500'}`}>
                              {step}
                            </p>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {[1, 2, 3, 4].map((item) => (
                    <div key={item} className="border border-gray-200 rounded-lg p-4 bg-gray-50 animate-pulse">
                      <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-gray-200" />
                        <div className="flex-1 space-y-2">
                          <div className="h-4 bg-gray-200 rounded w-3/4" />
                          <div className="h-3 bg-gray-200 rounded w-1/2" />
                        </div>
                      </div>
                      <div className="space-y-2">
                        <div className="h-3 bg-gray-200 rounded w-full" />
                        <div className="h-3 bg-gray-200 rounded w-2/3" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : errorMessage ? (
              <div className="space-y-4">
                <div className="bg-red-50 border border-red-200 rounded-lg p-5">
                  <div className="flex items-start gap-3">
                    <FaExclamationTriangle className="text-red-500 text-xl mt-0.5" />
                    <div>
                      <h3 className="text-red-900 font-semibold">No se pudo completar la búsqueda</h3>
                      <p className="text-red-700 text-sm mt-1">{errorMessage}</p>
                    </div>
                  </div>
                </div>

                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <h3 className="font-semibold text-blue-900 mb-2">Consulta solicitada</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                      <span className="text-blue-600 font-medium">Origen:</span>
                      <p className="text-gray-800">{cotizacionInfo?.ciudad_origen || 'N/A'}</p>
                    </div>
                    <div>
                      <span className="text-blue-600 font-medium">Destino:</span>
                      <p className="text-gray-800">{cotizacionInfo?.ciudad_destino || 'N/A'}</p>
                    </div>
                    <div>
                      <span className="text-blue-600 font-medium">Tipo Vehículo:</span>
                      <p className="text-gray-800">{cotizacionInfo?.vehicle_type || 'N/A'}</p>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <>
            {/* Información de la cotización */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <h3 className="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                <FaMapMarkerAlt />
                Información del Servicio
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div>
                  <span className="text-blue-600 font-medium">Origen:</span>
                  <p className="text-gray-800">{cotizacionInfo?.ciudad_origen || 'N/A'}</p>
                </div>
                <div>
                  <span className="text-blue-600 font-medium">Destino:</span>
                  <p className="text-gray-800">{cotizacionInfo?.ciudad_destino || 'N/A'}</p>
                </div>
                <div>
                  <span className="text-blue-600 font-medium">Tipo Vehículo:</span>
                  <p className="text-gray-800">{cotizacionInfo?.vehicle_type || 'N/A'}</p>
                </div>
              </div>
            </div>

            {/* Resumen */}
            <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-green-900 font-semibold text-lg">
                    Total de conductores a llamar: <span className="text-2xl">{totalDrivers}</span>
                  </p>
                  <p className="text-green-700 text-sm mt-1">
                    Se registrarán llamadas para todos estos conductores disponibles
                  </p>
                </div>
                <FaCheckCircle className="text-green-500 text-4xl" />
              </div>
            </div>

            {/* Alerta de ya llamados */}
            {yaLlamados.length > 0 && (
              <div className="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-4">
                <div className="flex items-center gap-2 mb-1">
                  <FaPhoneSlash className="text-amber-600" />
                  <p className="text-amber-900 font-semibold">
                    {yaLlamados.length} conductor{yaLlamados.length > 1 ? 'es' : ''} ya fue{yaLlamados.length > 1 ? 'ron' : ''} llamado{yaLlamados.length > 1 ? 's' : ''} previamente
                  </p>
                </div>
                <p className="text-amber-700 text-sm">
                  Estos conductores ya tienen un registro de llamada para esta cotización.
                </p>
              </div>
            )}

            {/* Lista de conductores */}
            <div>
              <h3 className="font-semibold text-gray-800 mb-3 text-lg">
                Conductores Disponibles ({drivers?.length || 0})
              </h3>
              
              {drivers && drivers.length > 0 ? (
                <div className="space-y-2 max-h-[400px] overflow-y-auto pr-2">
                  {drivers.slice(0, 50).map((driver, index) => (
                    <div 
                      key={driver.id || index}
                      className={`border rounded-lg p-4 hover:shadow-md transition-shadow ${
                        driver.ya_llamado 
                          ? 'bg-amber-50 border-amber-300' 
                          : 'bg-white border-gray-200'
                      }`}
                    >
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-2">
                            <div className={`p-2 rounded-full ${driver.ya_llamado ? 'bg-amber-100' : 'bg-orange-100'}`}>
                              <FaUser className={driver.ya_llamado ? 'text-amber-600' : 'text-orange-600'} />
                            </div>
                            <div className="flex-1">
                              <div className="flex items-center gap-2 flex-wrap">
                                <h4 className="font-semibold text-gray-800">
                                  {driver.conductor || driver.nombre || 'Sin nombre'}
                                </h4>
                                {driver.ya_llamado && (
                                  <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border font-medium ${estadoColor(driver.estado_llamada)}`}>
                                    <FaPhone className="text-[10px]" />
                                    {estadoLabel(driver.estado_llamada)}
                                  </span>
                                )}
                              </div>
                              <p className="text-xs text-gray-500">
                                {driver.placa ? `Placa: ${driver.placa}` : `ID: ${driver.id || 'N/A'}`}
                                {driver.ya_llamado && driver.fecha_llamada && (
                                  <span className="ml-2 text-amber-600">· Llamado el {driver.fecha_llamada}</span>
                                )}
                              </p>
                            </div>
                          </div>
                          
                          <div className="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                            <div className="flex items-center gap-2 text-gray-600">
                              <FaPhone className="text-green-600" />
                              <span>{driver.telefono || 'Sin teléfono'}</span>
                            </div>
                            <div className="flex items-center gap-2 text-gray-600">
                              <FaTruck className="text-blue-600" />
                              <span>{driver.carroceria || driver.clase_vehiculo || driver.tipo_vehiculo_arcangel || 'N/A'}</span>
                            </div>
                            <div className="text-gray-600">
                              <span className="font-medium">Score:</span> {driver.score || 'N/A'}
                            </div>
                          </div>

                          <div className="mt-2 flex flex-wrap gap-2">
                            {(driver.tipo_vehiculo_arcangel || driver.clase_vehiculo || driver.tipo_vehiculo) && (
                              <span className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                Arcángel: {driver.tipo_vehiculo_arcangel || driver.clase_vehiculo || driver.tipo_vehiculo}
                              </span>
                            )}
                            {driver.clase_vehiculo && driver.tipo_vehiculo_arcangel && driver.clase_vehiculo !== driver.tipo_vehiculo_arcangel && (
                              <span className="inline-block bg-slate-100 text-slate-700 text-xs px-2 py-1 rounded">
                                Clase local: {driver.clase_vehiculo}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                  
                  {drivers.length > 50 && (
                    <div className="text-center py-3 bg-gray-50 rounded-lg">
                      <p className="text-sm text-gray-600">
                        Mostrando primeros 50 conductores de {drivers.length} totales
                      </p>
                    </div>
                  )}
                </div>
              ) : (
                <div className="space-y-3">
                  <div className="text-center py-8 bg-gray-50 rounded-lg">
                    <p className="text-gray-500">No se encontraron conductores disponibles</p>
                  </div>

                  {searchMeta && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                      <p className="font-semibold">Por que aqui ves 0 y en "Buscar por Ciudad" puedes ver vehiculos:</p>
                      <p className="mt-2">
                        Esta ventana no muestra todos los vehiculos de la ciudad. Aqui solo se listan los que cumplen los filtros de la cotizacion.
                      </p>
                      <div className="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Ciudad consultada:</span> {searchMeta.city}
                        </div>
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Vehiculo solicitado:</span> {searchMeta.vehicle_requested}
                        </div>
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Variantes buscadas:</span> {(searchMeta.vehicle_variants || []).join(', ') || 'N/A'}
                        </div>
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Score minimo:</span> {searchMeta.min_score}
                        </div>
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Vehiculos totales en ciudad:</span> {searchMeta.city_total}
                        </div>
                        <div className="rounded bg-white px-3 py-2 border border-amber-100">
                          <span className="font-medium">Coincidencias filtradas:</span> {searchMeta.filtered_total}
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
              </>
            )}
          </div>

          {/* Footer */}
          <div className="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div className="flex flex-col md:flex-row gap-3 justify-end">
              <button
                onClick={onClose}
                className="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors"
              >
                Cancelar
              </button>
              {errorMessage ? (
                <button
                  onClick={onRetry}
                  className="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors shadow-lg"
                >
                  Reintentar Búsqueda
                </button>
              ) : (
                <button
                  onClick={onConfirm}
                  disabled={isLoading || !drivers || drivers.length === 0}
                  className="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
                >
                  {isLoading
                    ? 'Buscando conductores...'
                    : `Confirmar y Realizar Llamada (${totalDrivers} conductores)`}
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>,
    portalTarget
  );
}
