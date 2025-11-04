// resources/js/components/CotizacionInicial/QuoteIndex.jsx
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import QuoteModal from './QuoteModal';
import CreateQuoteModal from './CreateQuoteModal';
import ChatModal from './ChatModal';
import PricingModal from './PricingModal';
import PreviewModal from './PreviewModal';
import SuccessModal from './SuccessModal';
import EventEmitter from 'eventemitter3';

// Event bus global para comunicación entre componentes
export const quoteBus = new EventEmitter();

const QuoteIndex = ({ initialQuotes = [], user = {} }) => {
  // Estados principales
  const [quotes, setQuotes] = useState([]);
  const [selectedQuote, setSelectedQuote] = useState(null);
  const [loading, setLoading] = useState(false);
  const [loadingQuotes, setLoadingQuotes] = useState(true);
  
  // Estados de modales
  const [showModal, setShowModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showChatModal, setShowChatModal] = useState(false);
  const [showPricingModal, setShowPricingModal] = useState(false);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  
  // Estados del flujo de cotización
  const [step, setStep] = useState(0);
  const [quoteData, setQuoteData] = useState([]);
  const [messages, setMessages] = useState([]);
  const [inputMessage, setInputMessage] = useState('');
  const [pricings, setPricings] = useState([]);
  const [selectedPricings, setSelectedPricings] = useState({});
  const [porcentajeGlobal, setPorcentajeGlobal] = useState(17);

  // Datos del cliente y configuración
  const [clientData, setClientData] = useState({
    search: '',
    clientId: null,
    clientName: '',
    documentClient: '',
    clientCompanyName: '',
    clientLocation: '',
    clientPhoneNumbers: '',
    clientPersonalCell: '',
    clientEmail: '',
    clientAddress: '',
    clientBranchOffice: '',
    clientSalesRepresentative: '',
    clientContact: '',
    clientCargo: '',
    clientType: '',
    operationType: '',
    typeBusiness: ''
  });

  // Debug: Monitor clientData changes
  useEffect(() => {
    console.log('QuoteIndex - clientData changed:', clientData);
  }, [clientData]);

  // Función simplificada para emitir refresh - los datos se manejan en ChannelsWithCustomColumns
  const refreshQuotations = () => {
    console.log('QuoteIndex - Emitiendo evento de refresh');
    quoteBus.emit('refreshQuotations');
  };

  // Efectos
  useEffect(() => {
    // Ya no necesitamos cargar cotizaciones aquí - lo hace ChannelsWithCustomColumns
    console.log('QuoteIndex montado - las cotizaciones se cargan en ChannelsWithCustomColumns');
  }, []);

  useEffect(() => {
    // Configurar listeners del event bus
    quoteBus.on('openModal', handleOpenModal);
    quoteBus.on('closeModal', handleCloseModal);
    quoteBus.on('updateQuoteData', handleUpdateQuoteData);
    quoteBus.on('sendMessage', handleSendMessage);
    quoteBus.on('selectPricing', handleSelectPricing);
    
    return () => {
      quoteBus.removeAllListeners();
    };
  }, []);

  // Handlers
  const handleOpenModal = (modalType, data = {}) => {
    switch (modalType) {
      case 'create':
        setShowCreateModal(true);
        break;
      case 'chat':
        setShowChatModal(true);
        setStep(1);
        break;
      case 'pricing':
        setShowPricingModal(true);
        break;
      case 'preview':
        setShowPreviewModal(true);
        break;
      case 'success':
        setShowSuccessModal(true);
        break;
      default:
        setShowModal(true);
        setSelectedQuote(data);
    }
  };

  const handleCloseModal = (modalType) => {
    switch (modalType) {
      case 'create':
        setShowCreateModal(false);
        // No resetear datos al cerrar modal de creación, solo al cancelar completamente
        break;
      case 'chat':
        setShowChatModal(false);
        break;
      case 'pricing':
        setShowPricingModal(false);
        break;
      case 'preview':
        setShowPreviewModal(false);
        break;
      case 'success':
        setShowSuccessModal(false);
        break;
      default:
        setShowModal(false);
        setSelectedQuote(null);
    }
  };

  const handleUpdateQuoteData = (newData) => {
    setQuoteData(prev => Array.isArray(newData) ? newData : [...prev, newData]);
  };

  const handleSendMessage = async (message) => {
    try {
      setLoading(true);
      const newMessage = {
        role: 'user',
        text: message,
        created_at: new Date().toLocaleTimeString()
      };
      
      setMessages(prev => [...prev, newMessage]);
      setInputMessage('');
      
      // La lógica real se maneja en ChatModal
      // Este método es para mantener compatibilidad
      setLoading(false);
    } catch (error) {
      console.error('Error en handleSendMessage:', error);
      setLoading(false);
    }
  };

  const updateMessagesFromAPI = (apiMessages) => {
    console.log('Actualizando mensajes desde API:', apiMessages);
    // Convertir formato de API a formato del componente
    const formattedMessages = apiMessages.map(msg => ({
      role: msg.role,
      text: msg.text,
      created_at: msg.created_at ? new Date(msg.created_at).toLocaleTimeString() : new Date().toLocaleTimeString()
    }));
    setMessages(formattedMessages);
  };

  const handleSelectPricing = (routeIndex, pricing) => {
    setSelectedPricings(prev => ({
      ...prev,
      [routeIndex]: pricing
    }));
  };

  const resetCreateFlow = () => {
    setStep(0);
    setQuoteData([]);
    setMessages([]);
    setInputMessage('');
    setPricings([]);
    setSelectedPricings({});
    setPorcentajeGlobal(17);
    setClientData({
      search: '',
      clientId: null,
      clientName: '',
      documentClient: '',
      clientCompanyName: '',
      clientLocation: '',
      clientPhoneNumbers: '',
      clientPersonalCell: '',
      clientEmail: '',
      clientAddress: '',
      clientBranchOffice: '',
      clientSalesRepresentative: '',
      clientContact: '',
      clientCargo: '',
      clientType: '',
      operationType: '',
      typeBusiness: ''
    });
  };

  const handleCreateQuote = () => {
    handleOpenModal('create');
  };

  const handleCancelCreate = () => {
    handleCloseModal('create');
    resetCreateFlow();
  };

  const handleSubmitClient = async (data) => {
    console.log('QuoteIndex - handleSubmitClient - data recibida:', data);
    
    try {
      setLoading(true);
      
      // Crear grupo de cotización con parámetros automáticos
      const response = await fetch('/api/chat/quote/create-group', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          client_id: data.clientId,
          client_type: data.clientType,
          operation_type: data.operationType,
          type_business: data.typeBusiness,
          cargo_type: data.cargoType,
          candado_satelital: data.candadoSatelital,
          jen_set: data.jenSet,
          combustible: data.combustible,
          kit_derrames: data.kitDerrames,
          pictogramas: data.pictogramas,
        })
      });

      if (!response.ok) {
        console.error('Error en la respuesta del servidor:', response.status, response.statusText);
        const errorText = await response.text();
        console.error('Contenido del error:', errorText);
        throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
      }

      const result = await response.json();
      
      if (result.success) {
        console.log('QuoteIndex - Grupo creado exitosamente:', result.data);
        
        // Actualizar clientData con los datos del grupo creado
        const updatedClientData = {
          ...data,
          groupId: result.data.group_id,
          threadId: result.data.thread_id,
        };
        
        setClientData(updatedClientData);
        console.log('QuoteIndex - clientData actualizado:', updatedClientData);
        
        // Emitir evento para que ChannelsWithCustomColumns se refresque
        refreshQuotations();
        console.log('QuoteIndex - Evento de refresh emitido');
        
        // Continuar al siguiente paso
        handleCloseModal('create');
        handleOpenModal('chat');
      } else {
        console.error('Error creando grupo:', result.error);
        
        // Manejar error específico de autenticación
        if (response.status === 401) {
          alert('Sesión expirada. Por favor, inicia sesión nuevamente.');
          window.location.reload();
        } else {
          alert(`Error: ${result.error}`);
        }
      }
    } catch (error) {
      console.error('Error de red o parsing al crear el grupo:', error);
      alert(`Error de conexión: ${error.message}. Por favor, intenta nuevamente.`);
    } finally {
      setLoading(false);
    }
  };

  const handleNextStep = (stepNumber) => {
    switch (stepNumber) {
      case 1:
        // De chat a pricing
        handleCloseModal('chat');
        handleOpenModal('pricing');
        break;
      case 2:
        // De pricing a preview
        handleCloseModal('pricing');
        handleOpenModal('preview');
        break;
      case 3:
        // De preview a success
        handleCloseModal('preview');
        handleOpenModal('success');
        break;
      default:
        break;
    }
  };

  return (
    <div className="w-fit max-h-fit font-sans">
      {/* Indicador React */}
      <div className="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm text-blue-700">
              <strong>React Mode:</strong> Esta interfaz está siendo renderizada por <code>QuoteIndex.jsx</code>
              <br />
              <small className="text-blue-600">Componentes React cargados exitosamente ✓</small>
            </p>
          </div>
        </div>
      </div>

      <style jsx>{`
        .button_voice:hover {
          opacity: 1 !important;
        }
        .close2 {
          position: absolute;
          top: 10px;
          right: 10px;
          font-size: 20px;
          cursor: pointer;
        }
      `}</style>
      
      {/* Sección principal */}
      <section className="flex flex-col pt-8 pl-2 md:pl-7 pr-2 md:pr-8 pb-[1.38rem] items-start">
        {/* Botón de creación de cotización */}
        <div className="">
          <button 
            onClick={handleCreateQuote}
            className="text-white w-[30%] md:w-[15rem] h-11 rounded-lg bg-[#FF7C32] flex items-center text-center text-white text-sm font-medium gap-2 justify-center mx-4"
          >
            <span className="text-sm sm:text-base">+</span>
            <span className="text-sm leading-[1rem]">Crear cotización</span>
          </button>
        </div>

        {/* Mensaje informativo - las cotizaciones se muestran en las columnas abajo */}
        <div className="w-full mt-4">
          <p className="text-gray-600 text-sm">
            Las cotizaciones aparecen organizadas en las columnas de abajo según su estado.
          </p>
        </div>
      </section>

      {/* Modales */}
      {showModal && selectedQuote && (
        <QuoteModal
          quote={selectedQuote}
          onClose={() => handleCloseModal('view')}
        />
      )}

      {showCreateModal && (
        <CreateQuoteModal
          onClose={handleCancelCreate}
          onSubmit={handleSubmitClient}
          clientData={clientData}
          setClientData={setClientData}
        />
      )}

      {showChatModal && (
        <ChatModal
          onClose={() => handleCloseModal('chat')}
          onNext={() => handleNextStep(1)}
          messages={messages}
          inputMessage={inputMessage}
          setInputMessage={setInputMessage}
          onSendMessage={handleSendMessage}
          quoteData={quoteData}
          setQuoteData={setQuoteData}
          clientData={clientData}
          loading={loading}
          typeBusiness={clientData.typeBusiness || 'dta'}
          onUpdateMessages={updateMessagesFromAPI}
        />
      )}

      {showPricingModal && (
        <PricingModal
          onClose={() => handleCloseModal('pricing')}
          onNext={() => handleNextStep(2)}
          quoteData={quoteData}
          setQuoteData={setQuoteData}
          pricings={pricings}
          setPricings={setPricings}
          selectedPricings={selectedPricings}
          setSelectedPricings={setSelectedPricings}
          porcentajeGlobal={porcentajeGlobal}
          setPorcentajeGlobal={setPorcentajeGlobal}
          clientData={clientData}
        />
      )}

      {showPreviewModal && (
        <PreviewModal
          onClose={() => handleCloseModal('preview')}
          onNext={() => handleNextStep(3)}
          quoteData={quoteData}
          clientData={clientData}
          selectedPricings={selectedPricings}
        />
      )}

      {showSuccessModal && (
        <SuccessModal
          onClose={() => handleCloseModal('success')}
          clientData={clientData}
          quoteData={quoteData}
          threadId={clientData.threadId}
        />
      )}
    </div>
  );
};

QuoteIndex.propTypes = {
  initialQuotes: PropTypes.array,
  user: PropTypes.object
};

export default QuoteIndex;