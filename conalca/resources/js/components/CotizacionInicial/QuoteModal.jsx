// resources/js/components/CotizacionInicial/QuoteModal.jsx
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';

const QuoteModal = ({ onClose, quoteId, quoteData: initialQuoteData }) => {
  const [quoteData, setQuoteData] = useState(initialQuoteData || null);
  const [loading, setLoading] = useState(!initialQuoteData);
  const [activeTab, setActiveTab] = useState('details');
  const [showExportOptions, setShowExportOptions] = useState(false);

  // Simular carga de datos si no se proporcionan
  useEffect(() => {
    if (!initialQuoteData && quoteId) {
      const timer = setTimeout(() => {
        // Simular datos de cotización cargados
        setQuoteData({
          id: quoteId,
          clientName: 'Cliente Ejemplo',
          clientDocument: '123456789',
          clientEmail: 'cliente@ejemplo.com',
          createdAt: new Date().toISOString(),
          status: 'sent',
          total: 2500000,
          routes: [
            {
              id: 1,
              ciudad_origen: 'Bogotá',
              ciudad_destino: 'Medellín',
              vehiculo_requerido: 'Camión 5 Toneladas',
              finalValue: 1250000,
              porcentaje: 20
            },
            {
              id: 2,
              ciudad_origen: 'Medellín',
              ciudad_destino: 'Cali',
              vehiculo_requerido: 'Tractocamión',
              finalValue: 1250000,
              porcentaje: 18
            }
          ]
        });
        setLoading(false);
      }, 1500);

      return () => clearTimeout(timer);
    }
  }, [quoteId, initialQuoteData]);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'sent':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'accepted':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'rejected':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case 'sent':
        return 'Enviada';
      case 'pending':
        return 'Pendiente';
      case 'accepted':
        return 'Aceptada';
      case 'rejected':
        return 'Rechazada';
      default:
        return 'Desconocido';
    }
  };

  const handleExport = (format) => {
    console.log(`Exportando cotización en formato: ${format}`);
    setShowExportOptions(false);
    // Aquí iría la lógica de exportación
  };

  const handleResend = () => {
    console.log('Reenviando cotización...');
    // Aquí iría la lógica de reenvío
  };

  const handleDuplicate = () => {
    console.log('Duplicando cotización...');
    onClose();
    // Aquí se abriría el formulario con los datos precargados
  };

  if (loading) {
    return (
      <Modal onClose={onClose} size="large">
        <div className="bg-white">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
            <h2 className="text-lg font-semibold">Cargando Cotización...</h2>
          </div>
          <div className="p-8 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
              <svg className="w-8 h-8 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
            </div>
            <p className="text-gray-600">Cargando información de la cotización...</p>
          </div>
        </div>
      </Modal>
    );
  }

  if (!quoteData) {
    return (
      <Modal onClose={onClose} size="medium">
        <div className="bg-white">
          <div className="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 text-white">
            <h2 className="text-lg font-semibold">Error</h2>
          </div>
          <div className="p-8 text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
              <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Cotización no encontrada</h3>
            <p className="text-gray-600 mb-4">No se pudo cargar la información de la cotización.</p>
            <button
              onClick={onClose}
              className="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
            >
              Cerrar
            </button>
          </div>
        </div>
      </Modal>
    );
  }

  return (
    <Modal onClose={onClose} size="extra-large">
      <style jsx>{`
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        .inter-font { font-family: 'Inter', sans-serif; }
        .scrollbar-custom::-webkit-scrollbar { width: 4px; }
        .scrollbar-custom::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
        .scrollbar-custom::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 2px; }
        .scrollbar-custom::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
      `}</style>

      <div className="bg-white">
        {/* Header del Modal */}
        <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <div>
                <h2 className="text-lg font-semibold">Cotización #{quoteData.id}</h2>
                <p className="text-orange-100 text-sm">{quoteData.clientName}</p>
              </div>
            </div>
            <div className="flex items-center space-x-2">
              <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(quoteData.status)}`}>
                {getStatusText(quoteData.status)}
              </span>
              
              {/* Menú de Exportación */}
              <div className="relative">
                <button
                  onClick={() => setShowExportOptions(!showExportOptions)}
                  className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-orange-600 bg-white hover:bg-gray-50 transition-colors duration-200 shadow-sm"
                >
                  <svg className="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Exportar
                </button>
                
                {showExportOptions && (
                  <div className="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                    <div className="py-1">
                      <button
                        onClick={() => handleExport('pdf')}
                        className="flex items-center w-full px-4 py-2 text-xs text-gray-700 hover:bg-gray-100"
                      >
                        <svg className="w-3 h-3 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clipRule="evenodd"></path>
                        </svg>
                        Exportar PDF
                      </button>
                      <button
                        onClick={() => handleExport('excel')}
                        className="flex items-center w-full px-4 py-2 text-xs text-gray-700 hover:bg-gray-100"
                      >
                        <svg className="w-3 h-3 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clipRule="evenodd"></path>
                        </svg>
                        Exportar Excel
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Tabs de Navegación */}
        <div className="bg-gray-50 border-b border-gray-200">
          <div className="px-6">
            <nav className="flex space-x-8">
              <button
                onClick={() => setActiveTab('details')}
                className={`py-3 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'details'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Detalles
              </button>
              <button
                onClick={() => setActiveTab('routes')}
                className={`py-3 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'routes'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Rutas y Precios
              </button>
              <button
                onClick={() => setActiveTab('history')}
                className={`py-3 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'history'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Historial
              </button>
            </nav>
          </div>
        </div>

        {/* Contenido de las Tabs */}
        <div className="overflow-y-auto scrollbar-custom" style={{ maxHeight: '70vh' }}>
          {activeTab === 'details' && (
            <div className="p-6">
              {/* Información General */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <h3 className="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                    <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Información del Cliente
                  </h3>
                  <div className="space-y-3 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Nombre:</span>
                      <span className="font-medium text-gray-800">{quoteData.clientName}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Documento:</span>
                      <span className="font-medium text-gray-800">{quoteData.clientDocument}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Email:</span>
                      <span className="font-medium text-gray-800">{quoteData.clientEmail}</span>
                    </div>
                  </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <h3 className="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                    <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Información de la Cotización
                  </h3>
                  <div className="space-y-3 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Fecha de creación:</span>
                      <span className="font-medium text-gray-800">{formatDate(quoteData.createdAt)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Total de rutas:</span>
                      <span className="font-medium text-gray-800">{quoteData.routes?.length || 0}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Valor total:</span>
                      <span className="font-bold text-green-600 text-lg">
                        ${Number(quoteData.total || 0).toLocaleString()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Resumen de Rutas */}
              <div className="bg-white border border-gray-200 rounded-lg p-4">
                <h3 className="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                  <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                  </svg>
                  Resumen de Rutas
                </h3>
                <div className="space-y-2">
                  {quoteData.routes?.map((route, index) => (
                    <div key={route.id || index} className="bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                      <div className="flex-1">
                        <div className="text-sm font-medium text-gray-800">
                          {route.ciudad_origen} → {route.ciudad_destino}
                        </div>
                        <div className="text-xs text-gray-600">
                          {route.vehiculo_requerido} • Margen: {route.porcentaje}%
                        </div>
                      </div>
                      <div className="text-sm font-bold text-green-600">
                        ${Number(route.finalValue || 0).toLocaleString()}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {activeTab === 'routes' && (
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="w-full border-collapse border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                  <thead>
                    <tr className="bg-orange-100 text-gray-800 font-semibold">
                      <th className="border border-gray-200 px-4 py-3 text-left text-sm">#</th>
                      <th className="border border-gray-200 px-4 py-3 text-left text-sm">Origen</th>
                      <th className="border border-gray-200 px-4 py-3 text-left text-sm">Destino</th>
                      <th className="border border-gray-200 px-4 py-3 text-left text-sm">Vehículo</th>
                      <th className="border border-gray-200 px-4 py-3 text-center text-sm">Margen</th>
                      <th className="border border-gray-200 px-4 py-3 text-right text-sm">Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    {quoteData.routes?.map((route, index) => (
                      <tr key={route.id || index} className="bg-white hover:bg-orange-50 transition-colors duration-150">
                        <td className="border border-gray-200 px-4 py-3 text-sm font-medium">{index + 1}</td>
                        <td className="border border-gray-200 px-4 py-3 text-sm">{route.ciudad_origen || '-'}</td>
                        <td className="border border-gray-200 px-4 py-3 text-sm">{route.ciudad_destino || '-'}</td>
                        <td className="border border-gray-200 px-4 py-3 text-sm">{route.vehiculo_requerido || '-'}</td>
                        <td className="border border-gray-200 px-4 py-3 text-center text-sm">
                          <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {route.porcentaje || 0}%
                          </span>
                        </td>
                        <td className="border border-gray-200 px-4 py-3 text-right text-sm font-bold text-green-700">
                          ${Number(route.finalValue || 0).toLocaleString()}
                        </td>
                      </tr>
                    ))}
                    <tr className="bg-green-50 font-bold">
                      <td colSpan="5" className="border border-gray-200 px-4 py-3 text-right text-sm">Total:</td>
                      <td className="border border-gray-200 px-4 py-3 text-right text-lg text-green-700">
                        ${Number(quoteData.total || 0).toLocaleString()}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {activeTab === 'history' && (
            <div className="p-6">
              <div className="space-y-4">
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <div className="flex items-start space-x-3">
                    <div className="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                    <div className="flex-1">
                      <div className="flex justify-between items-start">
                        <div>
                          <h4 className="text-sm font-medium text-gray-800">Cotización enviada</h4>
                          <p className="text-xs text-gray-600">La cotización fue enviada exitosamente al cliente</p>
                        </div>
                        <span className="text-xs text-gray-500">{formatDate(quoteData.createdAt)}</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <div className="flex items-start space-x-3">
                    <div className="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                    <div className="flex-1">
                      <div className="flex justify-between items-start">
                        <div>
                          <h4 className="text-sm font-medium text-gray-800">Cotización creada</h4>
                          <p className="text-xs text-gray-600">Se creó la cotización con {quoteData.routes?.length || 0} rutas</p>
                        </div>
                        <span className="text-xs text-gray-500">{formatDate(quoteData.createdAt)}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer con Acciones */}
        <div className="bg-gray-50 px-6 py-4 border-t border-gray-200">
          <div className="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
            <div className="text-xs text-gray-500">
              Última actualización: {formatDate(quoteData.createdAt)}
            </div>
            <div className="flex space-x-3">
              <button
                onClick={handleResend}
                className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors duration-200 flex items-center"
              >
                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Reenviar
              </button>
              <button
                onClick={handleDuplicate}
                className="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors duration-200 flex items-center"
              >
                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Duplicar
              </button>
              <button
                onClick={onClose}
                className="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors duration-200"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
};

QuoteModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  quoteId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  quoteData: PropTypes.object
};

export default QuoteModal;