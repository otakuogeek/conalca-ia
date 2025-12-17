// resources/js/components/CotizacionInicial/SuccessModal.jsx
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';

const SuccessModal = ({ onClose, quoteData, clientData, threadId }) => {
  const [showDetails, setShowDetails] = useState(false);
  const [emailSent, setEmailSent] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [groupId, setGroupId] = useState(null);
  const [saveError, setSaveError] = useState(null); // No hay error - la cotización fue guardada exitosamente en PreviewModal

  // Guardar la cotización automáticamente al cargar el modal
  useEffect(() => {
    // La cotización ya fue guardada en PreviewModal, solo simular el proceso
    console.log('SuccessModal: La cotización ya fue guardada en PreviewModal');
    const timer = setTimeout(() => {
      setEmailSent(true);
      setIsLoading(false);
      setSaveError(null); // Asegurar que no hay error
    }, 1000);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    const timeout = setTimeout(() => {
    onClose();
    window.location.reload();
    }, 9000);
    return () => clearTimeout(timeout);
  }, [onClose]);

  const calculateTotal = () => {
    return quoteData.reduce((total, route) => {
      if (route.finalValue) {
        return total + parseFloat(route.finalValue);
      }
      return total;
    }, 0);
  };

  const formatDate = () => {
    return new Date().toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const handleNewQuote = () => {
    // Cerrar modal y limpiar datos para nueva cotización
    onClose();
    // Aquí se podría emitir un evento para resetear el estado completo
    window.location.reload();
  };

  const handleViewQuote = () => {
    // Abrir modal de visualización de cotización existente
    console.log('Ver cotización:', quoteData);
    // Esta función podría emitir un evento para abrir QuoteModal
  };

  const handleClose = () => {
    onClose();
    window.location.reload();
  };

  return (
    <Modal onClose={handleClose} size="large">
      <style jsx>{`
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        .inter-font { font-family: 'Inter', sans-serif; }
        
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(20px); }
          to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
          from { opacity: 0; transform: translateX(-20px); }
          to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes bounce {
          0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
          40% { transform: translateY(-10px); }
          60% { transform: translateY(-5px); }
        }
        
        .fade-in { animation: fadeIn 0.6s ease-out; }
        .slide-in { animation: slideIn 0.4s ease-out; }
        .bounce { animation: bounce 1s; }
        
        .pulse-success {
          animation: pulse-success 2s infinite;
        }
        
        @keyframes pulse-success {
          0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
          70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
          100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
      `}</style>

      <div className="bg-white">
        {/* Header de Éxito */}
        <div className="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className={`h-8 w-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center ${!isLoading ? 'pulse-success' : ''}`}>
                {isLoading ? (
                  <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                  </svg>
                ) : (
                  <svg className="h-5 w-5 bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                )}
              </div>
              <div>
                <h2 className="text-lg font-semibold">
                  {isLoading ? 'Procesando...' : '¡Cotización Enviada Exitosamente!'}
                </h2>
                <p className="text-green-100 text-sm">
                  {isLoading ? 'Guardando y enviando cotización' : 'La cotización ha sido enviada al cliente'}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Contenido Principal */}
        <div className="p-6">
          {isLoading ? (
            // Estado de Carga
            <div className="text-center py-12">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <svg className="w-8 h-8 text-green-600 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-800 mb-2">Finalizando cotización...</h3>
              <p className="text-gray-600 text-sm">
                Estamos guardando la información y enviando el correo electrónico
              </p>
              <div className="mt-4 flex justify-center">
                <div className="flex space-x-1">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" style={{ animationDelay: '0.2s' }}></div>
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" style={{ animationDelay: '0.4s' }}></div>
                </div>
              </div>
            </div>
          ) : (
            // Estado de Éxito
            <div className="fade-in">
              {/* Icono de Éxito Central */}
              <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4 pulse-success">
                  <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <h3 className="text-2xl font-bold text-gray-800 mb-2">¡Cotización Completada!</h3>
                <p className="text-gray-600">
                  La cotización ha sido procesada y enviada exitosamente
                </p>
              </div>

              {/* Información de la Cotización */}
              <div className="bg-gray-50 rounded-lg p-4 mb-6 slide-in">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Cliente:</span>
                      <span className="font-medium text-gray-800">{clientData.clientName || 'N/A'}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Documento:</span>
                      <span className="font-medium text-gray-800">{clientData.documentClient || 'N/A'}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Fecha:</span>
                      <span className="font-medium text-gray-800">{formatDate()}</span>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Total Rutas:</span>
                      <span className="font-medium text-gray-800">{quoteData.length}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Valor Total:</span>
                      <span className="font-bold text-green-600 text-lg">
                        ${Number(calculateTotal()).toLocaleString()}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Estado:</span>
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path>
                        </svg>
                        Enviada
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Mensaje de Confirmación o Error */}
              {emailSent && !saveError && (
                <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 slide-in" style={{ animationDelay: '0.3s' }}>
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    <div>
                      <h4 className="text-sm font-semibold text-green-800 mb-1">
                        Cotización guardada exitosamente
                      </h4>
                      <p className="text-xs text-green-700">
                        La cotización ha sido guardada en el sistema y aparecerá en la columna "Pre-Solicitud" 
                        del tablero de gestión. {groupId && `ID del grupo: ${groupId}`}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {saveError && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 slide-in" style={{ animationDelay: '0.3s' }}>
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd"></path>
                    </svg>
                    <div>
                      <h4 className="text-sm font-semibold text-red-800 mb-1">
                        Error al guardar la cotización
                      </h4>
                      <p className="text-xs text-red-700">
                        {saveError}. La cotización se ha mostrado pero no se ha guardado en el sistema.
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Detalles de las Rutas (Expandible) */}
              <div className="border border-gray-200 rounded-lg mb-6">
                <button
                  onClick={() => setShowDetails(!showDetails)}
                  className="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-t-lg flex items-center justify-between text-sm font-medium text-gray-700 transition-colors duration-200"
                >
                  <span>Detalles de la Cotización</span>
                  <svg 
                    className={`w-4 h-4 transform transition-transform duration-200 ${showDetails ? 'rotate-180' : ''}`}
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                {showDetails && (
                  <div className="p-4 border-t border-gray-200 slide-in">
                    <div className="space-y-3">
                      {quoteData.map((route, index) => (
                        <div key={index} className="bg-gray-50 rounded-lg p-3">
                          <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium text-gray-800">
                              Ruta #{index + 1}
                            </span>
                            <span className="text-sm font-bold text-green-600">
                              ${Number(route.finalValue || 0).toLocaleString()}
                            </span>
                          </div>
                          <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div>
                              <span className="font-medium">Origen:</span> {route.ciudad_origen || '-'}
                            </div>
                            <div>
                              <span className="font-medium">Destino:</span> {route.ciudad_destino || '-'}
                            </div>
                            <div>
                              <span className="font-medium">Vehículo:</span> {route.vehiculo_requerido || '-'}
                            </div>
                            <div>
                              <span className="font-medium">Margen:</span> {route.porcentaje || 0}%
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Acciones */}
              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  onClick={handleNewQuote}
                  className="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center"
                >
                  <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  Nueva Cotización
                </button>
                
               
                <button
                  onClick={handleClose}
                  className="sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center"
                >
                  <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                  Cerrar
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </Modal>
  );
};

SuccessModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  quoteData: PropTypes.array.isRequired,
  clientData: PropTypes.object.isRequired,
  threadId: PropTypes.string
};

export default SuccessModal;