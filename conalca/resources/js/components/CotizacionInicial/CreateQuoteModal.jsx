// resources/js/components/CotizacionInicial/CreateQuoteModal.jsx
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';

const CreateQuoteModal = ({ onClose, onSubmit, clientData, setClientData }) => {
  const [errors, setErrors] = useState({});

  const handleChange = (field, value) => {
    setClientData(prev => ({
      ...prev,
      [field]: value
    }));
    
    // Limpiar error del campo cuando el usuario empieza a escribir
    if (errors[field]) {
      setErrors(prev => ({
        ...prev,
        [field]: null
      }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Validación básica
    const newErrors = {};
    if (!clientData.clientType) newErrors.clientType = 'Selecciona el tipo de cliente';
    if (!clientData.operationType) newErrors.operationType = 'Selecciona el tipo de operación';
    if (!clientData.typeBusiness) newErrors.typeBusiness = 'Selecciona el tipo de modalidad';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    onSubmit(clientData);
  };

  return (
    <Modal onClose={onClose} size="large">
      {/* Contenedor principal responsive */}
      <div className="p-4 sm:p-6 lg:p-8 min-h-[500px] sm:min-h-[600px] lg:min-h-[700px] w-full max-w-none">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-12 h-full">
          
          {/* Columna izquierda - Logo/Imagen */}
          <div className="flex items-center justify-center order-2 lg:order-1">
            <div className="w-full max-w-xs sm:max-w-sm lg:max-w-md">
              <img 
                className="w-full h-auto object-contain"
                src="/img/quotes_logo.gif" 
                alt="conalca-ai-logo"
              />
            </div>
          </div>

          {/* Columna derecha - Formulario */}
          <div className="flex flex-col justify-center relative order-1 lg:order-2">
            
            
            <form onSubmit={handleSubmit} className="w-full space-y-4 sm:space-y-6">
              
              {/* Título con mejor espaciado responsive */}
              <div className="text-center mb-4 sm:mb-6 lg:mb-8">
                <h1 className="text-[#FF7C32] text-xl sm:text-2xl lg:text-3xl font-bold leading-tight px-2">
                  HOLA!, HOY TE AYUDARE A CREAR TU COTIZACIÓN
                </h1>
              </div>

              {/* Buscador de empresa */}
              <div className="space-y-2 sm:space-y-3">
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Buscar la empresa en tus registros u omite esta opción.
                </label>
                <input 
                  type="search"
                  value={clientData.search || ''}
                  onChange={(e) => handleChange('search', e.target.value)}
                  placeholder="Buscar empresa" 
                  className="w-full h-12 sm:h-14 rounded-lg bg-white border border-[#dcdcdc] px-3 sm:px-4 text-gray-700 text-sm sm:text-base placeholder-gray-400 focus:border-[#FF7C32] focus:ring-2 focus:ring-[#FF7C32] focus:ring-opacity-20 transition-all"
                />
              </div>

              {/* Tipo de cliente */}
              <div className="space-y-2 sm:space-y-3">
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Selecciona el tipo de cliente que va ser dirigida tu cotización *
                </label>
                <select 
                  value={clientData.clientType || ''}
                  onChange={(e) => handleChange('clientType', e.target.value)}
                  className={`w-full h-12 sm:h-14 rounded-lg bg-white border px-3 sm:px-4 text-gray-700 text-sm sm:text-base focus:ring-2 focus:ring-opacity-20 transition-all ${
                    errors.clientType 
                      ? 'border-red-500 focus:border-red-500 focus:ring-red-500' 
                      : 'border-[#dcdcdc] focus:border-[#FF7C32] focus:ring-[#FF7C32]'
                  }`}
                >
                  <option value="">Seleccione el tipo de cliente</option>
                  <option value="credit">Crédito</option>
                  <option value="cash">Contado</option>
                </select>
                {errors.clientType && (
                  <p className="text-red-500 text-xs sm:text-sm flex items-center">
                    <svg className="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                    </svg>
                    {errors.clientType}
                  </p>
                )}
              </div>

              {/* Tipo de operación */}
              <div className="space-y-2 sm:space-y-3">
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Tipo de operación *
                </label>
                <select 
                  value={clientData.operationType || ''}
                  onChange={(e) => handleChange('operationType', e.target.value)}
                  className={`w-full h-12 sm:h-14 rounded-lg bg-white border px-3 sm:px-4 text-gray-700 text-sm sm:text-base focus:ring-2 focus:ring-opacity-20 transition-all ${
                    errors.operationType 
                      ? 'border-red-500 focus:border-red-500 focus:ring-red-500' 
                      : 'border-[#dcdcdc] focus:border-[#FF7C32] focus:ring-[#FF7C32]'
                  }`}
                >
                  <option value="">Selecciona el tipo de operación</option>
                  <option value="DISTRIBUCION">DISTRIBUCIÓN</option>
                  <option value="EXPORTACION">EXPORTACIÓN</option>
                  <option value="IMPORTACION">IMPORTACIÓN</option>
                </select>
                {errors.operationType && (
                  <p className="text-red-500 text-xs sm:text-sm flex items-center">
                    <svg className="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                    </svg>
                    {errors.operationType}
                  </p>
                )}
              </div>

              {/* Tipo de modalidad */}
              <div className="space-y-2 sm:space-y-3">
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Selecciona el tipo de modalidad para la cotización *
                </label>
                <select 
                  value={clientData.typeBusiness || ''}
                  onChange={(e) => handleChange('typeBusiness', e.target.value)}
                  className={`w-full h-12 sm:h-14 rounded-lg bg-white border px-3 sm:px-4 text-gray-700 text-sm sm:text-base focus:ring-2 focus:ring-opacity-20 transition-all ${
                    errors.typeBusiness 
                      ? 'border-red-500 focus:border-red-500 focus:ring-red-500' 
                      : 'border-[#dcdcdc] focus:border-[#FF7C32] focus:ring-[#FF7C32]'
                  }`}
                >
                  <option value="">Seleccione el tipo de negocio</option>
                  <option value="dta">DTA</option>
                  <option value="otm">OTM</option>
                  <option value="refri">REFRI</option>
                </select>
                {errors.typeBusiness && (
                  <p className="text-red-500 text-xs sm:text-sm flex items-center">
                    <svg className="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                    </svg>
                    {errors.typeBusiness}
                  </p>
                )}
              </div>

              {/* Tipo de carga */}
              <div className="space-y-2 sm:space-y-3">
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Selecciona el tipo de carga
                </label>
                <select 
                  value={clientData.cargoType || ''}
                  onChange={(e) => handleChange('cargoType', e.target.value)}
                  className="w-full h-12 sm:h-14 rounded-lg bg-white border border-[#dcdcdc] px-3 sm:px-4 text-gray-700 text-sm sm:text-base focus:border-[#FF7C32] focus:ring-2 focus:ring-[#FF7C32] focus:ring-opacity-20 transition-all"
                >
                  <option value="">Seleccione el tipo de carga</option>
                  <option value="extradimensional">CARGA EXTRADIMENSIONAL</option>
                  <option value="dangerous">MERCANCÍA PELIGROSA</option>
                  <option value="general">CARGA GENERAL</option>
                </select>
              </div>

              {/* Botón de envío */}
              <div className="pt-4 sm:pt-6">
                <button 
                  type="submit"
                  className="w-full h-12 sm:h-14 rounded-lg bg-[#FF7C32] text-white text-base sm:text-lg font-semibold hover:bg-[#e66a2b] focus:bg-[#e66a2b] focus:ring-4 focus:ring-[#FF7C32] focus:ring-opacity-30 transition-all duration-200 flex items-center justify-center space-x-2"
                >
                  <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  <span>Crear cotización</span>
                </button>
              </div>
              
            </form>
          </div>
          
        </div>
      </div>
    </Modal>
  );
};

CreateQuoteModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  onSubmit: PropTypes.func.isRequired,
  clientData: PropTypes.object.isRequired,
  setClientData: PropTypes.func.isRequired
};

export default CreateQuoteModal;