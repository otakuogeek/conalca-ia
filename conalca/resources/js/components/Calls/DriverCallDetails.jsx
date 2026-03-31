import React, { useEffect, useState } from 'react';
import { FaPhone, FaUser, FaTruck, FaMapMarkerAlt, FaClock, FaCheckCircle, FaTimesCircle } from 'react-icons/fa';

export default function DriverCallDetails({ driverId }) {
  const [loading, setLoading] = useState(true);
  const [driver, setDriver] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const loadDriverDetails = async () => {
      try {
        const response = await fetch(`/api/conductor-details/${driverId}`, {
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        
        if (!response.ok) {
          throw new Error('Error al cargar detalles del conductor');
        }
        
        const data = await response.json();
        setDriver(data);
      } catch (e) {
        console.error('Error cargando detalles:', e);
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };

    if (driverId) {
      loadDriverDetails();
    }
  }, [driverId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-orange-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Cargando información del conductor...</p>
        </div>
      </div>
    );
  }

  if (error || !driver) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center">
        <div className="text-center bg-white p-8 rounded-lg shadow-lg">
          <FaTimesCircle className="text-red-600 text-6xl mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-red-600 mb-2">Error</h2>
          <p className="text-gray-600">{error || 'No se pudo cargar la información del conductor'}</p>
        </div>
      </div>
    );
  }

  const estadoColor = driver.estado_llamada === 'completed' ? 'green' : 
                       driver.estado_llamada === 'pending' ? 'yellow' : 
                       driver.estado_llamada === 'failed' ? 'red' : 'gray';
  
  const esAceptado = driver.respuesta_llamada?.toLowerCase().includes('acepta');

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100 py-8 px-4">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-4">
              <div className="bg-orange-100 p-4 rounded-full">
                <FaUser className="text-orange-600 text-3xl" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-800">{driver.nombre_conductor || 'Sin nombre'}</h1>
                <p className="text-gray-500 text-sm">Información de llamada</p>
              </div>
            </div>
            {esAceptado ? (
              <div className="bg-green-100 px-4 py-2 rounded-full flex items-center gap-2">
                <FaCheckCircle className="text-green-600" />
                <span className="text-green-700 font-semibold">Aceptó</span>
              </div>
            ) : (
              <div className="bg-red-100 px-4 py-2 rounded-full flex items-center gap-2">
                <FaTimesCircle className="text-red-600" />
                <span className="text-red-700 font-semibold">No aceptó</span>
              </div>
            )}
          </div>
        </div>

        {/* Información de contacto */}
        <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <FaPhone className="text-orange-600" />
            Información de Contacto
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-500 mb-1">Teléfono</p>
              <p className="text-lg font-semibold text-gray-800">
                {driver.telefono || 'No disponible'}
              </p>
            </div>
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-500 mb-1">Estado de Llamada</p>
              <span className={`inline-block px-3 py-1 rounded-full text-sm font-semibold bg-${estadoColor}-100 text-${estadoColor}-700`}>
                {driver.estado_llamada || 'Sin estado'}
              </span>
            </div>
          </div>
        </div>

        {/* Información del vehículo */}
        <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <FaTruck className="text-orange-600" />
            Información del Vehículo
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-500 mb-1">Placa</p>
              <p className="text-lg font-semibold text-gray-800 uppercase">
                {driver.placa || 'Sin placa'}
              </p>
            </div>
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-500 mb-1">Tipo de Vehículo</p>
              <p className="text-lg font-semibold text-gray-800">
                {driver.tipo_vehiculo || 'No especificado'}
              </p>
            </div>
          </div>
        </div>

        {/* Información de la ruta */}
        <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <FaMapMarkerAlt className="text-orange-600" />
            Información de Ruta
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <p className="text-sm text-blue-600 mb-1">Ciudad de Origen</p>
              <p className="text-lg font-semibold text-gray-800">
                {driver.ciudad_origen || 'No especificada'}
              </p>
            </div>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-green-600 mb-1">Ciudad de Destino</p>
              <p className="text-lg font-semibold text-gray-800">
                {driver.ciudad_destino || 'No especificada'}
              </p>
            </div>
          </div>
        </div>

        {/* Detalles de la llamada */}
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <FaClock className="text-orange-600" />
            Detalles de la Llamada
          </h2>
          <div className="space-y-4">
            {driver.fecha_llamada && (
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-500 mb-1">Fecha y Hora de Llamada</p>
                <p className="text-lg font-semibold text-gray-800">
                  {new Date(driver.fecha_llamada).toLocaleString('es-CO', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                  })}
                </p>
              </div>
            )}
            {driver.respuesta_llamada && (
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-500 mb-1">Respuesta de la Llamada</p>
                <p className="text-base text-gray-800 whitespace-pre-wrap">
                  {driver.respuesta_llamada}
                </p>
              </div>
            )}
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-500 mb-1">ID de Cotización</p>
              <p className="text-lg font-semibold text-gray-800">
                #{driver.cotizacion_id}
              </p>
            </div>
          </div>
        </div>

        {/* Botón para cerrar ventana */}
        <div className="mt-6 text-center">
          <button
            onClick={() => window.close()}
            className="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg shadow-lg transition-colors duration-200"
          >
            Cerrar Ventana
          </button>
        </div>
      </div>
    </div>
  );
}
