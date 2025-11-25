// resources/js/components/CotizacionInicial/DriversModal.jsx
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';

const DriversModal = ({ isOpen, onClose, cotizacionId, cotizacionData }) => {
  const [loading, setLoading] = useState(false);
  const [drivers, setDrivers] = useState([]);
  const [filtros, setFiltros] = useState({});
  const [error, setError] = useState(null);
  const [total, setTotal] = useState(0);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastUpdate, setLastUpdate] = useState(null);

  // Buscar conductores cuando se abre el modal
  useEffect(() => {
    if (isOpen && cotizacionId) {
      buscarConductores();
    }
  }, [isOpen, cotizacionId]);

  // Auto-refresh cada 30 segundos
  useEffect(() => {
    if (!isOpen || !cotizacionId || !autoRefresh) return;

    const interval = setInterval(() => {
      buscarConductores(true); // true = silent refresh (no loading state)
    }, 30000); // 30 segundos

    return () => clearInterval(interval);
  }, [isOpen, cotizacionId, autoRefresh]);

  const buscarConductores = async (silent = false) => {
    if (!silent) {
      setLoading(true);
    }
    setError(null);
    
    try {
      const response = await fetch('/api/arcangel/buscar-conductores', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          cotizacion_id: cotizacionId,
          min_score: 0,
          limit: 50
        })
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Error al buscar conductores');
      }

      if (result.success) {
        setDrivers(result.data.conductores || []);
        setFiltros(result.data.filtros || {});
        setTotal(result.data.total || 0);
        setLastUpdate(new Date());
      } else {
        throw new Error(result.message || 'No se pudieron cargar los conductores');
      }

    } catch (err) {
      console.error('Error buscando conductores:', err);
      if (!silent) {
        setError(err.message);
        setDrivers([]);
      }
    } finally {
      if (!silent) {
        setLoading(false);
      }
    }
  };

  const handleRegistrarLlamada = async (driver) => {
    try {
      setLoading(true);
      
      const response = await fetch('/api/arcangel/llamar-conductor', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          cotizacion_id: cotizacionId,
          conductor_nombre: driver.conductor,
          telefono: driver.telefono,
          placa: driver.placa
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert(`✅ Llamada iniciada exitosamente a ${driver.conductor}\n\nTeléfono: ${result.data.telefono}\nConversation ID: ${result.data.conversation_id || 'N/A'}`);
      } else {
        alert(`❌ Error al iniciar llamada: ${result.message}`);
      }
      
    } catch (error) {
      console.error('Error al iniciar llamada:', error);
      alert(`❌ Error al iniciar llamada: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
          <div>
            <h2 className="text-2xl font-bold text-white flex items-center gap-2">
              <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              CONDUCTORES DISPONIBLES
            </h2>
            {cotizacionData && (
              <p className="text-purple-100 text-sm mt-1">
                {cotizacionData.ciudad_origen} → {cotizacionData.ciudad_destino} • {cotizacionData.vehiculo_requerido}
              </p>
            )}
          </div>
          <button
            onClick={onClose}
            className="text-white hover:text-purple-200 transition-colors"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Filtros aplicados */}
        {filtros && Object.keys(filtros).length > 0 && (
          <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <div className="flex items-center gap-4 text-sm flex-wrap">
              <span className="text-gray-600">Filtros:</span>
              <span className="bg-white px-3 py-1 rounded-full text-gray-700 border border-gray-300">
                📍 {filtros.ciudad}
              </span>
              <span className="bg-white px-3 py-1 rounded-full text-gray-700 border border-gray-300">
                🚛 {filtros.vehiculo}
              </span>
              {filtros.min_score > 0 && (
                <span className="bg-white px-3 py-1 rounded-full text-gray-700 border border-gray-300">
                  ⭐ Score mín: {filtros.min_score}/10
                </span>
              )}
              <div className="ml-auto flex items-center gap-3">
                {lastUpdate && (
                  <span className="text-xs text-gray-500">
                    Última actualización: {lastUpdate.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                  </span>
                )}
                <button
                  onClick={() => setAutoRefresh(!autoRefresh)}
                  className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                    autoRefresh 
                      ? 'bg-green-100 text-green-700 border border-green-300' 
                      : 'bg-gray-200 text-gray-600 border border-gray-300'
                  }`}
                  title={autoRefresh ? 'Auto-refresh activo (cada 30s)' : 'Auto-refresh pausado'}
                >
                  {autoRefresh ? '🔄 Auto-refresh' : '⏸️ Pausado'}
                </button>
                <button
                  onClick={() => buscarConductores(false)}
                  disabled={loading}
                  className="bg-purple-600 text-white px-3 py-1 rounded-full text-xs font-medium hover:bg-purple-700 transition-colors disabled:opacity-50"
                  title="Actualizar ahora"
                >
                  ♻️ Actualizar
                </button>
                <span className="font-semibold text-purple-600">
                  {total} {total === 1 ? 'conductor' : 'conductores'}
                </span>
              </div>
            </div>
          </div>
        )}

        {/* Body */}
        <div className="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
          {loading ? (
            <div className="flex flex-col items-center justify-center py-12">
              <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600 mb-4"></div>
              <p className="text-gray-600 text-lg">Buscando conductores en Arcángel...</p>
              <p className="text-gray-400 text-sm mt-2">Esto puede tomar unos segundos</p>
            </div>
          ) : error ? (
            <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
              <svg className="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 className="text-lg font-semibold text-red-800 mb-2">Error al cargar conductores</h3>
              <p className="text-red-600 mb-4">{error}</p>
              <button
                onClick={buscarConductores}
                className="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors"
              >
                Reintentar
              </button>
            </div>
          ) : drivers.length === 0 ? (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
              <svg className="w-20 h-20 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 className="text-xl font-semibold text-yellow-800 mb-2">No se encontraron conductores</h3>
              <p className="text-yellow-700 mb-4">
                No hay conductores disponibles en {filtros.ciudad} con vehículos tipo {filtros.vehiculo}
              </p>
              <div className="bg-white rounded-lg p-4 text-left mt-4">
                <p className="text-sm text-gray-600 font-semibold mb-2">💡 Sugerencias:</p>
                <ul className="text-sm text-gray-700 space-y-1 list-disc list-inside">
                  <li>Verifica que la ciudad y tipo de vehículo sean correctos</li>
                  <li>Intenta con un vehículo de otra clase</li>
                  <li>Busca en ciudades cercanas</li>
                </ul>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              {/* Tabla de conductores */}
              <div className="overflow-x-auto">
                <table className="w-full border-collapse">
                  <thead>
                    <tr className="bg-gradient-to-r from-purple-50 to-indigo-50">
                      <th className="text-left px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Conductor</th>
                      <th className="text-left px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Placa</th>
                      <th className="text-left px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Teléfono</th>
                      <th className="text-left px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Vehículo</th>
                      <th className="text-left px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Carrocería</th>
                      <th className="text-center px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Capacidad</th>
                      <th className="text-center px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Score</th>
                      <th className="text-center px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Estado</th>
                      <th className="text-center px-4 py-3 text-sm font-semibold text-gray-700 border-b-2 border-purple-200">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    {drivers.map((driver, index) => (
                      <tr
                        key={index}
                        className="border-b border-gray-200 hover:bg-purple-50 transition-colors"
                      >
                        <td className="px-4 py-3 text-sm text-gray-900 font-medium">
                          {driver.conductor}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-700 font-mono">
                          {driver.placa}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-700">
                          <a href={`tel:${driver.telefono}`} className="text-blue-600 hover:text-blue-800 hover:underline">
                            {driver.telefono}
                          </a>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-700">
                          {driver.clase_vehiculo}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {driver.carroceria}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-700 text-center">
                          {driver.capacidad ? `${driver.capacidad} kg` : '-'}
                        </td>
                        <td className="px-4 py-3 text-center">
                          <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${
                            driver.score >= 9 ? 'bg-green-100 text-green-800' :
                            driver.score >= 7 ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                          }`}>
                            ⭐ {driver.score.toFixed(1)}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-center">
                          {driver.disponible ? (
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                              ✓ Disponible
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                              ○ Ocupado
                            </span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-center">
                          <button
                            onClick={() => handleRegistrarLlamada(driver)}
                            disabled={!driver.disponible}
                            className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all ${
                              driver.disponible
                                ? 'bg-red-600 text-white hover:bg-red-700 hover:shadow-lg'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            }`}
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Llamar
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Resumen */}
              <div className="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 border border-purple-200">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-6">
                    <div className="text-center">
                      <p className="text-3xl font-bold text-purple-600">{total}</p>
                      <p className="text-xs text-gray-600">Conductores</p>
                    </div>
                    <div className="text-center">
                      <p className="text-3xl font-bold text-green-600">
                        {drivers.filter(d => d.disponible).length}
                      </p>
                      <p className="text-xs text-gray-600">Disponibles</p>
                    </div>
                    <div className="text-center">
                      <p className="text-3xl font-bold text-yellow-600">
                        {drivers.filter(d => d.score >= 8).length}
                      </p>
                      <p className="text-xs text-gray-600">Score ≥8</p>
                    </div>
                  </div>
                  <button
                    onClick={buscarConductores}
                    className="flex items-center gap-2 bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-50 border border-purple-200 transition-colors text-sm font-semibold"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Actualizar
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
          <p className="text-sm text-gray-600">
            🌐 Datos desde <span className="font-semibold text-purple-600">Arcángel API</span>
          </p>
          <button
            onClick={onClose}
            className="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors font-semibold"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  );
};

DriversModal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
  cotizacionId: PropTypes.number,
  cotizacionData: PropTypes.shape({
    id: PropTypes.number,
    ciudad_origen: PropTypes.string,
    ciudad_destino: PropTypes.string,
    vehiculo_requerido: PropTypes.string,
    peso_mercancia: PropTypes.string,
  }),
};

export default DriversModal;
