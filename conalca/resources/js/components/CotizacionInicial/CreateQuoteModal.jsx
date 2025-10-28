// resources/js/components/CotizacionInicial/CreateQuoteModal.jsx
import React, { useState, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';

const CreateQuoteModal = ({ onClose, onSubmit, clientData, setClientData }) => {
  const [errors, setErrors] = useState({});
  const [searchResults, setSearchResults] = useState([]);
  const [showSearchResults, setShowSearchResults] = useState(false);
  const [searchLoading, setSearchLoading] = useState(false);
  const searchRef = useRef(null);

  // Cerrar dropdown cuando se hace clic fuera
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (searchRef.current && !searchRef.current.contains(event.target)) {
        setShowSearchResults(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Debug: Monitor clientData changes
  useEffect(() => {
    console.log('CreateQuoteModal - clientData changed:', clientData);
  }, [clientData]);

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

    // Si es el campo de búsqueda, ejecutar búsqueda
    if (field === 'search' && value.length >= 3) {
      searchClients(value);
    } else if (field === 'search' && value.length < 3) {
      setSearchResults([]);
      setShowSearchResults(false);
    }
  };

  const searchClients = async (searchTerm) => {
    setSearchLoading(true);
    try {
      const response = await fetch(`/api/clients/search?q=${encodeURIComponent(searchTerm)}`, {
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        const clients = data.clients || [];
        setSearchResults(clients);
        setShowSearchResults(clients.length > 0);
      }
    } catch (error) {
      console.error('Error buscando clientes:', error);
    } finally {
      setSearchLoading(false);
    }
  };

  const selectClient = (client) => {
    console.log('CreateQuoteModal - selectClient - cliente seleccionado:', client);
    
    const updatedClientData = {
      ...clientData,
      search: client.documento,
      clientId: client.id,
      clientName: client.cliente,
      documentClient: client.documento,
      clientCompanyName: client.cliente,
      clientLocation: client.ciudad,
      clientPhoneNumbers: client.telefono,
      clientPersonalCell: client.telefono, // usar telefono como celular si no hay campo específico
      clientEmail: client.email,
      clientAddress: client.direccion,
      clientBranchOffice: client.branch_office || '',
      clientSalesRepresentative: client.vendedor_nombre || '',
      clientContact: client.contacto,
      clientCargo: client.cargo
    };
    
    console.log('CreateQuoteModal - selectClient - datos actualizados:', updatedClientData);
    setClientData(updatedClientData);
    setShowSearchResults(false);
    setSearchResults([]);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    console.log('CreateQuoteModal - handleSubmit - clientData antes de enviar:', clientData);
    
    // Validación básica
    const newErrors = {};
    if (!clientData.clientType) newErrors.clientType = 'Selecciona el tipo de cliente';
    if (!clientData.operationType) newErrors.operationType = 'Selecciona el tipo de operación';
    if (!clientData.typeBusiness) newErrors.typeBusiness = 'Selecciona el tipo de modalidad';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    console.log('CreateQuoteModal - Enviando datos:', clientData);
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
              <div className="space-y-2 sm:space-y-3 relative" ref={searchRef}>
                <label className="block text-[#898989] text-xs sm:text-sm font-medium">
                  Buscar la empresa en tus registros u omite esta opción.
                </label>
                <div className="relative">
                  <input 
                    type="search"
                    value={clientData.search || ''}
                    onChange={(e) => handleChange('search', e.target.value)}
                    placeholder="Buscar empresa" 
                    className="w-full h-12 sm:h-14 rounded-lg bg-white border border-[#dcdcdc] px-3 sm:px-4 text-gray-700 text-sm sm:text-base placeholder-gray-400 focus:border-[#FF7C32] focus:ring-2 focus:ring-[#FF7C32] focus:ring-opacity-20 transition-all"
                  />
                  {searchLoading && (
                    <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-[#FF7C32]"></div>
                    </div>
                  )}
                </div>

                {/* Dropdown de resultados de búsqueda */}
                {showSearchResults && searchResults.length > 0 && (
                  <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    {searchResults.map((client) => (
                      <div
                        key={client.id}
                        onClick={() => selectClient(client)}
                        className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                      >
                        <div className="flex flex-col">
                          <span className="font-medium text-gray-900 text-sm">{client.cliente}</span>
                          <span className="text-xs text-gray-500">NIT: {client.documento}</span>
                          {client.ciudad && (
                            <span className="text-xs text-gray-500">{client.ciudad}</span>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* Mostrar información del cliente seleccionado */}
                {clientData.clientId && (
                  <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex items-center mb-2">
                      <svg className="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      <span className="text-sm font-medium text-green-800">Cliente encontrado</span>
                    </div>
                    <div className="text-xs text-green-700">
                      <p><strong>Empresa:</strong> {clientData.clientName}</p>
                      <p><strong>NIT:</strong> {clientData.documentClient}</p>
                      {clientData.clientLocation && <p><strong>Ciudad:</strong> {clientData.clientLocation}</p>}
                      {clientData.clientEmail && <p><strong>Email:</strong> {clientData.clientEmail}</p>}
                    </div>
                  </div>
                )}
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

              {/* DEBUG: Estado actual de clientData */}
              {clientData.clientId && (
                <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs">
                  <p><strong>DEBUG - Datos que se enviarán:</strong></p>
                  <p>clientId: {clientData.clientId}</p>
                  <p>clientName: {clientData.clientName}</p>
                  <p>documentClient: {clientData.documentClient}</p>
                  <p>clientLocation: {clientData.clientLocation}</p>
                  <p>clientEmail: {clientData.clientEmail}</p>
                  <p>clientContact: {clientData.clientContact}</p>
                </div>
              )}

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