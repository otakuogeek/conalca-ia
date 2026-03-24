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
import EditRoutesModal from './EditRoutesModal';

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
  const [showEditRoutesModal, setShowEditRoutesModal] = useState(false);

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

  // Función para continuar cotización desde un grupo existente
  const handleContinueQuotation = async (groupId, searchParam) => {
    try {
      setLoading(true);
      console.log('Recuperando datos del grupo:', groupId);
      
      // Llamar al backend para obtener los datos del grupo
      const response = await fetch(`/api/groups/${groupId}/recover`, {
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });
      
      if (!response.ok) {
        throw new Error('Error al recuperar los datos del grupo');
      }
      
      const result = await response.json();
      console.log('📦 Datos recuperados del backend:', result);
      console.log('🔍 Grupo completo:', result.data?.group);
      console.log('🔍 extracted_data recibido:', result.data?.group?.extracted_data);
      console.log('🔍 Tipo de extracted_data:', typeof result.data?.group?.extracted_data);
      
      if (result.success) {
        const { client, group, cotizaciones } = result.data;
        
        // Restaurar datos del cliente
        setClientData(prev => ({
          ...prev,
          search: searchParam || client.documento || '',
          clientId: client.id,
          clientName: client.cliente || client.name,
          documentClient: client.documento,
          clientCompanyName: client.cliente || client.name,
          clientLocation: client.ciudad,
          clientPhoneNumbers: client.telefono,
          clientPersonalCell: client.telefono,
          clientEmail: client.email,
          clientAddress: client.direccion,
          clientBranchOffice: client.branch_office || '',
          clientSalesRepresentative: client.vendedor_nombre || '',
          clientContact: client.contacto,
          clientCargo: client.cargo,
          clientType: 'cash', // Siempre contado
          operationType: group.operation_type || '',
          typeBusiness: group.type || '',
          groupId: group.id,
          threadId: group.openai_thread_id
        }));
        
        // 🚛 PRIORIDAD: Si hay extracted_data en formato multi-ruta, usarlo
        const extractedData = group.extracted_data;
        console.log('🔍 Verificando extracted_data:', {
          exists: !!extractedData,
          type: typeof extractedData,
          isMultiRuta: extractedData?.multi_ruta,
          hasRutas: !!extractedData?.rutas,
          isArray: Array.isArray(extractedData?.rutas),
          rutasLength: extractedData?.rutas?.length,
          cotizacionesLength: cotizaciones?.length || 0
        });
        
        const isMultiRuta = extractedData?.multi_ruta && extractedData?.rutas && Array.isArray(extractedData.rutas);
        
        if (isMultiRuta && extractedData.rutas.length > 0) {
          console.log('🚛 Formato multi-ruta detectado en recovered data:', extractedData.rutas.length, 'rutas');
          console.log('📦 Rutas crudas:', extractedData.rutas);
          
          const routeData = extractedData.rutas.map((ruta, idx) => {
            const mappedRoute = {
              ruta_id: ruta.ruta_id || `temp_ruta_${idx + 1}`, // 🆔 Preservar ID único
              ruta_numero: idx + 1,
              ciudadOrigen: ruta.origen,
              ciudad_origen: ruta.origen,
              ciudadDestino: ruta.destino,
              ciudad_destino: ruta.destino,
              pesoMercancia: ruta.peso || ruta.peso_kg,
              peso_mercancia: ruta.peso || ruta.peso_kg,
              peso_kg: ruta.peso || ruta.peso_kg,
              cantidadMercancia: ruta.cantidad,
              cantidad: ruta.cantidad,
              producto: ruta.producto,
              tipo_producto: ruta.producto,
              empaque: ruta.empaque,
              tipo_embajale: ruta.empaque,
              valorMercancia: ruta.valor,
              valor_declarado: ruta.valor,
              vehiculo: ruta.vehiculo,
              vehiculo_requerido: ruta.vehiculo,
              claseVehiculo: ruta.vehiculo,
              contenedor: ruta.contenedor,
              tipo_contenedor: ruta.contenedor
            };
            console.log(`📍 Ruta ${idx + 1} mapeada:`, mappedRoute);
            return mappedRoute;
          });
          
          setQuoteData(routeData);
          console.log('✅ Rutas cargadas desde extracted_data:', routeData);
          setShowChatModal(true);
          setStep(1);
        } else if (cotizaciones && cotizaciones.length > 0) {
          // Si hay cotizaciones en BD, usar esas
          const routeData = cotizaciones.map(cot => ({
            ciudad_origen: cot.ciudad_origen,
            ciudad_destino: cot.ciudad_destino,
            codigo_dane_origen: cot.ciudad_origen_dane,
            codigo_dane_destino: cot.ciudad_destino_dane,
            peso_mercancia: cot.peso_mercancia,
            cantidad: cot.cantidad,
            tipo_embajale: cot.tipo_embajale,
            dimensiones_exactas: cot.dimensiones_exactas,
            valor_declarado: cot.valor_declarado,
            tipo_mercancia: cot.tipo_mercancia,
            porcentaje: cot.porcentaje,
            select_value: cot.pricing_id,
            vehiculo_requerido: cot.pricing?.vehicle_type || '',
            // Restaurar parámetros automáticos
            candado_satelital: cot.candado_satelital || 0,
            jen_set: cot.jen_set || 0,
            combustible: cot.combustible || 0,
            kit_derrames: cot.kit_derrames || 0,
            pictogramas: cot.pictogramas || 0
          }));
          
          setQuoteData(routeData);
          console.log('✅ Rutas cargadas desde cotizacion_models:', routeData);
          
          // Si ya hay precios configurados, ir directo al modal de precios
          const hasPricings = cotizaciones.some(cot => cot.pricing_id);
          if (hasPricings) {
            console.log('Cotización con precios encontrada, abriendo PricingModal');
            // NO limpiar mensajes - mantener conversación
            setShowPricingModal(true);
            setStep(2);
          } else {
            console.log('Cotización sin precios, abriendo ChatModal');
            // NO limpiar mensajes - continuar conversación existente
            setShowChatModal(true);
            setStep(1);
          }
        } else {
          // No hay rutas, empezar desde el chat
          console.log('Grupo sin rutas, abriendo ChatModal');
          // NO limpiar mensajes - puede ser continuación de conversación
          setShowChatModal(true);
          setStep(1);
        }
        
        // Mostrar mensaje de recuperación exitosa
        console.log('✅ Cotización recuperada exitosamente');
        
      } else {
        throw new Error(result.message || 'Error al recuperar la cotización');
      }
      
    } catch (error) {
      console.error('Error al continuar cotización:', error);
      alert('Error al recuperar la cotización. Por favor, intenta de nuevo.');
    } finally {
      setLoading(false);
    }
  };

  // Efectos
  useEffect(() => {
    // Ya no necesitamos cargar cotizaciones aquí - lo hace ChannelsWithCustomColumns
    console.log('QuoteIndex montado - las cotizaciones se cargan en ChannelsWithCustomColumns');
    
    // Verificar si hay un parámetro "continue" en la URL para recuperar progreso
    const urlParams = new URLSearchParams(window.location.search);
    const continueGroupId = urlParams.get('continue');
    const searchParam = urlParams.get('search');
    
    if (continueGroupId) {
      console.log('Detectado parámetro continue:', continueGroupId);
      handleContinueQuotation(continueGroupId, searchParam);
    }
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
    const { isNewConversation = false } = data; // Flag para saber si es nueva conversación
    
    switch (modalType) {
      case 'create':
        setShowCreateModal(true);
        break;
      case 'chat':
        // Solo limpiar mensajes si es una conversación NUEVA
        if (isNewConversation) {
          console.log('🧽 Nueva conversación - Limpiando mensajes de chat anterior');
          setMessages([]);
        } else {
          console.log('💬 Continuando conversación - Manteniendo mensajes');
        }
        setShowChatModal(true);
        setStep(1);
        break;
      case 'editRoutes':
        setShowEditRoutesModal(true);
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

  const handleCloseModal = (modalType, options = {}) => {
    const { clearMessages = false } = options; // Solo limpiar si se indica explícitamente
    
    switch (modalType) {
      case 'create':
        setShowCreateModal(false);
        // No resetear datos al cerrar modal de creación, solo al cancelar completamente
        break;
      case 'chat':
        if (clearMessages) {
          console.log('🧹 Limpiando mensajes al cerrar ChatModal');
          setMessages([]); // Limpiar solo si se pide explícitamente
        } else {
          console.log('💬 Cerrando ChatModal SIN limpiar mensajes (continúa flujo)');
        }
        setShowChatModal(false);
        break;
      case 'editRoutes':
        setShowEditRoutesModal(false);
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
    
    // Si es una función (callback con prev), llamarla con el estado actual
    if (typeof apiMessages === 'function') {
      setMessages(apiMessages);
      return;
    }
    
    // Si no es un array, no hacer nada
    if (!Array.isArray(apiMessages)) {
      console.warn('⚠️ updateMessagesFromAPI recibió datos no válidos:', typeof apiMessages);
      return;
    }
    
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

  const resetCreateFlow = async () => {
    console.log('🧹 Limpiando completamente el formulario de cotización');
    
    // Si hay un threadId activo, limpiarlo en el backend
    if (clientData.threadId) {
      try {
        console.log('🗑️ Limpiando thread en backend:', clientData.threadId);
        await fetch('/api/chat/clear-thread', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            thread_id: clientData.threadId,
            client_id: clientData?.clientId || null
          })
        });
        console.log('✅ Thread limpiado en backend');
      } catch (error) {
        console.warn('⚠️ Error limpiando thread:', error);
      }
    }
    
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
      clientType: 'cash', // Siempre contado por defecto
      operationType: '',
      typeBusiness: '',
      groupId: null,
      threadId: null,
      cargoType: '',
      candadoSatelital: false,
      jenSet: false,
      combustible: false,
      kitDerrames: false,
      pictogramas: false
    });
    
    // Limpiar cualquier parámetro de URL
    if (window.location.search) {
      const cleanUrl = window.location.pathname;
      window.history.replaceState({}, document.title, cleanUrl);
    }
    
    console.log('✅ Formulario y chat limpiados completamente');
  };

  const handleCreateQuote = async () => {
    // Resetear todo antes de abrir el modal (incluyendo limpieza de thread)
    await resetCreateFlow();
    handleOpenModal('create');
  };

  const handleCancelCreate = async () => {
    // Al cancelar, limpiar mensajes explícitamente
    handleCloseModal('create');
    await resetCreateFlow();
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
          operation_type: data.operationType, // Convertir de camelCase a snake_case
          type_business: data.typeBusiness,    // Convertir de camelCase a snake_case
          cargo_type: data.cargoType,          // Convertir de camelCase a snake_case
          load_type: data.loadType,              // Tipo de carga: suelta o contenerizada
          candado_satelital: data.candadoSatelital, // Convertir de camelCase a snake_case
          jen_set: data.jenSet,                     // Convertir de camelCase a snake_case
          combustible: data.combustible,
          kit_derrames: data.kitDerrames,           // Convertir de camelCase a snake_case
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
        console.log('QuoteIndex - Grupo borrador creado:', result.data);
        
        // Actualizar clientData CON groupId
        const updatedClientData = {
          ...data,
          groupId: result.data.group_id,
          threadId: result.data.thread_id,
        };
        
        setClientData(updatedClientData);
        console.log('QuoteIndex - clientData actualizado:', updatedClientData);
        
        // Guardar group_id en sesión para Livewire
        try {
          await fetch('/api/chat/quote/set-session-group', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ group_id: result.data.group_id })
          });
          console.log('✅ Group ID guardado en sesión');
        } catch (e) {
          console.warn('⚠️ No se pudo guardar en sesión:', e);
        }
        
        // Emitir evento para que ChannelsWithCustomColumns se refresque
        refreshQuotations();
        console.log('QuoteIndex - Evento de refresh emitido');
        
        // Continuar al siguiente paso
        handleCloseModal('create');
        handleOpenModal('chat', { isNewConversation: true }); // Nueva conversación
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

  // const handleNextStep = (stepNumber) => {
  //   switch (stepNumber) {
  //     case 1:
  //       // De chat a pricing
  //       handleCloseModal('chat');
  //       handleOpenModal('pricing');
  //       break;
  //     case 2:
  //       // De pricing a preview
  //       handleCloseModal('pricing');
  //       handleOpenModal('preview');
  //       break;
  //     case 3:
  //       // De preview a success
  //       handleCloseModal('preview');
  //       handleOpenModal('success');
  //       break;
  //     default:
  //       break;
  //   }
  // };

  const handleNextStep = (stepNumber) => {
    console.log('🔄 handleNextStep llamado con step:', stepNumber);
    
    switch (stepNumber) {
      case 1:
        // From Chat to EditRoutes
        console.log('📋 Cerrando ChatModal y abriendo EditRoutesModal');
        handleCloseModal('chat');
        handleOpenModal('editRoutes');
        setStep(2);
        break;
      case 2:
        // From EditRoutes to Pricing
        console.log('💰 Cerrando EditRoutesModal y abriendo PricingModal');
        handleCloseModal('editRoutes');
        handleOpenModal('pricing');
        setStep(3);
        break;
      case 3:
        // From Pricing to Preview
        console.log('👁️ Cerrando PricingModal y abriendo PreviewModal');
        handleCloseModal('pricing');
        handleOpenModal('preview');
        setStep(4);
        break;
      case 4:
        // From Preview to Success
        console.log('✅ Cerrando PreviewModal y abriendo SuccessModal');
        handleCloseModal('preview');
        handleOpenModal('success');
        setStep(5);
        break;
      default:
        break;
    }
  };

  return (
    <div className="w-fit max-h-fit font-sans">
      {/* Indicador React */}
    

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
          onClose={() => handleCloseModal('chat', { clearMessages: true })} // Limpiar al cancelar
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

      {showEditRoutesModal && (
        <EditRoutesModal
          onClose={() => handleCloseModal('editRoutes')}
          onNext={() => handleNextStep(2)} // go to Pricing
          groupId={clientData.groupId}
          quoteData={quoteData}
          setQuoteData={setQuoteData}
          clientData={clientData}
        />
      )}

      {showPricingModal && (
        <PricingModal
          onClose={() => handleCloseModal('pricing')}
          onNext={() => handleNextStep(3)}
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
          onNext={() => handleNextStep(4)}
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