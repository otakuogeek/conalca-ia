// resources/js/components/CotizacionInicial/ChatModal.jsx
import React, { useEffect, useRef, useState, useMemo, useCallback } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import SpeechRecognition from './ui/SpeechRecognition';
import QuoteDetailsPanel from './QuoteDetailsPanel';
import { stripMarkdown, formatMessageTime, detectRouteFromMessage, getCsrfToken } from './utils/chatUtils';
import { prepareRoutesForPanel, hasAllRequiredData as checkHasAllRequiredData, mapBackendRouteToQuote, createDataHash } from './utils/quoteDataMapper';

// 🆕 Palabras que NUNCA pueden ser ciudades válidas (evita bugs de extracción)
const INVALID_CITY_WORDS = [
  // Meses del año
  'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
  'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
  // Palabras de tiempo/hora
  'las', 'los', 'del', 'dia', 'dias', 'hora', 'horas', 'manana', 'mañana', 'tarde', 'noche',
  'am', 'pm', 'hoy', 'ayer', 'semana', 'mes', 'ano', 'año',
  // Campos de formulario
  'producto', 'peso', 'valor', 'empaque', 'embalaje', 'cantidad', 'origen', 'destino',
  // Acciones
  'cambia', 'cambiar', 'nota', 'importante'
];

/**
 * Valida si una ciudad es válida (no es una palabra de fecha/hora/campo)
 */
const isValidCity = (city) => {
  if (!city || typeof city !== 'string') return false;
  const cityLower = city.toLowerCase().trim();
  if (cityLower.length < 3) return false;
  return !INVALID_CITY_WORDS.includes(cityLower);
};

/**
 * Filtra datos de ruta para evitar ciudades inválidas
 * Mantiene el valor existente si el nuevo es inválido
 */
const sanitizeRouteData = (newRoute, existingRoute = {}) => {
  const sanitized = { ...newRoute };

  // Validar ciudadOrigen/origen
  const newOrigen = newRoute.ciudadOrigen || newRoute.origen || newRoute.ciudad_origen;
  const existingOrigen = existingRoute.ciudadOrigen || existingRoute.origen || existingRoute.ciudad_origen;
  if (!isValidCity(newOrigen) && isValidCity(existingOrigen)) {
    sanitized.ciudadOrigen = existingOrigen;
    sanitized.origen = existingOrigen;
    sanitized.ciudad_origen = existingOrigen;
    console.warn('⚠️ Ciudad origen inválida detectada:', newOrigen, '- manteniendo:', existingOrigen);
  }

  // Validar ciudadDestino/destino
  const newDestino = newRoute.ciudadDestino || newRoute.destino || newRoute.ciudad_destino;
  const existingDestino = existingRoute.ciudadDestino || existingRoute.destino || existingRoute.ciudad_destino;
  if (!isValidCity(newDestino) && isValidCity(existingDestino)) {
    sanitized.ciudadDestino = existingDestino;
    sanitized.destino = existingDestino;
    sanitized.ciudad_destino = existingDestino;
    console.warn('⚠️ Ciudad destino inválida detectada:', newDestino, '- manteniendo:', existingDestino);
  }

  return sanitized;
};

const ChatModal = ({
  onClose,
  onNext,
  messages,
  inputMessage,
  setInputMessage,
  onSendMessage,
  quoteData,
  setQuoteData,
  clientData,
  loading,
  typeBusiness = 'dta',
  onUpdateMessages
}) => {
  const conversationRef = useRef(null);
  const [isRecording, setIsRecording] = useState(false);
  const [threadId, setThreadId] = useState(null);
  const [isSending, setIsSending] = useState(false);
  const [currentRunId, setCurrentRunId] = useState(null);
  const pollingIntervalRef = useRef(null);
  const [processingMessage, setProcessingMessage] = useState(null);
  const [processingProgress, setProcessingProgress] = useState(0);
  const sendingRef = useRef(false);
  const isSavingQuote = useRef(false);
  const [isCreatingQuote, setIsCreatingQuote] = useState(false);
  const [shouldAutoScroll, setShouldAutoScroll] = useState(true);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [selectedEmpaque, setSelectedEmpaque] = useState(null);
  const [selectedRouteIndex, setSelectedRouteIndex] = useState(null);
  const selectedRouteIndexRef = useRef(null);
  const lastProcessedDataHashRef = useRef(null);

  // Función para manejar selección de ruta
  const handleSelectRoute = useCallback((index) => {
    setSelectedRouteIndex(index);
    if (onUpdateMessages && index !== null) {
      onUpdateMessages(prev => [
        ...prev,
        {
          role: 'system',
          text: `📝 Ruta ${index + 1} seleccionada para edición. Los próximos cambios se aplicarán solo a esta ruta.`,
          created_at: new Date().toLocaleTimeString()
        }
      ]);
    }
  }, [onUpdateMessages]);

  // Sincronizar selectedRouteIndexRef con el estado
  useEffect(() => {
    selectedRouteIndexRef.current = selectedRouteIndex;
  }, [selectedRouteIndex]);

  // Monitorear cambios en quoteData (solo en desarrollo)
  useEffect(() => {
    if (process.env.NODE_ENV === 'development' && Array.isArray(quoteData) && quoteData.length > 0) {
      console.log('📊 quoteData actualizado:', quoteData.length, 'ruta(s)');
    }
  }, [quoteData]);


  useEffect(() => {
    const resetConversation = async () => {
      if (!clientData.clientId) return;

      // 🔴 SOLO resetear si es un NUEVO cliente o grupo (no en cada render)
      const currentKey = `${clientData.clientId}-${clientData.groupId}`;
      const previousKey = sessionStorage.getItem('lastChatKey');
      
      // Si es el mismo cliente/grupo, NO resetear
      if (previousKey === currentKey) {
        console.log('🔒 Mismo cliente/grupo, preservando datos existentes');
        return;
      }
      
      // Guardar la clave actual
      sessionStorage.setItem('lastChatKey', currentKey);
      
      console.log('🔄 Nuevo cliente/grupo detectado, reseteando conversación');

      // Limpiar mensajes del chat
      if (onUpdateMessages) {
        onUpdateMessages([]);
      }

      // Limpiar datos de cotización para empezar fresco
      if (setQuoteData) {
        setQuoteData({});
      }

      // Limpiar hash de datos procesados para evitar falsos positivos
      lastProcessedDataHashRef.current = null;

      // Resetear selección de producto y empaque
      setSelectedProduct(null);
      setSelectedEmpaque(null);

      // Usar threadId existente si hay, sino null
      setThreadId(clientData.threadId || null);
    };

    resetConversation();
  }, [clientData.clientId, clientData.groupId]); // Ejecutar cuando cambia cliente O grupo

  // Auto-enviar mensaje inicial con datos del formulario si existen
  useEffect(() => {
    // Solo ejecutar una vez cuando el modal se abre
    if (!quoteData || messages.length > 0) return;

    const hasData = quoteData.ciudadOrigen || quoteData.ciudadDestino ||
      quoteData.pesoMercancia || quoteData.valorMercancia;

    if (hasData) {
      // Construir mensaje con los datos disponibles
      let mensaje = "He completado los siguientes datos del formulario:\n\n";

      if (quoteData.ciudadOrigen) {
        mensaje += `- Ciudad de origen: ${quoteData.ciudadOrigen}\n`;
      }
      if (quoteData.ciudadDestino) {
        mensaje += `- Ciudad de destino: ${quoteData.ciudadDestino}\n`;
      }
      if (quoteData.cantidadMercancia) {
        mensaje += `- Cantidad de mercancía: ${quoteData.cantidadMercancia}\n`;
      }
      if (quoteData.pesoMercancia) {
        mensaje += `- Peso: ${quoteData.pesoMercancia} kg\n`;
      }
      if (quoteData.valorMercancia) {
        mensaje += `- Valor declarado: $${quoteData.valorMercancia}\n`;
      }
      if (quoteData.producto) {
        mensaje += `- Producto: ${quoteData.producto}\n`;
      }
      if (quoteData.empaque) {
        mensaje += `- Empaque: ${quoteData.empaque}\n`;
      }
      if (quoteData.claseVehiculo) {
        mensaje += `- Clase de vehículo: ${quoteData.claseVehiculo}\n`;
      }
      if (quoteData.carroceria) {
        mensaje += `- Carrocería: ${quoteData.carroceria}\n`;
      }
      if (quoteData.tipoFlete) {
        mensaje += `- Tipo de flete: ${quoteData.tipoFlete}\n`;
      }

      mensaje += "\n¿Hay algo más que deba completar o modificar?";

      // Establecer el mensaje en el input y simular envío
      setInputMessage(mensaje);

      // Enviar después de un breve delay para que el componente se monte completamente
      setTimeout(() => {
        onSendMessage(mensaje);
      }, 500);
    }
  }, []); // Solo ejecutar al montar



  // 🆕 Cargar mensajes existentes desde la base de datos cuando hay threadId o groupId
  // DESHABILITADO: Para evitar mezcla de conversaciones, NO cargar mensajes históricos
  // Solo mostrar mensajes de la sesión actual
  useEffect(() => {
    console.log('🔒 Carga de mensajes históricos DESHABILITADA - Solo mensajes nuevos');
    console.log('🎯 Iniciando conversación limpia para groupId:', clientData?.groupId);
    // No hacer nada - dejar el chat limpio
  }, [clientData?.groupId]); // 🔴 SOLO ejecutar cuando cambia groupId (no threadId)

  useEffect(() => {
    if (shouldAutoScroll && conversationRef.current) {
      conversationRef.current.scrollTop = conversationRef.current.scrollHeight;
    }
  }, [messages, processingMessage, shouldAutoScroll]);

  // Cleanup polling on unmount
  useEffect(() => {
    return () => {
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
        pollingIntervalRef.current = null;
      }
    };
  }, []); // Sin dependencias - solo cleanup al desmontar

  // Validar si tenemos todos los datos necesarios para crear cotización
  const hasAllRequiredData = useMemo(() => {
    // Obtener el primer elemento si es array, o el objeto directamente
    const data = Array.isArray(quoteData) ? quoteData[0] : quoteData;

    if (!data) return false;

    return (
      (selectedProduct !== null || data.producto || data.tipo_producto) &&
      (selectedEmpaque !== null || data.empaque || data.tipo_embajale) &&
      (data.ciudadOrigen || data.ciudad_origen) &&
      (data.ciudadDestino || data.ciudad_destino) &&
      (data.pesoMercancia || data.peso_mercancia) &&
      (data.cantidadMercancia || data.cantidad)
    );
  }, [selectedProduct, selectedEmpaque, quoteData]);

  // Calcular routesData para QuoteDetailsPanel (memoizado para evitar recálculos innecesarios)
  const routesData = useMemo(() => {
    // Caso 1: quoteData es array (multi-ruta)
    if (Array.isArray(quoteData) && quoteData.length > 0) {
      return quoteData.map((route, index) => {
        return {
          ...route,
          // Priorizar el producto de la ruta sobre selectedProduct global
          producto: route.producto || route.tipo_producto || route.producto_nombre || (index === 0 ? selectedProduct?.nombre : null),
          producto_codigo: route.producto_codigo || (index === 0 ? selectedProduct?.codigo : null),
          tipo_producto: route.tipo_producto || route.producto || route.producto_nombre || (index === 0 ? selectedProduct?.nombre : null),
          // Priorizar el empaque de la ruta sobre selectedEmpaque global
          empaque: route.empaque || route.tipo_embalaje || (index === 0 ? (selectedEmpaque?.nome || selectedEmpaque?.nombre) : null),
          tipo_embalaje: route.tipo_embalaje || route.empaque || (index === 0 ? (selectedEmpaque?.nome || selectedEmpaque?.nombre) : null)
        };
      });
    }
    // Caso 2: quoteData es objeto con datos
    else if (quoteData && typeof quoteData === 'object' && Object.keys(quoteData).length > 0) {
      return [{
        ...quoteData,
        producto: selectedProduct?.nombre || quoteData.producto || quoteData.tipo_producto,
        producto_codigo: selectedProduct?.codigo || quoteData.producto_codigo,
        tipo_producto: selectedProduct?.nombre || quoteData.tipo_producto || quoteData.producto,
        empaque: selectedEmpaque?.nome || selectedEmpaque?.nombre || quoteData.empaque || quoteData.tipo_embalaje,
        tipo_embalaje: selectedEmpaque?.nome || selectedEmpaque?.nombre || quoteData.tipo_embalaje || quoteData.empaque
      }];
    }
    // Caso 3: NO hay quoteData pero SÍ hay selectedProduct o selectedEmpaque
    else if (selectedProduct || selectedEmpaque) {
      return [{
        producto: selectedProduct?.nombre,
        producto_codigo: selectedProduct?.codigo,
        tipo_producto: selectedProduct?.nombre,
        empaque: selectedEmpaque?.nome || selectedEmpaque?.nombre,
        tipo_embalaje: selectedEmpaque?.nome || selectedEmpaque?.nombre
      }];
    }
    return [];
  }, [quoteData, selectedProduct, selectedEmpaque]);

  // Helper para limpiar estado de procesamiento
  const clearProcessingState = () => {
    setProcessingMessage(null);
    setProcessingProgress(100); // Completar barra
    setTimeout(() => setProcessingProgress(0), 500); // Reset después de animación
    setIsSending(false);
    sendingRef.current = false;
    setCurrentRunId(null);
  };

  const handleConversationScroll = () => {
    if (!conversationRef.current) return;

    const { scrollTop, scrollHeight, clientHeight } = conversationRef.current;
    const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

    // If user is near bottom (< 60px), keep auto-scroll enabled; otherwise disable
    setShouldAutoScroll(distanceFromBottom < 60);
  };

  // Detectar mensajes huérfanos y crear run si es necesario
  const checkForOrphanMessages = async (threadId) => {
    try {
      console.log('🔍 Verificando mensajes huérfanos para thread:', threadId);

      // Usar el endpoint especializado para procesar mensajes huérfanos
      const response = await fetch('/api/chat/orphan/process', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          thread_id: threadId,
          client_id: clientData.clientId
        })
      });

      // Manejar error 503 (servicio no disponible)
      if (response.status === 503) {
        const data = await response.json();
        console.log('⚠️ Servicio temporalmente no disponible:', data.message);
        return false; // No reintentar, el mensaje se procesará automáticamente cuando el servicio esté disponible
      }

      // Manejar error 500 (error interno)
      if (response.status === 500) {
        console.error('❌ Error interno procesando huérfanos');
        return false;
      }

      const data = await response.json();
      if (data.success) {
        console.log('✅ Respuesta del procesador de huérfanos:', data.data);

        if (data.data.run_id) {
          console.log('🚀 Run creado para mensaje huérfano:', data.data.run_id);

          // Mostrar mensaje de procesamiento
          setProcessingMessage({
            role: 'assistant',
            text: 'Procesando mensaje pendiente...',
            created_at: new Date().toLocaleTimeString(),
            status: 'thinking',
            isTemporary: true
          });

          // Iniciar polling para el run creado
          startPollingRun(threadId, data.data.run_id);

          return true; // Indica que se encontró y procesó un mensaje huérfano
        } else {
          console.log('ℹ️ No se detectaron mensajes huérfanos');
          return false;
        }
      } else {
        console.error('❌ Error en el procesador de huérfanos:', data.error);
        return false;
      }
    } catch (error) {
      console.error('Error verificando mensajes huérfanos:', error);
      return false;
    }
  };

  const startPollingRun = (threadId, runId) => {
    console.log('🔄 Iniciando polling para run:', runId);
    setCurrentRunId(runId);
    setProcessingProgress(50);

    let pollAttempts = 0;
    const maxPollAttempts = 20;
    let isPollingActive = true;

    const stopPolling = (reason = '') => {
      if (process.env.NODE_ENV === 'development') {
        console.log(`🛑 Deteniendo polling: ${reason}`);
      }
      isPollingActive = false;
      if (pollingIntervalRef.current) {
        clearInterval(pollingIntervalRef.current);
        pollingIntervalRef.current = null;
      }
      setCurrentRunId(null);
      setProcessingMessage(null);
    };

    const pollRun = async () => {
      try {
        if (!isPollingActive) {
          console.log('⏹️ Polling ya detenido, saliendo...');
          return;
        }

        pollAttempts++;
        console.log(`🔍 Polling intento ${pollAttempts}/${maxPollAttempts}`);

        if (pollAttempts > maxPollAttempts) {
          stopPolling('Timeout alcanzado');
          return;
        }

        const response = await fetch(`/api/chat/run/${threadId}/${runId}`, {
          headers: { 'Accept': 'application/json' }
        });

        const data = await response.json();
        console.log(`📊 Status recibido:`, data.data?.status);

        if (!data.success) {
          console.error('❌ Error en respuesta:', data);
          return;
        }

        const status = data.data?.status;

        // DETENER INMEDIATAMENTE si hay datos
        if (status === 'completed_with_data') {
          console.log('✅ DATOS RECIBIDOS - Procesando y deteniendo...');

          // Procesar extracted_data - AHORA PROCESA TODAS LAS RUTAS
          if (data.data.extracted_data && Array.isArray(data.data.extracted_data)) {
            const allRoutes = data.data.extracted_data;
            console.log('📦 Rutas extraídas:', allRoutes.length, allRoutes);

            if (setQuoteData && allRoutes.length > 0) {
              // 🆕 PROCESAR TODAS LAS RUTAS COMO ARRAY
              const processedRoutes = allRoutes.map((route, idx) => {
                console.log(`📍 Procesando ruta ${idx + 1}:`, route);
                return {
                  ciudadOrigen: route.ciudad_origen ?? route.origen ?? null,
                  ciudadDestino: route.ciudad_destino ?? route.destino ?? null,
                  pesoMercancia: route.peso_mercancia ?? route.peso_kg ?? null,
                  cantidadMercancia: route.cantidad_unidades ?? route.cantidad ?? null,
                  valorMercancia: route.valor_mercancia ?? route.valor_declarado ?? null,
                  vehiculo: route.vehiculo ?? null, // Para compatibilidad
                  claseVehiculo: route.vehiculo ?? route.claseVehiculo ?? route.vehiculo_requerido ?? null, // 🆕 Para el panel
                  producto: route.tipo_producto ?? route.producto ?? null,
                  tipo_producto: route.tipo_producto ?? route.producto ?? null,
                  empaque: route.empaque ?? null,
                  empaque_id: route.empaque_id ?? null,
                  contenedor: route.tipo_contenedor ?? route.contenedor ?? null,
                };
              });

              console.log(`✅ ${processedRoutes.length} rutas procesadas para QuoteDetailsPanel:`, processedRoutes);

              // 🆕 SIEMPRE devolver array
              setQuoteData(processedRoutes);

              // Auto-seleccionar empaque del PRIMER elemento
              const firstRoute = allRoutes[0];
              if (firstRoute.empaque && firstRoute.empaque_id) {
                setSelectedEmpaque({
                  id: firstRoute.empaque_id,
                  nome: firstRoute.empaque,
                  nombre: firstRoute.empaque
                });
              }

              // Auto-seleccionar producto del PRIMER elemento (si es común a todas)
              if (firstRoute.tipo_producto || firstRoute.producto) {
                const prodNombre = firstRoute.tipo_producto || firstRoute.producto;
                setSelectedProduct({
                  id: firstRoute.producto_id || 0,
                  codigo: firstRoute.producto_codigo || prodNombre.toUpperCase(),
                  producto_codigo: firstRoute.producto_codigo || prodNombre.toUpperCase(),
                  nombre: prodNombre.toUpperCase(),
                  producto: prodNombre.toUpperCase()
                });
                console.log('✅ Producto auto-seleccionado de primera ruta:', {
                  codigo: firstRoute.producto_codigo || prodNombre.toUpperCase(),
                  nombre: prodNombre.toUpperCase()
                });
              }
            }
          }

          // NOTA: quote_data viene como array de rutas pero no tiene la normalización camelCase
          // extracted_data ya fue procesado arriba con el mapeo correcto
          // NO sobrescribir quoteData con quote_data crudo

          // NO obtener mensajes históricos - solo usar los de la sesión actual
          console.log('✅ Datos procesados - NO cargando mensajes históricos');
          console.log('💬 Los mensajes se actualizarán solo con los de esta conversación');

          stopPolling('Datos completados');
          return;
        }

        // Estados que también detienen
        if (status === 'completed' || status === 'failed' || status === 'cancelled') {
          stopPolling(`Estado final: ${status}`);
          return;
        }

        // in_progress continúa normalmente
        if (status === 'in_progress') {
          console.log('⏳ Procesando...');
        }

      } catch (error) {
        console.error('❌ Error en polling:', error);
        stopPolling('Error en fetch');
      }
    };

    // Ejecutar primera vez inmediatamente
    pollRun();

    // Configurar intervalo
    const interval = setInterval(pollRun, 2000);
    pollingIntervalRef.current = interval;
  };

  // Función para procesar mensaje con IA y extraer datos
  const processMessageWithAI = async (messageText) => {
    try {
      let currentData = {};
      if (Array.isArray(quoteData) && quoteData.length > 0) {
        currentData = quoteData[0];
      } else if (quoteData && typeof quoteData === 'object') {
        currentData = quoteData;
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const response = await fetch('/api/chat/extract-quote-data', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          message: messageText,
          current_data: currentData,
          thread_id: threadId,
          client_id: String(clientData.clientId)
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      // PROCESAR DATOS EXTRAÍDOS
      // 🔴 CRÍTICO: NO sobrescribir si ya hay múltiples rutas detectadas
      if (result.data?.extracted && Object.keys(result.data.extracted).length > 0) {
        const extractedData = result.data.extracted;

        const mappedData = {
          ciudadOrigen: extractedData.origen || currentData.ciudadOrigen || currentData.ciudad_origen || null,
          ciudad_origen: extractedData.origen || currentData.ciudadOrigen || currentData.ciudad_origen || null,
          ciudadDestino: extractedData.destino || currentData.ciudadDestino || currentData.ciudad_destino || null,
          ciudad_destino: extractedData.destino || currentData.ciudadDestino || currentData.ciudad_destino || null,
          pesoMercancia: extractedData.peso || currentData.pesoMercancia || currentData.peso_mercancia || null,
          peso_mercancia: extractedData.peso || currentData.pesoMercancia || currentData.peso_mercancia || null,
          cantidadMercancia: extractedData.cantidad || currentData.cantidadMercancia || currentData.cantidad_unidades || null,
          cantidad: extractedData.cantidad || currentData.cantidadMercancia || currentData.cantidad || null,
          contenedor: extractedData.contenedor || currentData.contenedor || null,
          producto: extractedData.producto || currentData.producto || null,
          tipo_producto: extractedData.producto || currentData.tipo_producto || null,
          tipo_embajale: extractedData.contenedor || currentData.tipo_embajale || currentData.empaque || null,
          valorMercancia: extractedData.valor || currentData.valorMercancia || currentData.valor_mercancia || null,
          valor_declarado: extractedData.valor || currentData.valorMercancia || currentData.valor_declarado || null,
          vehiculo_requerido: extractedData.vehiculo || currentData.claseVehiculo || currentData.vehiculo_requerido || null,
          claseVehiculo: extractedData.vehiculo || currentData.claseVehiculo || currentData.vehiculo_requerido || null,
          incoterm: extractedData.incoterm || currentData.incoterm || null,
          observaciones: extractedData.observaciones || currentData.observaciones || null
        };

        // 🆕 NO sobrescribir si ya hay múltiples rutas (evitar perder multi-ruta)
        setQuoteData(prev => {
          if (Array.isArray(prev) && prev.length > 1) {
            console.log('⚠️ processMessageWithAI: Ya hay', prev.length, 'rutas - NO sobrescribiendo');
            return prev; // Mantener las rutas existentes
          }
          console.log('✅ processMessageWithAI: Actualizando con nuevos datos extraídos');
          return [mappedData];
        });
      }

      if (result.data?.message && onUpdateMessages) {
        setTimeout(() => {
          onUpdateMessages(prev => [...prev, { role: 'assistant', text: result.data.message, created_at: new Date().toLocaleTimeString() }]);
        }, 200);
      }

      console.log('========== FIN processMessageWithAI ==========\n');
      return result.data;
    } catch (error) {
      console.error('❌❌❌ ERROR EN processMessageWithAI:', error);
      console.error('Stack trace:', error.stack);
      return null;
    }
  };

  const handleSendMessage = async (retryAttempt = false) => {
    if (!inputMessage.trim()) return;
    if (!clientData.clientId) return;
    if (isSending || sendingRef.current) return;
    if (currentRunId || processingMessage) return;

    const messageText = inputMessage.trim();

    // 🔍 BÚSQUEDA PROACTIVA DE PRODUCTOS - Busca CUALQUIER producto mencionado
    const buscarProductoProactivamente = async (texto) => {
      // Regex mejorado para capturar productos mencionados después de palabras clave
      const regex = /(?:toneladas de|tonelada de|kilos de|kilo de|kg de|transportar|llevar|enviar|envío de|envio de)\s+([a-záéíóúñ\s]+?)(?:\s+(?:por|en|con|desde|hacia|para|,|\.)|$)/gi;

      let posibleProducto = null;
      let match;

      // Buscar el primer producto mencionado
      while ((match = regex.exec(texto)) !== null) {
        const producto = match[1].trim();
        // Filtrar palabras muy cortas o comunes que no son productos
        if (producto.length > 2 && !['por', 'con', 'sin', 'para', 'desde', 'hacia'].includes(producto.toLowerCase())) {
          posibleProducto = producto;
          break;
        }
      }

      // Si se detectó un posible producto y no hay producto seleccionado, buscar en BD
      if (posibleProducto && !selectedProduct) {
        console.log('🔍 BÚSQUEDA PROACTIVA - Detectado:', posibleProducto);

        try {
          const response = await fetch('/api/mcp/search-products', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ query: posibleProducto })
          });

          const result = await response.json();
          console.log('📦 Resultado búsqueda proactiva:', result);

          if (result.success && result.productos && result.productos.length > 0) {
            const producto = result.productos[0];

            if (result.match_type === 'exact') {
              console.log('✅ MATCH EXACTO - Auto-seleccionando:', producto);

              setSelectedProduct({
                codigo: producto.codigo,
                nombre: producto.nombre,
                producto_codigo: producto.codigo
              });

              setQuoteData(prev => ({
                ...prev,
                producto: producto.nombre,
                producto_codigo: producto.codigo,
                tipo_producto: producto.nombre
              }));
            } else if (result.match_type === 'partial') {
              console.log('⚠️ MATCH PARCIAL - Mostrando sugerencia:', producto);

              // Auto-seleccionar el más parecido
              setSelectedProduct({
                codigo: producto.codigo,
                nombre: producto.nombre,
                producto_codigo: producto.codigo
              });

              setQuoteData(prev => ({
                ...prev,
                producto: producto.nombre,
                producto_codigo: producto.codigo,
                tipo_producto: producto.nombre
              }));

              // Mostrar mensaje informativo
              if (onUpdateMessages) {
                setTimeout(() => {
                  onUpdateMessages(prevMessages => [
                    ...prevMessages,
                    {
                      role: 'assistant',
                      text: `📦 Producto similar encontrado: "${producto.nombre}" (buscaste: "${posibleProducto}")`,
                      created_at: new Date().toLocaleTimeString()
                    }
                  ]);
                }, 300);
              }
            }
          } else if (result.match_type === 'none') {
            console.warn('❌ No se encontró ningún producto similar a:', posibleProducto);
          }
        } catch (err) {
          console.error('Error búsqueda proactiva:', err);
        }
      }
    };

    await buscarProductoProactivamente(messageText);

    setIsSending(true);
    sendingRef.current = true;
    setProcessingProgress(10);

    const activeThreadId = threadId; // <-- only use local thread id

    try {
      if (activeThreadId) {
        try {
          const groupId = clientData?.groupId;
          const url = groupId
            ? `/api/chat/messages/${activeThreadId}?group_id=${groupId}`
            : `/api/chat/messages/${activeThreadId}`;

          console.log('🔍 Verificando mensajes existentes:', { activeThreadId, groupId });

          const existingMessagesResponse = await fetch(url, {
            headers: { Accept: 'application/json' }
          });
          const existingData = await existingMessagesResponse.json();
          if (existingData.success && existingData.data.messages) {
            const lastUserMessage = existingData.data.messages
              .filter(msg => msg.role === 'user')
              .pop();
            if (lastUserMessage && lastUserMessage.text === messageText) {
              await checkForOrphanMessages(activeThreadId);
              setIsSending(false);
              return;
            }
          }
        } catch (error) {
          console.warn('Error checking existing messages:', error);
        }
      }

      onSendMessage(messageText);

      const thinkingMessage = {
        role: 'assistant',
        text: stripMarkdown('Procesando tu solicitud...'),
        created_at: new Date().toLocaleTimeString(),
        status: 'thinking',
        isTemporary: true
      };
      setProcessingMessage(thinkingMessage);
      setProcessingProgress(30);

      // 🆕 Procesar mensaje con IA en paralelo (sin esperar respuesta)
      // Esto extrae datos y actualiza el preview sin bloquear el chat
      processMessageWithAI(messageText)
        .then(result => console.log('processMessageWithAI completado:', result))
        .catch(err => console.error('ERROR en processMessageWithAI:', err));

      const response = await fetch('/api/chat/quote', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          Accept: 'application/json'
        },
        body: JSON.stringify({
          message: messageText,
          thread_id: activeThreadId || null, // <-- never send clientData.threadId
          client_id: clientData.clientId,
          group_id: clientData.groupId || null, // 🆕 Enviar group_id para guardar en conversation_messages
          type_business: clientData.typeBusiness || typeBusiness,
          selected_route_index: selectedRouteIndex, // 🆕 Índice de ruta seleccionada para edición
          existing_routes_count: Array.isArray(quoteData) ? quoteData.length : (quoteData ? 1 : 0) // 🆕 Cuántas rutas ya existen
        })
      });

      let data;
      if (response.ok || response.status === 409 || response.status === 503) {
        data = await response.json();
      } else if (response.status === 500) {
        setInputMessage(messageText);
        setProcessingMessage(null);
        if (onUpdateMessages) {
          onUpdateMessages(prev => [
            ...prev,
            {
              role: 'system',
              text: '⚠️ El servidor está experimentando demoras. Intenta nuevamente en unos momentos.',
              created_at: new Date().toLocaleTimeString(),
              status: 'error'
            }
          ]);
        }
        return;
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      if (data.success) {
        setInputMessage('');

        if (data.data.thread_id && !activeThreadId) {
          setThreadId(data.data.thread_id);
        }

        // 🔴 PRIMERO: ACTUALIZAR MENSAJES DEL CHAT INMEDIATAMENTE
        // Esto debe ejecutarse ANTES de cualquier otra lógica para garantizar que el usuario vea la respuesta
        if (data.data.messages && Array.isArray(data.data.messages) && data.data.messages.length > 0) {
          console.log('💬 ACTUALIZANDO MENSAJES:', data.data.messages.length, 'mensajes recibidos');
          console.log('📝 Primer mensaje:', data.data.messages[0]);
          console.log('📝 Último mensaje:', data.data.messages[data.data.messages.length - 1]);

          // El backend ya devuelve los mensajes en el formato correcto {role, text, created_at}
          // Solo necesitamos pasarlos directamente
          if (onUpdateMessages) {
            onUpdateMessages(data.data.messages);
            console.log('✅ Mensajes del chat actualizados');
          }
        }

        // 🆕 MOSTRAR PRODUCTOS PENDIENTES COMO BOTONES CLICKEABLES
        if (data.data.productos_pendientes && Array.isArray(data.data.productos_pendientes) && data.data.productos_pendientes.length > 0) {
          console.log('🎯 Productos pendientes para selección:', data.data.productos_pendientes);

          if (onUpdateMessages) {
            onUpdateMessages(prev => [
              ...prev,
              {
                role: 'assistant',
                text: 'Selecciona el producto que deseas usar:',
                created_at: new Date().toLocaleTimeString(),
                toolCallData: {
                  isToolCall: true,
                  type: 'productos',
                  content: (
                    <div className="space-y-2 mt-2">
                      {data.data.productos_pendientes.map((prod, idx) => (
                        <button
                          key={idx}
                          className="block w-full text-left px-4 py-3 rounded-lg bg-gray-50 hover:bg-orange-100 border border-gray-200 hover:border-orange-300 transition-all duration-200"
                          onClick={() => {
                            console.log('✅ Producto seleccionado:', prod);
                            console.log('📍 selectedRouteIndex actual:', selectedRouteIndexRef.current);

                            // Actualizar quoteData con el producto seleccionado
                            // FIX: Usar ref para obtener el valor actual (evitar closure obsoleto)
                            const currentRouteIndex = selectedRouteIndexRef.current;
                            setQuoteData(prev => {
                              if (Array.isArray(prev) && prev.length > 0) {
                                // Si hay una ruta seleccionada, actualizar SOLO esa
                                if (currentRouteIndex !== null && currentRouteIndex < prev.length) {
                                  console.log(`🎯 Actualizando SOLO ruta ${currentRouteIndex + 1}`);
                                  return prev.map((route, routeIdx) => {
                                    if (routeIdx === currentRouteIndex) {
                                      return {
                                        ...route,
                                        producto: prod.nombre,
                                        producto_codigo: prod.codigo,
                                        tipo_producto: prod.nombre
                                      };
                                    }
                                    return route; // Las demás rutas no se modifican
                                  });
                                }
                                // Si no hay ruta seleccionada, aplicar a todas (comportamiento original)
                                return prev.map(route => ({
                                  ...route,
                                  producto: prod.nombre,
                                  producto_codigo: prod.codigo,
                                  tipo_producto: prod.nombre
                                }));
                              } else if (prev && typeof prev === 'object') {
                                return {
                                  ...prev,
                                  producto: prod.nombre,
                                  producto_codigo: prod.codigo,
                                  tipo_producto: prod.nombre
                                };
                              }
                              return {
                                producto: prod.nombre,
                                producto_codigo: prod.codigo,
                                tipo_producto: prod.nombre
                              };
                            });

                            // Enviar selección al backend
                            // 🆕 FIX CRÍTICO: Usar selectedRouteIndexRef.current para obtener valor actualizado
                            const currentSelectedRouteIndex = selectedRouteIndexRef.current;
                            
                            console.log('🚀🚀 ENVIANDO SELECCIÓN DE PRODUCTO AL BACKEND', {
                              producto: prod.nombre,
                              opcion: idx + 1,
                              selectedRouteIndex_ref: currentSelectedRouteIndex,
                              selectedRouteIndex_state: selectedRouteIndex,
                              thread_id: activeThreadId || data.data.thread_id,
                              '⚠️ CRÍTICO': currentSelectedRouteIndex !== null 
                                ? `Producto se aplicará SOLO a Ruta ${currentSelectedRouteIndex + 1}` 
                                : 'NO HAY RUTA SELECCIONADA - se aplicará a primera sin producto'
                            });
                            
                            fetch('/api/chat/quote', {
                              method: 'POST',
                              headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                                Accept: 'application/json'
                              },
                              body: JSON.stringify({
                                message: `opción ${idx + 1}`,
                                thread_id: activeThreadId || data.data.thread_id,
                                client_id: clientData.clientId,
                                group_id: clientData.groupId || null,
                                type_business: clientData.typeBusiness || typeBusiness,
                                selected_route_index: currentSelectedRouteIndex, // 🆕 FIX: Usar valor actualizado de ref
                                existing_routes_count: Array.isArray(quoteData) ? quoteData.length : (quoteData ? 1 : 0) // 🆕 FIX: Pasar cantidad de rutas
                              })
                            }).then(res => res.json()).then(result => {
                              console.log('✅ Selección enviada al backend:', result);
                              if (result.data?.messages && onUpdateMessages) {
                                onUpdateMessages(result.data.messages);
                              }
                              // 🆕 FIX: Actualizar quoteData con extracted_data del backend
                              if (result.data?.extracted_data) {
                                const backendData = result.data.extracted_data;
                                console.log('📦 Actualizando quoteData con datos del backend:', backendData);

                                const routesArray = Array.isArray(backendData) ? backendData :
                                  (backendData && typeof backendData === 'object' && backendData[0] ?
                                    Object.values(backendData).filter(v => typeof v === 'object' && v.origen) :
                                    [backendData]);

                                if (routesArray.length > 0 && setQuoteData) {
                                  setQuoteData(prev => {
                                    if (!Array.isArray(prev)) return routesArray;
                                    return prev.map((route, idx) => {
                                      const newRoute = routesArray[idx];
                                      if (newRoute) {
                                        return {
                                          ...route,
                                          producto: newRoute.producto_nombre || newRoute.producto || route.producto,
                                          producto_codigo: newRoute.producto_codigo || route.producto_codigo,
                                          tipo_producto: newRoute.producto_nombre || newRoute.producto || route.tipo_producto
                                        };
                                      }
                                      return route;
                                    });
                                  });
                                }
                              }
                            });

                            // Agregar mensaje de confirmación
                            onUpdateMessages(prev => [
                              ...prev,
                              {
                                role: 'user',
                                text: `Seleccioné: ${prod.nombre}`,
                                created_at: new Date().toLocaleTimeString()
                              }
                            ]);
                          }}
                        >
                          <div className="font-semibold text-gray-900">{prod.nombre}</div>
                          <div className="text-xs text-gray-500 mt-1">Código: {prod.codigo}</div>
                        </button>
                      ))}
                    </div>
                  )
                }
              }
            ]);
          }
        }

        // Limpiar mensaje de procesamiento
        setProcessingMessage(null);

        // DEBUG - Ver respuesta del backend
        console.log('🔍 RESPUESTA BACKEND:', JSON.stringify(data.data, null, 2));
        console.log('📦 extracted_data:', data.data.extracted_data);

        // Procesar datos extraídos para llenar campos
        // 🔴 SOLO procesar si hay datos reales (no array vacío ni objeto vacío)
        const hasRealExtractedData = data.data.extracted_data && (
          (Array.isArray(data.data.extracted_data) && data.data.extracted_data.length > 0) ||
          (!Array.isArray(data.data.extracted_data) && Object.keys(data.data.extracted_data).length > 0)
        );

        if (hasRealExtractedData && setQuoteData) {
          console.log('✅ Datos extraídos recibidos, auto-llenando campos:', data.data.extracted_data);

          // 🆕 DETECTAR SI ES ARRAY (MULTI-RUTA) O OBJETO (RUTA ÚNICA)
          let extractedData = data.data.extracted_data;

          console.log('🔍 Tipo de extractedData antes de procesar:', {
            tipo: typeof extractedData,
            esArray: Array.isArray(extractedData),
            valor: extractedData
          });

          // 🆕 GARANTIZAR QUE SEA UN ARRAY DE RUTAS
          let routesArray = [];

          console.log('🔍 Analizando estructura de extracted_data...');
          console.log('  - Tipo:', typeof extractedData);
          console.log('  - Es Array:', Array.isArray(extractedData));
          console.log('  - Keys:', extractedData ? Object.keys(extractedData) : 'N/A');
          console.log('  - Valor:', JSON.stringify(extractedData, null, 2));

          if (Array.isArray(extractedData) && extractedData.length > 0) {
            // Ya es un array nativo
            routesArray = extractedData.filter(item => item && typeof item === 'object');
            console.log('✅ extracted_data es ARRAY nativo con', routesArray.length, 'ruta(s)');
          } else if (extractedData && typeof extractedData === 'object') {
            const keys = Object.keys(extractedData);

            // 🆕 DETECTAR MULTI-RUTA: Buscar claves que sean objetos con datos de ruta
            const routeKeys = keys.filter(k => {
              const val = extractedData[k];
              return val && typeof val === 'object' &&
                (val.origen || val.destino || val.ciudad_origen || val.ciudad_destino || val.peso);
            });

            if (routeKeys.length > 0) {
              // Es multi-ruta (ej: {"0": {...ruta1}, "1": {...ruta2}, "producto": "..."})
              console.log('✅ Detectadas', routeKeys.length, 'rutas en objeto:', routeKeys);

              // Ordenar por clave numérica si son números, o por orden de aparición
              routeKeys.sort((a, b) => {
                const numA = parseInt(a);
                const numB = parseInt(b);
                if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
                return 0;
              });

              routesArray = routeKeys.map(k => extractedData[k]);
              console.log('📊 Rutas extraídas:', routesArray);
            } else if (extractedData.origen || extractedData.destino || extractedData.ciudad_origen || extractedData.ciudad_destino) {
              // Es un único objeto de ruta
              console.log('✅ extracted_data es OBJETO único de ruta');
              routesArray = [extractedData];
            } else {
              console.warn('⚠️ extracted_data no contiene datos de ruta válidos:', keys);
              routesArray = [];
            }
          } else if (typeof extractedData === 'string' && extractedData.trim().length > 0) {
            // Por si acaso es un STRING JSON
            try {
              const parsed = JSON.parse(extractedData);
              routesArray = Array.isArray(parsed) ? parsed : [parsed];
              console.log('✅ extracted_data era STRING JSON - parseado');
            } catch (e) {
              console.error('❌ No se puede parsear extracted_data como JSON:', e);
              routesArray = [];
            }
          } else {
            console.warn('⚠️ extracted_data está vacío o es inválido');
            routesArray = [];
          }

          console.log(`🛣️ TOTAL: ${routesArray.length} ruta(s) para procesar`);
          if (routesArray.length > 1) {
            console.log('🎯 MULTI-RUTA DETECTADA');
          }

          console.log(`🛣️ Procesando ${routesArray.length} ruta(s) extraídas:`, routesArray);

          // 🔴 CRÍTICO: MAPEAR CADA RUTA CON LOGGING DETALLADO
          const mappedRoutes = routesArray.map((route, idx) => {
            const mapped = {
              ciudadOrigen: route.origen || route.ciudad_origen || null,
              ciudad_origen: route.origen || route.ciudad_origen || null,
              ciudadDestino: route.destino || route.ciudad_destino || null,
              ciudad_destino: route.destino || route.ciudad_destino || null,
              pesoMercancia: route.peso || route.peso_kg || route.peso_mercancia || null,
              peso_mercancia: route.peso || route.peso_kg || route.peso_mercancia || null,
              cantidadMercancia: route.cantidad || route.cantidad_unidades || null,
              cantidad: route.cantidad || route.cantidad_unidades || null,
              contenedor: route.contenedor || route.tipo_contenedor || route.empaque || null,
              tipo_embajale: route.contenedor || route.tipo_contenedor || route.empaque || route.tipo_embajale || null,
              producto: route.producto_nombre || route.producto || route.tipo_producto || null, // 🆕 Priorizar producto_nombre
              tipo_producto: route.producto_nombre || route.producto || route.tipo_producto || null,
              producto_codigo: route.producto_codigo || null,
              valorMercancia: route.valor || route.valor_mercancia || route.valor_declarado || null,
              valor_declarado: route.valor || route.valor_mercancia || route.valor_declarado || null,
              vehiculo: route.vehiculo || null, // Para compatibilidad
              claseVehiculo: route.vehiculo || route.claseVehiculo || route.vehiculo_requerido || null, // 🆕 AGREGAR claseVehiculo para el panel
              vehiculo_requerido: route.vehiculo || route.claseVehiculo || route.vehiculo_requerido || null, // 🆕 Para el panel izquierdo
              incoterm: route.incoterm || null,
              observaciones: route.observaciones || null,
              empaque: route.empaque || null,
              empaque_id: route.empaque_id || null
            };

            console.log(`📍 Ruta ${idx + 1} mapeada:`, {
              origen: mapped.ciudadOrigen,
              destino: mapped.ciudadDestino,
              peso: mapped.pesoMercancia,
              cantidad: mapped.cantidadMercancia,
              contenedor: mapped.contenedor,
              producto: mapped.producto,
              vehiculo: mapped.vehiculo,
              valor: mapped.valorMercancia,
              observaciones: mapped.observaciones
            });

            return mapped;
          });

          console.log('✅ TODAS LAS RUTAS MAPEADAS:', mappedRoutes);

          // 🔴 CRÍTICO: ACTUALIZAR QUOTEDATA HACIENDO MERGE CON DATOS EXISTENTES
          if (mappedRoutes.length > 0) {
            console.log('🚀 Llamando setQuoteData con merge:', mappedRoutes);
            setQuoteData(prev => {
              // Si no hay datos previos, usar los nuevos directamente (sanitizados)
              if (!prev || (Array.isArray(prev) && prev.length === 0) || (typeof prev === 'object' && Object.keys(prev).length === 0)) {
                console.log('🆕 No hay datos previos, usando nuevos directamente (sanitizados)');
                return mappedRoutes.map(route => sanitizeRouteData(route, {}));
              }

              // Si hay datos previos, hacer merge campo por campo
              const prevArray = Array.isArray(prev) ? prev : [prev];

              const merged = mappedRoutes.map((newRoute, idx) => {
                const existingRoute = prevArray[idx] || {};

                // 🆕 PRIMERO: Sanitizar la ruta nueva para evitar ciudades inválidas
                const sanitizedNewRoute = sanitizeRouteData(newRoute, existingRoute);

                const mergedRoute = { ...existingRoute };

                // Solo sobrescribir campos que tienen valor en los nuevos datos (sanitizados)
                Object.keys(sanitizedNewRoute).forEach(key => {
                  if (sanitizedNewRoute[key] !== null && sanitizedNewRoute[key] !== undefined && sanitizedNewRoute[key] !== '') {
                    mergedRoute[key] = sanitizedNewRoute[key];
                  }
                });

                console.log(`📍 Ruta ${idx + 1} mergeada:`, {
                  prev: existingRoute,
                  new: sanitizedNewRoute,
                  merged: mergedRoute
                });

                return mergedRoute;
              });

              console.log('✅ DATOS MERGEADOS:', merged);
              return merged;
            });
            console.log('✅ SETQUOTEDATA EJECUTADO CON MERGE');
          } else {
            console.warn('⚠️ No hay rutas mapeadas para actualizar');
          }

          // Evitar procesamiento duplicado
          // Crear un hash simple de los datos para comparar
          const dataHash = JSON.stringify(routesArray.map(r => `${r.origen || r.ciudad_origen}-${r.destino || r.ciudad_destino}-${r.peso_kg}-${r.producto || r.producto_nombre || ''}`));
          if (lastProcessedDataHashRef.current === dataHash) {
            if (process.env.NODE_ENV === 'development') {
              console.log('⚠️ Datos ya procesados, saltando duplicación');
            }
            return; // No procesar los mismos datos dos veces
          }
          lastProcessedDataHashRef.current = dataHash;

          // Auto-buscar producto de CADA ruta (no solo la primera)
          // Recopilar todos los productos únicos de las rutas
          const productosUnicos = [...new Set(routesArray.map(r => r.producto).filter(Boolean))];
          console.log('🔍 Productos únicos en rutas:', productosUnicos);

          // Buscar y validar cada producto por separado
          productosUnicos.forEach((productoTexto, idx) => {
            console.log(`🔍 Buscando producto ${idx + 1}/${productosUnicos.length}: "${productoTexto}"`);

            // Llamar al backend para buscar el producto
            fetch('/api/mcp/search-products', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
              },
              body: JSON.stringify({ query: productoTexto })
            })
              .then(res => res.json())
              .then(result => {
                console.log(`📦 Resultado búsqueda "${productoTexto}":`, result);

                if (result.success && result.productos && result.productos.length > 0) {
                  if (result.match_type === 'exact') {
                    // Coincidencia exacta - auto-actualizar SOLO las rutas con este producto
                    const producto = result.productos[0];
                    console.log(`✅ Coincidencia EXACTA para "${productoTexto}":`, producto);
                    console.log('📍 selectedRouteIndex al procesar producto:', selectedRouteIndex);

                    // Si es el primer producto (o único), actualizar selectedProduct global
                    if (idx === 0) {
                      setSelectedProduct({
                        codigo: producto.codigo,
                        nombre: producto.nombre,
                        producto_codigo: producto.codigo
                      });
                    }

                    // Si hay ruta seleccionada, actualizar SOLO esa. 
                    // Si no, actualizar rutas que tenían ESTE producto original
                    // FIX: Usar ref para obtener valor actual (evitar closure obsoleto)
                    const currentRouteIndex = selectedRouteIndexRef.current;
                    setQuoteData(prev => {
                      if (Array.isArray(prev) && prev.length > 0) {
                        // Si hay ruta seleccionada para edición, actualizar SOLO esa ruta
                        if (currentRouteIndex !== null && currentRouteIndex < prev.length) {
                          const updated = prev.map((route, idx) => {
                            if (idx === currentRouteIndex) {
                              return {
                                ...route,
                                producto: producto.nombre,
                                producto_codigo: producto.codigo,
                                tipo_producto: producto.nombre
                              };
                            }
                            return route; // No modificar otras rutas
                          });
                          return updated;
                        }

                        // Sin ruta seleccionada - buscar rutas que tenían este producto
                        const updated = prev.map(route => {
                          // Solo actualizar si esta ruta tenía el producto que buscamos
                          const routeProducto = (route.producto || '').toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                          const buscando = productoTexto.toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                          if (routeProducto === buscando || route.producto?.toUpperCase() === productoTexto.toUpperCase()) {
                            console.log(`  ✅ Actualizando ruta con ${route.producto} -> ${producto.nombre}`);
                            return {
                              ...route,
                              producto: producto.nombre,
                              producto_codigo: producto.codigo,
                              tipo_producto: producto.nombre
                            };
                          }
                          // No modificar rutas con otro producto
                          return route;
                        });
                        return updated;
                      }
                      return prev;
                    });
                  } else if (result.match_type === 'partial') {
                    // Sugerencias - mostrar al usuario para que elija
                    console.log('⚠️ No hay coincidencia exacta - Mostrando sugerencias:', result.productos);

                    // Agregar mensaje al chat con sugerencias
                    if (onUpdateMessages) {
                      onUpdateMessages(prev => [
                        ...prev,
                        {
                          role: 'assistant',
                          text: `No encontré "${productoTexto}" exactamente en el catálogo. ¿Te refieres a alguno de estos?`,
                          created_at: new Date().toLocaleTimeString(),
                          toolCallData: {
                            isToolCall: true,
                            type: 'productos',
                            content: (
                              <div className="space-y-2">
                                {result.productos.map((prod, idx) => (
                                  <button
                                    key={idx}
                                    className="block w-full text-left px-4 py-2 rounded bg-gray-100 hover:bg-orange-100 transition-colors"
                                    onClick={() => {
                                      console.log('✅ Usuario seleccionó sugerencia:', prod);
                                      console.log('📍 selectedRouteIndex actual:', selectedRouteIndex);

                                      // 1. Actualizar selectedProduct (global para referencia)
                                      setSelectedProduct({
                                        codigo: prod.codigo,
                                        nombre: prod.nombre,
                                        producto_codigo: prod.codigo
                                      });

                                      // 2. Actualizar routes con el producto seleccionado
                                      // FIX: Usar ref para obtener valor actual (evitar closure obsoleto)
                                      const currentRouteIndex = selectedRouteIndexRef.current;
                                      setQuoteData(prev => {
                                        if (Array.isArray(prev) && prev.length > 0) {
                                          // Si hay ruta seleccionada, actualizar SOLO esa ruta
                                          if (currentRouteIndex !== null && currentRouteIndex < prev.length) {
                                            const updated = prev.map((route, idx) => {
                                              if (idx === currentRouteIndex) {
                                                return {
                                                  ...route,
                                                  producto: prod.nombre,
                                                  producto_codigo: prod.codigo,
                                                  tipo_producto: prod.nombre
                                                };
                                              }
                                              return route;
                                            });
                                            return updated;
                                          }
                                          // Sin ruta seleccionada - actualizar todas
                                          const updated = prev.map(route => ({
                                            ...route,
                                            producto: prod.nombre,
                                            producto_codigo: prod.codigo,
                                            tipo_producto: prod.nombre
                                          }));
                                          return updated;
                                        } else if (prev && typeof prev === 'object' && Object.keys(prev).length > 0) {
                                          const updated = {
                                            ...prev,
                                            producto: prod.nombre,
                                            producto_codigo: prod.codigo,
                                            tipo_producto: prod.nombre
                                          };
                                          console.log('✅ Objeto actualizado:', updated);
                                          return updated;
                                        } else {
                                          // Si NO hay datos previos, crear objeto inicial
                                          const newData = {
                                            producto: prod.nombre,
                                            producto_codigo: prod.codigo,
                                            tipo_producto: prod.nombre
                                          };
                                          console.log('🆕 Creando nuevo quoteData:', newData);
                                          return newData;
                                        }
                                      });
                                    }}
                                  >
                                    <div className="font-semibold text-gray-900">{prod.nombre}</div>
                                    <div className="text-xs text-gray-500">Código: {prod.codigo}</div>
                                  </button>
                                ))}
                              </div>
                            )
                          }
                        }
                      ]);
                    }
                  }
                } else {
                  console.warn('❌ Producto no encontrado en BD:', productoTexto, '- Se usará como texto personalizado');
                  // Crear mensaje informativo
                  if (onUpdateMessages) {
                    onUpdateMessages(prev => [
                      ...prev,
                      {
                        role: 'assistant',
                        text: `⚠️ El producto "${productoTexto}" no existe en nuestro catálogo. Se guardará como producto personalizado.`,
                        created_at: new Date().toLocaleTimeString(),
                      }
                    ]);
                  }
                }
              })
              .catch(err => {
                console.error('❌ Error buscando producto:', err);
              });
          });

          // 2️⃣ Actualizar quoteData - AHORA MANEJA MULTI-RUTA Y EDICIÓN INDIVIDUAL
          console.log('🚀 Antes de setQuoteData - routesArray:', routesArray, 'length:', routesArray.length);

          // 🆕 Detectar si el backend indica que fue edición de una sola ruta
          const backendIndicaSingleEdit = data.data.is_single_route_edit === true;
          const backendEditedRouteIndex = data.data.edited_route_index;

          console.log('🔧 Backend indica edición individual:', {
            is_single_route_edit: backendIndicaSingleEdit,
            edited_route_index: backendEditedRouteIndex
          });

          // 🆕 Detectar si el mensaje menciona una ruta específica para editar
          const mentionedRouteIndex = detectRouteFromMessage(messageText);
          if (mentionedRouteIndex !== null) {
            console.log(`🎯 Usuario mencionó Ruta ${mentionedRouteIndex + 1} - seleccionando automáticamente`);
            setSelectedRouteIndex(mentionedRouteIndex);
          }

          // 🆕 Si el backend ya procesó una edición de ruta individual, usar directamente los datos
          // PERO sanitizar para evitar ciudades inválidas (ej: "ENERO", "LAS" de fechas)
          if (backendIndicaSingleEdit && routesArray.length > 1) {
            console.log('✅ Backend ya procesó edición individual - usando datos directamente (con sanitización)');
            setQuoteData(prev => {
              const prevArray = Array.isArray(prev) ? prev : [];
              return routesArray.map((route, idx) => {
                const existingRoute = prevArray[idx] || {};
                const newRoute = {
                  ciudadOrigen: route.origen || route.ciudad_origen || null,
                  ciudad_origen: route.origen || route.ciudad_origen || null,
                  ciudadDestino: route.destino || route.ciudad_destino || null,
                  ciudad_destino: route.destino || route.ciudad_destino || null,
                  pesoMercancia: route.peso || route.peso_kg || route.peso_mercancia || null,
                  peso_mercancia: route.peso || route.peso_kg || route.peso_mercancia || null,
                  cantidadMercancia: route.cantidad || route.cantidad_unidades || null,
                  cantidad: route.cantidad || route.cantidad_unidades || null,
                  contenedor: route.contenedor || route.tipo_contenedor || route.empaque || null,
                  tipo_embajale: route.contenedor || route.tipo_contenedor || route.empaque || route.tipo_embajale || null,
                  producto: route.producto || route.tipo_producto || null,
                  tipo_producto: route.producto || route.tipo_producto || null,
                  producto_codigo: route.producto_codigo || null,
                  valorMercancia: route.valor || route.valor_mercancia || route.valor_declarado || null,
                  valor_declarado: route.valor || route.valor_mercancia || route.valor_declarado || null,
                  vehiculo: route.vehiculo || null,
                  claseVehiculo: route.vehiculo || route.claseVehiculo || route.vehiculo_requerido || null,
                  vehiculo_requerido: route.vehiculo || route.claseVehiculo || route.vehiculo_requerido || null,
                  incoterm: route.incoterm || null,
                  observaciones: route.observaciones || null,
                  empaque: route.empaque || null,
                  empaque_id: route.empaque_id || null
                };
                // 🆕 Sanitizar para evitar ciudades inválidas como "ENERO", "LAS"
                return sanitizeRouteData(newRoute, existingRoute);
              });
            });
            return; // No continuar con la lógica normal
          }

          setQuoteData(prev => {
            console.log('📝 Dentro de setQuoteData - routesArray:', routesArray.length, 'elementos');
            console.log('📍 selectedRouteIndex actual:', selectedRouteIndex);
            console.log('📍 mentionedRouteIndex detectado:', mentionedRouteIndex);

            // 🆕 Determinar qué índice de ruta usar para edición
            const editingRouteIndex = mentionedRouteIndex !== null ? mentionedRouteIndex : selectedRouteIndex;

            const mensajeLower = messageText.toLowerCase();

            // Detectar si menciona "NO incluye tara" o "sin tara"
            const noIncluyeTara =
              mensajeLower.includes('no incluye tara') ||
              mensajeLower.includes('sin tara') ||
              mensajeLower.includes('no incluir tara') ||
              mensajeLower.includes('peso sin tara') ||
              mensajeLower.includes('peso neto') ||
              mensajeLower.includes('tara no incluida');

            // 🔴 IMPORTANTE: El backend YA calcula la tara en MCPAssistantService.php
            // Si el backend ya la calculó, viene incluye_tara: true en los datos
            // NO debemos calcularla de nuevo en el frontend
            const backendYaAgregoTara = routesArray.some(route => route.incluye_tara === true);

            // Solo agregar tara en frontend si:
            // 1. Se menciona explícitamente en el mensaje
            // 2. NO dice que NO incluye
            // 3. El backend NO la agregó ya
            const mencionaTara = !noIncluyeTara && !backendYaAgregoTara && (
              mensajeLower.includes('incluir tara') ||
              mensajeLower.includes('incluye tara') ||
              mensajeLower.includes('tara incluida') ||
              mensajeLower.includes('suma tara') ||
              mensajeLower.includes('suma el tara') ||
              mensajeLower.includes('suma la tara') ||
              mensajeLower.includes('sumar tara') ||
              mensajeLower.includes('con tara') ||
              mensajeLower.includes('más tara') ||
              mensajeLower.includes('mas tara') ||
              mensajeLower.includes('agregar tara') ||
              mensajeLower.includes('agrega tara') ||
              mensajeLower.includes('añadir tara') ||
              mensajeLower.includes('añade tara') ||
              mensajeLower.includes('peso incluye tara') ||
              mensajeLower.includes('peso con tara') ||
              mensajeLower.includes('tara sumada')
            );

            console.log('🔍 Detección de TARA:', { mencionaTara, noIncluyeTara, backendYaAgregoTara, mensaje: mensajeLower.substring(0, 100) });

            const TARA_KG = 3400;

            // 🆕 Si hay una ruta seleccionada para edición y solo viene 1 ruta en los datos,
            // aplicar los cambios SOLO a esa ruta, manteniendo las demás intactas
            const prevArray = Array.isArray(prev) ? prev : (prev ? [prev] : []);
            const hasExistingRoutes = prevArray.length > 0;
            const isEditingSingleRoute = editingRouteIndex !== null && routesArray.length === 1 && hasExistingRoutes;

            console.log('🔧 Modo de edición:', {
              isEditingSingleRoute,
              editingRouteIndex,
              prevRoutesCount: prevArray.length,
              newRoutesCount: routesArray.length
            });

            if (isEditingSingleRoute && editingRouteIndex < prevArray.length) {
              // 🆕 EDICIÓN DE RUTA INDIVIDUAL - Fusionar cambios solo en la ruta seleccionada
              const editedRoute = routesArray[0];
              const updatedRoutes = prevArray.map((existingRoute, idx) => {
                if (idx === editingRouteIndex) {
                  // Esta es la ruta que se está editando - fusionar cambios SOLO de campos que vienen
                  const pesoBase = editedRoute.peso_kg ?? existingRoute.pesoMercancia ?? 0;
                  // Solo calcular tara en frontend si el backend no la calculó ya
                  const backendYaTieneTara = editedRoute.incluye_tara === true;
                  const pesoFinal = (mencionaTara && pesoBase > 0 && !backendYaTieneTara)
                    ? parseFloat(pesoBase) + TARA_KG
                    : pesoBase;

                  // Determinar si incluye tara
                  const incluyeTara = backendYaTieneTara || (mencionaTara && pesoBase > 0);

                  const mergedRoute = {
                    ...existingRoute,
                    // Solo sobrescribir si el valor viene definido (no undefined)
                    ...(editedRoute.origen !== undefined && { ciudadOrigen: editedRoute.origen }),
                    ...(editedRoute.destino !== undefined && { ciudadDestino: editedRoute.destino }),
                    ...(pesoFinal && { pesoMercancia: pesoFinal }),
                    ...(editedRoute.cantidad !== undefined && { cantidadMercancia: editedRoute.cantidad }),
                    // 🔧 FIX: Preservar valorMercancia - verificar múltiples nombres de propiedad
                    ...(editedRoute.valor_declarado !== undefined && { 
                      valorMercancia: editedRoute.valor_declarado,
                      valor_declarado: editedRoute.valor_declarado 
                    }),
                    ...(editedRoute.valor_mercancia !== undefined && !editedRoute.valor_declarado && { 
                      valorMercancia: editedRoute.valor_mercancia,
                      valor_declarado: editedRoute.valor_mercancia 
                    }),
                    ...(editedRoute.vehiculo !== undefined && { claseVehiculo: editedRoute.vehiculo }),
                    ...(editedRoute.empaque !== undefined && { empaque: editedRoute.empaque }),
                    ...(editedRoute.empaque_id !== undefined && { empaque_id: editedRoute.empaque_id }),
                    ...(editedRoute.producto !== undefined && {
                      producto: editedRoute.producto,
                      tipo_producto: editedRoute.producto
                    }),
                    incluye_tara: incluyeTara || existingRoute.incluye_tara || false,
                  };

                  console.log(`✏️ Ruta ${idx + 1} EDITADA - Campos actualizados:`, {
                    origen: editedRoute.origen !== undefined,
                    destino: editedRoute.destino !== undefined,
                    peso: editedRoute.peso_kg !== undefined,
                    cantidad: editedRoute.cantidad !== undefined,
                    producto: editedRoute.producto !== undefined
                  });
                  console.log('🔍 Resultado merge:', mergedRoute);
                  return mergedRoute;
                }
                // Rutas no editadas permanecen igual
                return existingRoute;
              });

              console.log(`📊 Rutas actualizadas (1 editada, ${updatedRoutes.length - 1} sin cambios):`, updatedRoutes);

              // Notificar al usuario
              if (onUpdateMessages) {
                setTimeout(() => {
                  onUpdateMessages(prevMessages => [
                    ...prevMessages,
                    {
                      role: 'system',
                      text: `✅ Ruta ${editingRouteIndex + 1} actualizada correctamente.`,
                      created_at: new Date().toLocaleTimeString()
                    }
                  ]);
                }, 300);
              }

              return updatedRoutes;
            }

            // 🆕 MODO NORMAL - Procesar CADA ruta del array
            // 🔴 IMPORTANTE: Si vienen múltiples rutas NUEVAS (ej: "crea dos rutas..."),
            // REEMPLAZAR las existentes en lugar de fusionar
            // Solo fusionar si es una corrección de ruta individual

            // Detectar si es una solicitud de NUEVAS rutas (menciona "crea", "nueva", "dos rutas", etc.)
            const esNuevasSolicitud =
              mensajeLower.includes('crea') ||
              mensajeLower.includes('nueva') ||
              mensajeLower.includes('dos rutas') ||
              mensajeLower.includes('tres rutas') ||
              mensajeLower.includes('2 rutas') ||
              mensajeLower.includes('3 rutas') ||
              mensajeLower.includes('la primera') ||
              (routesArray.length >= 2 && prevArray.length === 0);

            // Si es una nueva solicitud con múltiples rutas, NO fusionar con las anteriores
            const shouldReplace = esNuevasSolicitud && routesArray.length >= 2;

            console.log('🔍 Modo de procesamiento:', {
              esNuevasSolicitud,
              shouldReplace,
              routesArrayLength: routesArray.length,
              prevArrayLength: prevArray.length
            });

            const processedRoutes = routesArray.map((routeData, idx) => {
              const pesoBase = routeData.peso_kg || 0;
              const pesoFinal = (mencionaTara && pesoBase > 0)
                ? parseFloat(pesoBase) + TARA_KG
                : pesoBase;

              // Si es la primera ruta con tara, mostrar mensaje
              if (idx === 0 && mencionaTara && pesoBase > 0 && onUpdateMessages) {
                console.log(`🏋️ TARA DETECTADA - Sumando ${TARA_KG} kg al peso base ${pesoBase} kg`);
                setTimeout(() => {
                  onUpdateMessages(prevMessages => [
                    ...prevMessages,
                    {
                      role: 'assistant',
                      text: `✅ Tara agregada: ${parseFloat(pesoBase).toLocaleString('es-CO')} kg + 3,400 kg (tara) = ${pesoFinal.toLocaleString('es-CO')} kg total`,
                      created_at: new Date().toLocaleTimeString()
                    }
                  ]);
                }, 500);
              }

              // 🔴 Solo fusionar con existentes si NO es nueva solicitud
              const existingRoute = shouldReplace ? {} : (prevArray[idx] || {});

              // Determinar si incluye tara (ya sea del backend o calculada aquí)
              const incluyeTara = routeData.incluye_tara === true || (mencionaTara && pesoBase > 0);

              return {
                // Mantener datos existentes como base (solo si NO es reemplazo)
                ...existingRoute,
                // Solo actualizar campos que vienen del backend Y tienen valor
                // El backend puede enviar: origen/ciudad_origen, destino/ciudad_destino
                ciudadOrigen: routeData.origen || routeData.ciudad_origen || existingRoute.ciudadOrigen || null,
                ciudadDestino: routeData.destino || routeData.ciudad_destino || existingRoute.ciudadDestino || null,
                pesoMercancia: pesoFinal || routeData.peso_kg || existingRoute.pesoMercancia || null,
                cantidadMercancia: routeData.cantidad || existingRoute.cantidadMercancia || null,
                valorMercancia: routeData.valor_declarado || existingRoute.valorMercancia || null,
                claseVehiculo: routeData.vehiculo || existingRoute.claseVehiculo || null,
                empaque: routeData.empaque || existingRoute.empaque || null,
                empaque_id: routeData.empaque_id || existingRoute.empaque_id || null,
                producto: routeData.producto || routeData.tipo_producto || existingRoute.producto || null,
                tipo_producto: routeData.producto || routeData.tipo_producto || existingRoute.tipo_producto || null,
                volumen: routeData.volumen_m3 || existingRoute.volumen || null,
                tipo_contenedor: routeData.tipo_contenedor || existingRoute.tipo_contenedor || null,
                incluye_tara: incluyeTara || existingRoute.incluye_tara || false,
              };
            });

            console.log(`📊 ${processedRoutes.length} ruta(s) procesadas (${shouldReplace ? 'REEMPLAZO' : 'fusionadas'}):`, processedRoutes);

            // 🆕 SIEMPRE devolver array para consistencia con QuoteDetailsPanel
            return processedRoutes;
          });

          // NUEVO: Auto-seleccionar empaque del PRIMER elemento si viene con ID de la BD
          const firstExtracted = Array.isArray(data.data.extracted_data)
            ? data.data.extracted_data[0]
            : data.data.extracted_data;

          if (firstExtracted?.empaque && firstExtracted?.empaque_id) {
            const empaqueObj = {
              id: firstExtracted.empaque_id,
              nome: firstExtracted.empaque,
              nombre: firstExtracted.empaque
            };
            setSelectedEmpaque(empaqueObj);
            console.log('✅ Embalaje auto-seleccionado:', empaqueObj);
          }
        }

        // Solo activar polling si hay run_id Y no está completado
        if (data.data.run_id && !data.data.completed) {
          console.log('🔄 Iniciando polling porque run_id existe y no está completado');
          startPollingRun(data.data.thread_id || activeThreadId, data.data.run_id);
        } else {
          console.log('✅ Procesamiento completado - NO se necesita polling');
        }
      } else if (data.error === 'processing_active' || response.status === 409) {
        if (!retryAttempt) {
          try {
            await fetch('/api/chat/clear-thread', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
              },
              body: JSON.stringify({
                thread_id: activeThreadId || null,
                client_id: clientData.clientId
              })
            });
            setThreadId(null);
            await new Promise(resolve => setTimeout(resolve, 2000));
            return handleSendMessage(true);
          } catch (clearError) {
            console.error('Error clearing thread:', clearError);
          }
        }

        if (onUpdateMessages) {
          onUpdateMessages(prev => [
            ...prev,
            {
              role: 'system',
              text: '⏳ Hay un mensaje en proceso. Si persiste, usa “Cancelar” para reiniciar.',
              created_at: new Date().toLocaleTimeString(),
              status: 'waiting'
            }
          ]);
        }

        setProcessingMessage({
          role: 'system',
          text: '⚠️ Hay un procesamiento pendiente. Si tardó más de 30 segundos, cancela y reinicia.',
          created_at: new Date().toLocaleTimeString(),
          status: 'conflict',
          isTemporary: true
        });

        if (data.data?.active_run_id && data.data.thread_id) {
          startPollingRun(data.data.thread_id, data.data.active_run_id);
        }
      } else if (data.error === 'openai_unavailable' || response.status === 503) {
        setInputMessage(messageText);
        setProcessingMessage(null);
        if (onUpdateMessages) {
          onUpdateMessages(prev => [
            ...prev,
            {
              role: 'system',
              text: '⚠️ El servicio de IA no está disponible. Se procesará cuando vuelva a estar en línea.',
              created_at: new Date().toLocaleTimeString(),
              status: 'warning'
            }
          ]);
        }
        if (data.data?.active_run_id) {
          startPollingRun(data.data.thread_id, data.data.active_run_id);
        } else if (data.data?.thread_id) {
          await checkForOrphanMessages(data.data.thread_id);
        }
      } else {
        setInputMessage(messageText);
        setProcessingMessage(null);
        const errorText = data.error?.includes('timeout') || data.error?.includes('cURL')
          ? '⚠️ El servicio está con demoras. Intenta nuevamente en unos momentos.'
          : '⚠️ Ha ocurrido un error temporal. Por favor, intenta nuevamente.';
        if (onUpdateMessages) {
          onUpdateMessages(prev => [
            ...prev,
            {
              role: 'system',
              text: errorText,
              created_at: new Date().toLocaleTimeString(),
              status: 'error'
            }
          ]);
        }
      }
    } catch (error) {
      console.error('Error sending message:', error);
      setInputMessage(messageText);
      setProcessingMessage(null);
      let errorText = '⚠️ ';
      if (error.message?.includes('NetworkError')) {
        errorText += 'Error de conexión. Verifica tu internet e intenta de nuevo.';
      } else if (error.message?.includes('timeout')) {
        errorText += 'La conexión está tardando demasiado. Intenta nuevamente.';
      } else {
        errorText += 'Ha ocurrido un error de conexión. Intenta nuevamente.';
      }
      if (onUpdateMessages) {
        onUpdateMessages(prev => [
          ...prev,
          {
            role: 'system',
            text: errorText,
            created_at: new Date().toLocaleTimeString(),
            status: 'error'
          }
        ]);
      }
    } finally {
      setIsSending(false);
      sendingRef.current = false;
      setProcessingProgress(0);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();

      // Validación estricta ANTES de intentar enviar
      if (isSending || sendingRef.current || currentRunId || processingMessage) {
        console.log('⚠️ Enter bloqueado: hay procesamiento activo');
        return; // Bloquear completamente si hay procesamiento
      }

      // Solo permitir envío si no hay procesamiento activo
      if (clientData.clientId && inputMessage.trim()) {
        handleSendMessage();
      }
    }
  };

  const handleVoiceResult = (transcript) => {
    setInputMessage(transcript);
  };

  const handleCreateQuote = async () => {
    // 🔒 PROTECCIÓN TRIPLE contra doble click
    if (isSavingQuote.current || isCreatingQuote) {
      console.warn('⚠️ Ya se está guardando la cotización, ignorando click duplicado...');
      return;
    }

    // 🆕 Convertir a array si es objeto único
    const routesArray = Array.isArray(quoteData) ? quoteData : (quoteData ? [quoteData] : []);

    console.log('🔍 DEBUG handleCreateQuote - Estado inicial:', {
      quoteDataType: Array.isArray(quoteData) ? 'array' : typeof quoteData,
      quoteDataLength: Array.isArray(quoteData) ? quoteData.length : 1,
      routesArrayLength: routesArray.length,
      quoteData: JSON.stringify(quoteData, null, 2)
    });

    if (routesArray.length === 0) {
      alert('⚠️ No hay datos de cotización. Por favor completa la información en el chat.');
      return;
    }

    // Marcar como guardando (doble bloqueo)
    isSavingQuote.current = true;
    setIsCreatingQuote(true);
    console.log('🔒 Guardado iniciado - bloqueando doble click');

    // Validar que al menos la primera ruta tenga datos básicos
    const firstRoute = routesArray[0];
    const missingFields = [];

    if (!firstRoute.ciudadOrigen && !firstRoute.ciudad_origen && !firstRoute.origen) {
      missingFields.push('Ciudad de origen');
    }
    if (!firstRoute.ciudadDestino && !firstRoute.ciudad_destino && !firstRoute.destino) {
      missingFields.push('Ciudad de destino');
    }
    if (!firstRoute.pesoMercancia && !firstRoute.peso_mercancia && !firstRoute.peso_kg) {
      missingFields.push('Peso de mercancía');
    }

    // Validar producto (puede venir de selectedProduct o de la ruta)
    if (!selectedProduct && !firstRoute.producto && !firstRoute.tipo_producto) {
      missingFields.push('Producto');
    }

    // Si faltan datos, mostrar mensaje específico y desbloquear
    if (missingFields.length > 0) {
      isSavingQuote.current = false;
      setIsCreatingQuote(false);
      alert(`⚠️ FALTAN DATOS REQUERIDOS:\n\n${missingFields.map(f => `• ${f}`).join('\n')}\n\nPor favor completa la información en el chat.`);
      return;
    }

    if (!clientData.groupId) {
      isSavingQuote.current = false;
      setIsCreatingQuote(false);
      alert('No se encontró el grupo de cotización. Por favor recarga la página.');
      return;
    }

    // Tabla de capacidades de vehículos (kg)
    const vehicleCapacities = {
      'CAMIONETA': 2000,
      'TURBO': 4000,
      'SENCILLO': 9000,
      'PATINETA2': 20000,
      'PATINETA3': 25000,
      'TRACTOMULA 2': 30000,
      'TRACTOMULA 3': 34000
    };

    // Función para recomendar vehículo basado en peso
    const recommendVehicle = (weight) => {
      if (!weight || weight <= 0) return 'Sencillo';

      const sortedVehicles = Object.entries(vehicleCapacities)
        .sort((a, b) => a[1] - b[1]);

      for (const [vehicle, capacity] of sortedVehicles) {
        if (weight <= capacity) {
          return vehicle;
        }
      }

      return 'TRACTOMULA 3';
    };

    // 🆕 PROCESAR TODAS LAS RUTAS - cada una con sus datos explícitos
    const routesToSave = routesArray.map((route, idx) => {
      const pesoMercancia = route.pesoMercancia || route.peso_mercancia || route.peso_kg || 0;
      const vehiculoRecomendado = route.claseVehiculo || route.vehiculo || recommendVehicle(pesoMercancia);

      return {
        ciudad_origen: route.ciudadOrigen || route.ciudad_origen || route.origen,
        ciudad_destino: route.ciudadDestino || route.ciudad_destino || route.destino,
        peso_mercancia: pesoMercancia,
        cantidad: route.cantidadMercancia || route.cantidad || 1,
        tipo_embajale: route.empaque || selectedEmpaque?.Codigo || selectedEmpaque?.codigo || selectedEmpaque?.nome || 'Caja',
        tipo_producto: route.producto || route.tipo_producto || selectedProduct?.codigo || selectedProduct?.nombre || 'Mercancía general',
        vehiculo_requerido: vehiculoRecomendado,
        valor_declarado: route.valorMercancia || route.valor_declarado || 0,
      };
    });

    console.log('📤 Preparando guardar cotización MULTI-RUTA:', {
      groupId: clientData.groupId,
      clientId: clientData.clientId,
      routesCount: routesToSave.length,
      routes: routesToSave,
      selectedProduct,
      selectedEmpaque
    });

    // 🔍 DEBUG: Verificar si hay duplicados en routesToSave
    const routeSignatures = routesToSave.map(r => `${r.ciudad_origen}-${r.ciudad_destino}-${r.peso_mercancia}`);
    const uniqueSignatures = [...new Set(routeSignatures)];
    if (routeSignatures.length !== uniqueSignatures.length) {
      console.error('❌❌❌ RUTAS DUPLICADAS DETECTADAS EN routesToSave:', {
        total: routeSignatures.length,
        unique: uniqueSignatures.length,
        duplicates: routeSignatures.filter((sig, idx) => routeSignatures.indexOf(sig) !== idx)
      });
      alert('⚠️ Se detectaron rutas duplicadas. Por favor recarga la página y vuelve a intentar.');
      isSavingQuote.current = false;
      setIsCreatingQuote(false);
      return;
    }
    console.log('✅ Verificación de duplicados OK - todas las rutas son únicas');

    try {
      const saveResponse = await fetch('/api/chat/quote/save-routes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          group_id: clientData.groupId,
          routes: routesToSave
        })
      });

      const saveResult = await saveResponse.json();

      if (saveResult.success) {
        console.log('✅ Cotización guardada exitosamente:', saveResult.data);

        // Actualizar quoteData con los IDs retornados
        if (saveResult.data?.routes && saveResult.data.routes.length > 0) {
          if (Array.isArray(quoteData)) {
            // Si es array, actualizar cada elemento con su ID correspondiente
            setQuoteData(prev =>
              prev.map((route, idx) => ({
                ...route,
                id: saveResult.data.routes[idx]?.id || route.id
              }))
            );
          } else {
            // Si es objeto, actualizar con el primer ID
            const savedRoute = saveResult.data.routes[0];
            setQuoteData(prev => ({
              ...prev,
              id: savedRoute.id
            }));
          }
        }

        // Cerrar modal actual y avanzar al siguiente paso (EditRoutesModal)
        console.log('🔄 Avanzando al siguiente paso del flujo...');
        console.log('onNext disponible:', typeof onNext);

        if (onNext) {
          console.log('✅ Ejecutando onNext para abrir EditRoutesModal');
          onNext();
        } else {
          console.warn('⚠️ onNext no está definido - no se puede avanzar al siguiente modal');
        }
      } else {
        console.error('Error saving routes:', saveResult.error);
        alert('There was a problem saving routes. Please try again.');
      }
    } catch (err) {
      console.error('Network error saving routes:', err);
      alert('Network error while saving routes. Please retry.');
    } finally {
      // 🔓 Desbloquear después de guardar (exitoso o con error)
      isSavingQuote.current = false;
      setIsCreatingQuote(false);
      console.log('🔓 Guardado finalizado - desbloqueando');
    }
  };

  const canProceed = quoteData && quoteData.length > 0 &&
    quoteData.some(route => route.ciudad_origen && route.ciudad_destino);

  // Función para formatear mensajes de tool calls
  const formatToolCallMessage = (text, message = null, messageIndex = null) => {
    try {
      const data = JSON.parse(text);

      console.log('🔍 Parseando tool call:', { function: data.function, hasResult: !!data.result, data });

      // Si es una llamada a search_products con resultados (soportar múltiples variantes del nombre)
      const isSearchProducts = data.function && (
        data.function.toLowerCase().includes('searchproduct') ||
        data.function === 'search_products'
      );

      if (isSearchProducts && data.result?.productos && Array.isArray(data.result.productos)) {
        const productos = data.result.productos;

        // 🆕 Si ya se seleccionó algo LOCALMENTE en este mensaje, mostrar solo el resumen
        if (message?.localSelection) {
          return {
            isToolCall: true,
            type: 'productos',
            content: (
              <div className="text-sm bg-green-50 border border-green-200 rounded p-3 text-green-800 font-medium transition-all duration-500 ease-in-out">
                ✅ Producto seleccionado: <span className="font-bold">{message.localSelection.nombre}</span>
              </div>
            )
          };
        }

        console.log('✅ Mostrando productos:', productos.length, productos);
        return {
          isToolCall: true,
          type: 'productos',
          content: (
            <div className="space-y-3">
              <div className="font-semibold text-gray-900 mb-2">
                🔍 Encontré {productos.length} producto(s). Selecciona uno:
              </div>
              <div className="space-y-2">
                {productos.map((producto, idx) => {
                  return (
                    <div
                      key={idx}
                      className="border-2 rounded-lg p-4 transition-all cursor-pointer shadow-sm bg-orange-50 border-orange-300 hover:bg-orange-100 hover:border-orange-400 group"
                      onClick={() => {
                        setSelectedProduct(producto);
                        // Actualizar quoteData automáticamente
                        setQuoteData(prev => ({
                          ...prev,
                          producto: producto.nombre,
                          codigoProducto: producto.codigo
                        }));

                        // 🆕 Ocultar la lista y mostrar selección en este mensaje específico
                        if (messageIndex !== null) {
                          setMessages(prev => prev.map((msg, i) =>
                            i === messageIndex ? { ...msg, localSelection: producto } : msg
                          ));
                        }
                      }}
                    >
                      <div className="flex items-start gap-3">
                        <div className="w-5 h-5 rounded-full border-2 border-orange-400 flex items-center justify-center flex-shrink-0 mt-1 group-hover:bg-orange-600 group-hover:border-orange-600 transition-colors">
                          {/* Círculo vacío por defecto, efecto hover visual */}
                        </div>
                        <div className="flex-1">
                          <div className="font-bold text-orange-900 mb-2 text-base">
                            {producto.nombre}
                          </div>
                          <div className="text-xs text-gray-700 space-y-1">
                            <div className="flex items-center gap-1">
                              <span className="font-semibold">📦 Código:</span> {producto.codigo}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )
        };
      }

      // Si es una llamada a get_empaques con resultados
      if (data.function === 'get_empaques' && data.result?.success && data.result?.empaques) {
        const empaques = data.result.empaques;
        console.log('✅ Mostrando empaques:', empaques.length);
        return {
          isToolCall: true,
          type: 'empaques',
          content: (
            <div className="space-y-3">
              <div className="font-semibold text-gray-900 mb-2">
                📦 Tipos de embalaje disponibles. Selecciona uno:
              </div>
              <div className="grid grid-cols-2 gap-2">
                {empaques.map((empaque, idx) => {
                  const nombre = empaque.nome || empaque.nombre;
                  const isSelected = selectedEmpaque?.id === empaque.id;
                  return (
                    <div
                      key={idx}
                      className={`border-2 rounded-lg p-3 transition-all text-center cursor-pointer shadow-sm ${isSelected
                        ? 'bg-blue-100 border-blue-500 shadow-md'
                        : 'bg-blue-50 border-blue-300 hover:bg-blue-100 hover:border-blue-400'
                        }`}
                      onClick={() => {
                        setSelectedEmpaque(empaque);
                        // Actualizar quoteData automáticamente
                        setQuoteData(prev => ({
                          ...prev,
                          empaque: nombre,
                          empaqueId: empaque.id
                        }));
                      }}
                    >
                      <div className="flex flex-col items-center gap-1">
                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${isSelected
                          ? 'border-blue-600 bg-blue-600'
                          : 'border-blue-400'
                          }`}>
                          {isSelected && (
                            <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                              <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                            </svg>
                          )}
                        </div>
                        <div className="font-bold text-blue-900 text-sm">
                          {nombre}
                        </div>
                        {empaque.id && (
                          <div className="text-xs text-gray-500">ID: {empaque.id}</div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
              {selectedEmpaque && (
                <div className="text-sm bg-green-50 border border-green-200 rounded p-3 text-green-800 font-medium">
                  ✅ Empaque seleccionado: {selectedEmpaque.nome || selectedEmpaque.nombre}
                </div>
              )}
            </div>
          )
        };
      }

      // Si es create_cotizacion con resultado exitoso
      if (data.function === 'create_cotizacion' && data.result) {
        console.log('✅ Cotización creada:', data.result);

        // Actualizar quoteData con los resultados
        if (setQuoteData && data.result.cotizacion) {
          const cotizacion = data.result.cotizacion;
          setQuoteData(prev => Array.isArray(prev) ? [{
            ciudad_origen: cotizacion.ciudad_origen || prev.ciudadOrigen,
            ciudad_destino: cotizacion.ciudad_destino || prev.ciudadDestino,
            peso_mercancia: cotizacion.peso_mercancia || prev.pesoMercancia,
            cantidad: cotizacion.cantidad || prev.cantidadMercancia,
            tipo_embajale: cotizacion.tipo_embajale || prev.empaque,
            producto: cotizacion.producto || prev.producto,
            vehiculo_requerido: cotizacion.vehiculo_requerido,
            valor_flete: cotizacion.valor_flete,
            distancia_km: cotizacion.distancia_km,
            tiempo_estimado: cotizacion.tiempo_estimado
          }] : prev);
        }

        return {
          isToolCall: true,
          type: 'cotizacion_creada',
          content: (
            <div className="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-5 shadow-md">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                  <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div className="text-lg font-bold text-green-900">¡Cotización Creada Exitosamente!</div>
                  <div className="text-sm text-green-700">ID: {data.result.cotizacion_id}</div>
                </div>
              </div>

              {data.result.cotizacion && (
                <div className="space-y-2 bg-white rounded-lg p-4 border border-green-200">
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span className="font-semibold text-gray-600">📍 Origen:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.ciudad_origen}</div>
                    </div>
                    <div>
                      <span className="font-semibold text-gray-600">📍 Destino:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.ciudad_destino}</div>
                    </div>
                    <div>
                      <span className="font-semibold text-gray-600">⚖️ Peso:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.peso_mercancia} kg</div>
                    </div>
                    <div>
                      <span className="font-semibold text-gray-600">📦 Cantidad:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.cantidad}</div>
                    </div>
                    <div>
                      <span className="font-semibold text-gray-600">📦 Embalaje:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.tipo_embajale}</div>
                    </div>
                    <div>
                      <span className="font-semibold text-gray-600">🏷️ Producto:</span>
                      <div className="text-gray-900 font-medium">{data.result.cotizacion.producto}</div>
                    </div>
                    {data.result.cotizacion.vehiculo_requerido && (
                      <div className="col-span-2">
                        <span className="font-semibold text-gray-600">🚛 Vehículo:</span>
                        <div className="text-gray-900 font-medium">{data.result.cotizacion.vehiculo_requerido}</div>
                      </div>
                    )}
                    {data.result.cotizacion.valor_flete && (
                      <div className="col-span-2 bg-green-50 p-3 rounded border border-green-200">
                        <span className="font-semibold text-green-700">💰 Valor del Flete:</span>
                        <div className="text-2xl font-bold text-green-900">${Number(data.result.cotizacion.valor_flete).toLocaleString()}</div>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {data.result.message && (
                <div className="mt-3 text-sm text-green-700 font-medium">
                  ℹ️ {data.result.message}
                </div>
              )}
            </div>
          )
        };
      }

      // Si es un error de herramienta
      if (data.result?.error) {
        return {
          isToolCall: true,
          type: 'error',
          content: (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3">
              <div className="text-red-800">
                ⚠️ El servidor está experimentando demoras. Intenta nuevamente en unos momentos.
              </div>
            </div>
          )
        };
      }

      // Fallback: Si tiene función y resultado pero no coincide con ningún formato conocido
      if (data.function && data.result) {
        console.warn('⚠️ Tool call no reconocido:', data);
      }

    } catch (e) {
      // No es JSON válido, retornar null para mostrar texto normal
      return null;
    }

    return null;
  };

  // formatMessageTime se importa desde ./utils/chatUtils (línea 7)

  const sanitizedMessages = useMemo(() => {
    return messages.map((msg, index) => {
      const toolCallFormatted = formatToolCallMessage(msg.text, msg, index);
      if (toolCallFormatted) {
        return {
          ...msg,
          text: null,
          toolCallData: toolCallFormatted,
          created_at: formatMessageTime(msg.created_at)
        };
      }

      // 🔒 Filtrar notas internas que no deben mostrarse al usuario
      // Estas notas son instrucciones para la IA, no para el usuario
      let cleanText = stripMarkdown(msg.text);
      if (msg.role === 'user') {
        cleanText = cleanText.replace(/\s*NOTA\s+IMPORTANTE:.*$/us, '').trim();
      }

      return {
        ...msg,
        text: cleanText,
        created_at: formatMessageTime(msg.created_at)
      };
    });
  }, [messages]);

  return (
    <Modal onClose={onClose} size="extra-large">
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap');
        .product-sans {
          font-family: 'Product Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .chat-message {
          animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .scrollbar-thin::-webkit-scrollbar {
          width: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
          background: #f1f5f9;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 2px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
      `}</style>

      <div className="grid grid-cols-1 lg:grid-cols-2 h-[85vh] max-h-[85vh] product-sans">
        {/* Columna Izquierda - Panel de Información */}
        <div className="bg-gray-50 border-r border-gray-200 flex flex-col h-full overflow-hidden">
          {/* Contenido con scroll */}
          <div className="flex-1 overflow-y-auto p-8 scrollbar-thin">
          {/* Header del Panel */}
          <div className="mb-8">
            <div className="flex items-center justify-between mb-6 p-6 bg-white rounded-xl shadow-sm border border-gray-200">
              <div className="flex-1">
                <div className="flex items-center space-x-2 mb-2">
                  <div className="w-2 h-2 bg-green-300 rounded-full animate-pulse"></div>
                  <span className="text-sm font-500 text-green-500 product-sans">En Proceso</span>
                </div>
                <h3 className="text-lg font-600 text-gray-700 product-sans mb-1">
                  {clientData.clientName || clientData.search || 'Nueva Cotización'}
                </h3>



                <div className="space-y-1">
                  {clientData.clientId ? (
                    <>
                      <p className="text-sm font-500 text-gray-500 product-sans">
                        <span className="inline-flex items-center space-x-2">
                          <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">CLIENTE VINCULADO</span>
                          <span>NIT: {clientData.documentClient}</span>
                        </span>
                      </p>
                      {clientData.clientLocation && (
                        <p className="text-xs text-gray-500 product-sans flex items-center">
                          <span className="mr-1">📍</span>
                          {clientData.clientLocation}
                        </p>
                      )}
                      {clientData.clientEmail && (
                        <p className="text-xs text-gray-500 product-sans flex items-center">
                          <span className="mr-1">✉️</span>
                          {clientData.clientEmail}
                        </p>
                      )}
                      {clientData.clientContact && (
                        <p className="text-xs text-gray-500 product-sans flex items-center">
                          <span className="mr-1">👤</span>
                          {clientData.clientContact}
                        </p>
                      )}
                    </>
                  ) : (
                    <p className="text-sm font-500 text-gray-500 product-sans uppercase tracking-wide">
                      {clientData.clientName || 'Cliente'}
                    </p>
                  )}
                </div>
                <p className="text-xs text-gray-400 product-sans mt-1">
                  {new Date().toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </p>
              </div>
              <div className="bg-orange-400 text-white px-4 py-2 rounded-lg shadow-sm">
                <span className="text-sm font-500 product-sans">Asesor</span>
              </div>
            </div>
          </div>

          {/* Status de Validación */}
          <div className="mb-6">
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                {currentRunId ? (
                  <svg className="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                  </svg>
                ) : (
                  <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                )}
              </div>
              <h4 className="text-base font-600 text-gray-600 product-sans">
                {currentRunId ? 'Procesando información...' : 'Validando Información'}
              </h4>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-2 mb-4">
              <div
                className={`h-2 rounded-full transition-all duration-500 ${currentRunId ? 'bg-orange-400 animate-pulse' : 'bg-orange-400'
                  }`}
                style={{ width: `${quoteData.length > 0 ? '70' : currentRunId ? '50' : '30'}%` }}
              ></div>
            </div>
            {currentRunId && (
              <div className="text-xs text-orange-600 product-sans flex items-center">
                <svg className="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Extrayendo datos de cotización...
              </div>
            )}
          </div>

          {/* Panel de Progreso de Cotización - OCULTO */}
          {false && (
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div className="bg-gradient-to-r from-green-50 to-blue-50 px-6 py-4 border-b border-gray-200">
                <h4 className="text-base font-600 text-gray-700 product-sans">📋 Información de la Cotización</h4>
              </div>
              <div className="p-6 space-y-2">
                {/* 1. Origen */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.ciudadOrigen
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.ciudadOrigen ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.ciudadOrigen ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">1</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Origen</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.ciudadOrigen || 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 2. Destino */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.ciudadDestino
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.ciudadDestino ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.ciudadDestino ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">2</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Destino</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.ciudadDestino || 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 3. Peso */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.pesoMercancia
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.pesoMercancia ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.pesoMercancia ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">3</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Peso</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.pesoMercancia ? `${quoteData.pesoMercancia} kg` : 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 4. Cantidad */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.cantidadMercancia
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.cantidadMercancia ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.cantidadMercancia ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">4</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Cantidad</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.cantidadMercancia ? `${quoteData.cantidadMercancia} unidades` : 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 5. Embalaje */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${selectedEmpaque
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${selectedEmpaque ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {selectedEmpaque ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">5</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Embalaje</div>
                      <div className="text-xs text-gray-500">
                        {selectedEmpaque ? (selectedEmpaque.nome || selectedEmpaque.nombre) : 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 6. Producto */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${selectedProduct
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${selectedProduct ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {selectedProduct ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">6</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Producto</div>
                      <div className="text-xs text-gray-500">
                        {selectedProduct ? selectedProduct.nombre : 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 7. Vehículo */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.claseVehiculo || quoteData?.vehiculoRequerido
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.claseVehiculo || quoteData?.vehiculoRequerido ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.claseVehiculo || quoteData?.vehiculoRequerido ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">7</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Vehículo</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.claseVehiculo || quoteData?.vehiculoRequerido || 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* 8. Valor */}
                <div className={`flex items-center justify-between p-2 rounded-lg border transition-all ${quoteData?.valorMercancia
                  ? 'bg-green-50 border-green-300'
                  : 'bg-gray-50 border-gray-200'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-5 h-5 rounded-full flex items-center justify-center ${quoteData?.valorMercancia ? 'bg-green-500' : 'bg-gray-300'
                      }`}>
                      {quoteData?.valorMercancia ? (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      ) : (
                        <span className="text-xs text-white">8</span>
                      )}
                    </div>
                    <div>
                      <div className="text-xs font-600 text-gray-700">Valor</div>
                      <div className="text-xs text-gray-500">
                        {quoteData?.valorMercancia ? `$${parseInt(quoteData.valorMercancia).toLocaleString('es-CO')}` : 'Esperando...'}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Resumen */}
                <div className="pt-3 border-t border-gray-200">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-600 text-gray-600">
                      Campos completados
                    </span>
                    <span className="text-xs font-700 text-gray-900">
                      {(() => {
                        const fields = [
                          quoteData?.ciudadOrigen,
                          quoteData?.ciudadDestino,
                          quoteData?.pesoMercancia,
                          quoteData?.cantidadMercancia,
                          selectedEmpaque,
                          selectedProduct,
                          quoteData?.claseVehiculo || quoteData?.vehiculoRequerido,
                          quoteData?.valorMercancia
                        ];
                        const completed = fields.filter(f => f).length;
                        return `${completed}/8`;
                      })()}
                    </span>
                  </div>
                  <div className="mt-2 bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div
                      className="bg-gradient-to-r from-green-500 to-blue-500 h-full transition-all duration-500"
                      style={{
                        width: `${(() => {
                          const fields = [
                            quoteData?.ciudadOrigen,
                            quoteData?.ciudadDestino,
                            quoteData?.pesoMercancia,
                            quoteData?.cantidadMercancia,
                            selectedEmpaque,
                            selectedProduct,
                            quoteData?.claseVehiculo || quoteData?.vehiculoRequerido,
                            quoteData?.valorMercancia
                          ];
                          const completed = fields.filter(f => f).length;
                          return Math.round((completed / 8) * 100);
                        })()}%`
                      }}
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Panel de Detalles de Cotización - React Component */}
          <QuoteDetailsPanel
            routes={routesData}
            selectedProduct={selectedProduct}
            selectedEmpaque={selectedEmpaque}
            isLoading={!!processingMessage && (!quoteData || (Array.isArray(quoteData) ? quoteData.length === 0 : !quoteData.ciudadOrigen))}
            onCreateQuote={handleCreateQuote}
            canCreate={hasAllRequiredData && !isCreatingQuote}
            selectedRouteIndex={selectedRouteIndex}
            onSelectRoute={handleSelectRoute}
            isCreating={isCreatingQuote}
          />

          {/* ELIMINADO: Panel antiguo Livewire/Detalles editables que causaba errores */}
          {false && (
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div className="bg-gray-100 px-6 py-4 border-b border-gray-200">
                <h4 className="text-base font-600 text-gray-600 product-sans">Detalles de la Cotización</h4>
              </div>
              <div className="max-h-[500px] overflow-y-auto scrollbar-thin">
                {/* Mostrar productos disponibles para selección SI existen */}
                {(() => {
                  // Buscar si hay productos en los mensajes
                  const productosMessage = messages.find(msg =>
                    msg.content?.props?.type === 'productos' &&
                    msg.content?.props?.content?.props?.children?.[1]?.props?.children
                  );

                  const productos = productosMessage?.content?.props?.content?.props?.children?.[1]?.props?.children;

                  if (productos && Array.isArray(productos)) {
                    const totalProductos = productos.length;
                    return (
                      <div className="p-6 space-y-4">
                        <div className="bg-orange-50 border-2 border-orange-300 rounded-lg p-4">
                          <h5 className="font-600 text-orange-900 mb-2 product-sans flex items-center justify-between">
                            <span className="flex items-center gap-2">
                              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                              </svg>
                              Productos Disponibles
                            </span>
                            <span className="bg-orange-200 text-orange-900 px-2.5 py-1 rounded-full text-xs font-700">
                              {totalProductos} {totalProductos === 1 ? 'variación' : 'variaciones'}
                            </span>
                          </h5>
                          {!selectedProduct && (
                            <div className="bg-yellow-100 border border-yellow-300 rounded-lg p-2.5 mb-3">
                              <p className="text-xs text-yellow-900 font-600 product-sans flex items-center gap-1.5">
                                <svg className="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                  <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                                ⚠️ REQUERIDO: Debes seleccionar un producto antes de crear la cotización
                              </p>
                            </div>
                          )}
                          <p className="text-sm text-gray-700 product-sans mb-3">
                            {totalProductos > 1
                              ? `Hay ${totalProductos} variaciones disponibles. Selecciona la que mejor describa tu mercancía:`
                              : 'Confirma que este producto corresponde a tu mercancía:'
                            }
                          </p>
                          <div className="space-y-2 max-h-60 overflow-y-auto">
                            {productos.map((productoEl, idx) => {
                              if (!productoEl?.props) return null;
                              const producto = productoEl.props;
                              const productoData = {
                                nombre: producto.children?.[1]?.props?.children?.[0]?.props?.children,
                                codigo: producto.children?.[1]?.props?.children?.[1]?.props?.children?.[1]?.props?.children
                              };
                              const isSelected = selectedProduct?.codigo === productoData.codigo;

                              return (
                                <div
                                  key={idx}
                                  className={`border-2 rounded-lg p-3 transition-all cursor-pointer ${isSelected
                                    ? 'bg-orange-100 border-orange-500'
                                    : 'bg-white border-gray-300 hover:border-orange-400'
                                    }`}
                                  onClick={() => {
                                    setSelectedProduct(productoData);
                                    setQuoteData(prev => ({
                                      ...prev,
                                      producto: productoData.nombre,
                                      codigoProducto: productoData.codigo
                                    }));
                                  }}
                                >
                                  <div className="flex items-start gap-2">
                                    <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5 ${isSelected ? 'border-orange-600 bg-orange-600' : 'border-gray-400'
                                      }`}>
                                      {isSelected && (
                                        <svg className="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                        </svg>
                                      )}
                                    </div>
                                    <div className="flex-1">
                                      <div className="font-600 text-sm text-gray-900 product-sans">
                                        {productoData.nombre}
                                      </div>
                                      <div className="text-xs text-gray-600 mt-0.5 product-sans">
                                        Código: {productoData.codigo}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                          {selectedProduct && (
                            <div className="mt-3 bg-green-50 border-2 border-green-400 rounded-lg p-3 shadow-sm">
                              <div className="flex items-center gap-2">
                                <div className="w-6 h-6 bg-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                  <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                  </svg>
                                </div>
                                <div className="flex-1">
                                  <div className="text-xs text-green-700 font-600 product-sans">PRODUCTO SELECCIONADO</div>
                                  <div className="text-sm text-green-900 font-700 product-sans mt-0.5">{selectedProduct.nombre}</div>
                                  <div className="text-xs text-green-700 product-sans mt-0.5">Código: {selectedProduct.codigo}</div>
                                </div>
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                    );
                  }
                })()}

                {/* Formulario editable con datos extraídos */}
                {(quoteData?.ciudadOrigen || quoteData?.ciudadDestino || quoteData?.pesoMercancia) ? (
                  <div className="p-6 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      {/* Origen */}
                      <div>
                        <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Origen</label>
                        <input
                          type="text"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          value={quoteData.ciudadOrigen || ''}
                          onChange={(e) => setQuoteData(prev => ({ ...prev, ciudadOrigen: e.target.value }))}
                          placeholder="Ciudad origen"
                        />
                      </div>

                      {/* Destino */}
                      <div>
                        <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Destino</label>
                        <input
                          type="text"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          value={quoteData.ciudadDestino || ''}
                          onChange={(e) => setQuoteData(prev => ({ ...prev, ciudadDestino: e.target.value }))}
                          placeholder="Ciudad destino"
                        />
                      </div>

                      {/* Peso */}
                      <div>
                        <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Peso (kg)</label>
                        <input
                          type="number"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          value={quoteData.pesoMercancia || ''}
                          onChange={(e) => setQuoteData(prev => ({ ...prev, pesoMercancia: parseInt(e.target.value) || 0 }))}
                          placeholder="0"
                        />
                      </div>

                      {/* Cantidad */}
                      <div>
                        <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Cantidad</label>
                        <input
                          type="number"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          value={quoteData.cantidadMercancia || ''}
                          onChange={(e) => setQuoteData(prev => ({ ...prev, cantidadMercancia: parseInt(e.target.value) || 0 }))}
                          placeholder="0"
                        />
                      </div>

                      {/* Valor Declarado */}
                      <div className="col-span-2">
                        <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Valor Declarado (COP)</label>
                        <input
                          type="number"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          value={quoteData.valorMercancia || ''}
                          onChange={(e) => setQuoteData(prev => ({ ...prev, valorMercancia: parseInt(e.target.value) || 0 }))}
                          placeholder="0"
                        />
                      </div>

                      {/* Vehículo */}
                      {quoteData.claseVehiculo && (
                        <div className="col-span-2">
                          <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Vehículo</label>
                          <input
                            type="text"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            value={quoteData.claseVehiculo || ''}
                            onChange={(e) => setQuoteData(prev => ({ ...prev, claseVehiculo: e.target.value }))}
                            placeholder="Tipo de vehículo"
                          />
                        </div>
                      )}

                      {/* Embalaje seleccionado */}
                      {selectedEmpaque && (
                        <div className="col-span-2">
                          <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Embalaje</label>
                          <div className="px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm font-500 text-green-800 product-sans">
                            ✅ {selectedEmpaque.nome || selectedEmpaque.nombre}
                          </div>
                        </div>
                      )}

                      {/* Producto seleccionado */}
                      {selectedProduct && (
                        <div className="col-span-2">
                          <label className="block text-xs font-600 text-gray-600 mb-1 product-sans">Producto</label>
                          <div className="px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm font-500 text-green-800 product-sans">
                            ✅ {selectedProduct.nombre}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                ) : quoteData.length > 0 ? (
                  quoteData.map((route, index) => (
                    <div key={index} className={`p-6 ${index > 0 ? 'border-t border-gray-200' : ''}`}>
                      <div className="flex items-center space-x-2 mb-4">
                        <span className="bg-gray-200 text-gray-600 text-xs font-600 px-2 py-1 rounded-full product-sans">
                          Ruta {index + 1}
                        </span>
                      </div>
                      <div className="grid grid-cols-1 gap-3">
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Origen:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.ciudad_origen || '-'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Destino:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.ciudad_destino || '-'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Peso:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.peso_mercancia || '0'} kg
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Cantidad:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.cantidad || '0'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Tipo de embalaje:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.tipo_embajale || '-'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Tipo producto:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.tipo_producto || '-'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Vehículo requerido:</span>
                          <span className="text-sm font-600 text-gray-900 product-sans">
                            {route.vehiculo_requerido || '-'}
                          </span>
                        </div>
                        <div className="flex justify-between items-center py-1">
                          <span className="text-sm font-500 text-gray-600 product-sans">Valor declarado:</span>
                          <span className="text-sm font-600 text-green-600 product-sans">
                            {route.valor_declarado || '-'}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="p-6 text-center text-gray-500">
                    <p className="text-sm product-sans">
                      Comparte los detalles de tu envío para comenzar
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}
          </div> {/* Cierre del div de scroll de columna izquierda */}
        </div>

        {/* Columna Derecha - Chat y Acciones */}
        <div className="bg-white flex flex-col h-full overflow-hidden">
          {/* Header del Chat */}
          <div className="bg-orange-400 text-white p-6">
            <div className="flex items-center space-x-3">
              <div className="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
              </div>
              <div>
                <h3 className="text-lg font-600 product-sans">Asistente IA</h3>
                <p className="text-sm text-orange-100 product-sans">Configura tu cotización</p>
              </div>
            </div>
          </div>

          {/* Área de Conversación - Scroll interno controlado */}
          <div
            ref={conversationRef}
            onScroll={handleConversationScroll}
            className="flex-1 min-h-0 p-6 overflow-y-auto scrollbar-thin bg-gray-50"
          >
            {messages.length > 0 || processingMessage ? (
              <>
                {sanitizedMessages.map((message, index) => (
                  <div
                    key={index}
                    className={`mb-4 ${message.role === 'user' ? 'flex justify-end' : 'flex justify-start'} chat-message`}
                  >
                    <div className={`max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm ${message.role === 'user'
                      ? 'bg-orange-400 text-white rounded-br-sm'
                      : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm'
                      }`}>
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-xs opacity-75 product-sans">
                          {message.role === 'user' ? 'Tú' : 'Asistente'}
                        </span>
                        <div className="flex items-center space-x-1">
                          <span className="text-xs opacity-75 product-sans">
                            {message.created_at}
                          </span>
                          {message.role === 'user' && message.status === 'sent' && (
                            <div className="flex items-center">
                              <svg className="w-3 h-3 text-white opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd"></path>
                              </svg>
                            </div>
                          )}
                        </div>
                      </div>
                      {message.toolCallData ? (
                        <div className="text-sm product-sans leading-relaxed">
                          {message.toolCallData.content}
                        </div>
                      ) : (
                        <p className="text-sm product-sans leading-relaxed whitespace-pre-wrap">
                          {message.text}
                        </p>
                      )}
                    </div>
                  </div>
                ))}

                {/* Mensaje de "Pensando..." */}
                {processingMessage && (
                  <div className="mb-4 flex justify-start chat-message">
                    <div className="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm bg-white text-gray-800 border border-gray-200 rounded-bl-sm">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-xs opacity-75 product-sans">Asistente</span>
                        <span className="text-xs opacity-75 product-sans">
                          {processingMessage.created_at}
                        </span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <svg className="w-4 h-4 text-orange-400 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <p className="text-sm product-sans leading-relaxed text-orange-600">
                          {processingMessage.text}
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </>
            ) : (
              <div className="text-center py-12">
                <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                </div>
                <p className="text-gray-600 product-sans mb-4 font-500">
                  ¡Hola! Soy tu asistente inteligente para cotizaciones de transporte.
                </p>
                <p className="text-gray-500 product-sans text-sm mb-6">
                  Comparte los detalles de tu envío y yo me encargaré del resto.
                </p>

                {/* Ejemplos de mensajes propositivos */}
                <div className="bg-orange-50 border border-orange-200 rounded-lg p-4 max-w-md mx-auto">
                  <p className="text-orange-800 text-xs product-sans font-500 mb-2">💡 Ejemplos de mensajes:</p>
                  <div className="space-y-2 text-xs text-orange-700 product-sans">
                    <div className="bg-white rounded px-2 py-1 border border-orange-100">
                      "Envío de 500kg de Bogotá a Medellín"
                    </div>
                    <div className="bg-white rounded px-2 py-1 border border-orange-100">
                      "Necesito transportar 10 pallets de Cali a Barranquilla"
                    </div>
                    <div className="bg-white rounded px-2 py-1 border border-orange-100">
                      "Mercancía refrigerada 200kg, origen Cartagena"
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Área de Input - SIEMPRE VISIBLE para poder seguir modificando datos */}
          {true && (
            <div className="border-t border-gray-200 p-6 bg-white">
              {!clientData.clientId ? (
                <div className="text-center py-4">
                  <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p className="text-yellow-800 text-sm product-sans">
                      <span className="font-semibold">⚠️ Cliente requerido:</span> Selecciona un cliente para comenzar la conversación.
                    </p>
                  </div>
                </div>
              ) : currentRunId || processingMessage ? (
                <div className="text-center py-4">
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p className="text-blue-800 text-sm product-sans flex items-center justify-center mb-3">
                      <svg className="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                      </svg>
                      <span className="font-semibold">
                        {processingMessage ? 'Generando respuesta...' : 'Procesando tu mensaje anterior...'}
                      </span>
                    </p>

                    {/* Barra de progreso visual */}
                    {processingProgress > 0 && (
                      <div className="w-full bg-blue-200 rounded-full h-2 mb-3 overflow-hidden">
                        <div
                          className="bg-blue-500 h-full rounded-full transition-all duration-500 ease-out"
                          style={{ width: `${processingProgress}%` }}
                        >
                          <div className="h-full w-full bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-pulse"></div>
                        </div>
                      </div>
                    )}

                    <p className="text-blue-600 text-xs product-sans mb-3">
                      Espera un momento para enviar el siguiente mensaje.
                    </p>

                    {/* Botón para cancelar y reiniciar */}
                    <button
                      onClick={async () => {
                        console.log('🔄 Cancelando procesamiento y reiniciando chat...');

                        // Detener polling si existe
                        if (pollingIntervalRef.current) {
                          clearInterval(pollingIntervalRef.current);
                          pollingIntervalRef.current = null;
                        }

                        // Limpiar todos los estados
                        clearProcessingState();
                        setInputMessage('');

                        // Limpiar thread en el backend
                        if (clientData.threadId) {
                          try {
                            const response = await fetch('/api/chat/clear-thread', {
                              method: 'POST',
                              headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                              },
                              body: JSON.stringify({
                                thread_id: clientData.threadId,
                                client_id: clientData?.clientId || null
                              })
                            });

                            const data = await response.json();
                            if (data.success) {
                              console.log('✅ Chat reiniciado desde cero');

                              // Limpiar mensajes locales
                              if (onUpdateMessages) {
                                onUpdateMessages([]);
                              }

                              // Mostrar mensaje de confirmación
                              const confirmMessage = {
                                role: 'system',
                                text: '✅ Chat reiniciado correctamente. Puedes comenzar una nueva conversación.',
                                created_at: new Date().toLocaleTimeString(),
                                status: 'success',
                                isTemporary: false
                              };

                              if (onUpdateMessages) {
                                onUpdateMessages([confirmMessage]);
                              }
                            }
                          } catch (error) {
                            console.error('Error limpiando thread:', error);
                          }
                        }
                      }}
                      className="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg product-sans"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                      <span>Cancelar y Reiniciar Chat</span>
                    </button>

                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  {/* Indicador de procesamiento en el área de input */}
                  {(isSending || currentRunId || processingMessage) && (
                    <div className="bg-blue-50 border border-blue-300 rounded-lg p-3">
                      <div className="flex items-center space-x-3 mb-3">
                        <svg className="w-5 h-5 text-blue-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <div className="flex-1">
                          <p className="text-blue-700 text-sm font-semibold product-sans">
                            {processingMessage?.text || 'Procesando mensaje...'}
                          </p>
                          {inputMessage.trim() && (
                            <p className="text-blue-800 text-xs product-sans mt-1 bg-white rounded px-2 py-1 border border-blue-200">
                              📝 <span className="font-semibold">Esperando envío:</span> "{inputMessage.substring(0, 60)}{inputMessage.length > 60 ? '...' : ''}"
                            </p>
                          )}
                          <p className="text-blue-600 text-xs product-sans mt-1">
                            No puedes enviar mensajes mientras se procesa el anterior
                          </p>
                        </div>
                      </div>

                      {/* Botón cancelar en el indicador */}
                      <button
                        onClick={async () => {
                          console.log('🔄 Cancelando procesamiento...');

                          if (pollingIntervalRef.current) {
                            clearInterval(pollingIntervalRef.current);
                            pollingIntervalRef.current = null;
                          }

                          clearProcessingState();
                          setInputMessage('');

                          if (clientData.threadId) {
                            try {
                              await fetch('/api/chat/clear-thread', {
                                method: 'POST',
                                headers: {
                                  'Content-Type': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                                },
                                body: JSON.stringify({
                                  thread_id: clientData.threadId,
                                  client_id: clientData?.clientId || null
                                })
                              });

                              if (onUpdateMessages) {
                                onUpdateMessages([{
                                  role: 'system',
                                  text: '✅ Chat reiniciado. Puedes comenzar de nuevo.',
                                  created_at: new Date().toLocaleTimeString(),
                                  status: 'success'
                                }]);
                              }
                            } catch (error) {
                              console.error('Error:', error);
                            }
                          }
                        }}
                        className="w-full py-2 px-3 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 product-sans"
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Cancelar y Empezar de Nuevo</span>
                      </button>
                    </div>
                  )}

                  {/* 🆕 Indicador de ruta en edición - FUERA del textarea */}
                  {selectedRouteIndex !== null && (
                    <div className="mb-2 flex items-center justify-between bg-yellow-100 border border-yellow-300 rounded-lg px-3 py-2">
                      <div className="flex items-center space-x-2">
                        <span className="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full">
                          ✏️ Ruta {selectedRouteIndex + 1}
                        </span>
                        <span className="text-yellow-700 text-sm">
                          Modo edición activo - Los cambios se aplicarán solo a esta ruta
                        </span>
                      </div>
                      <button
                        onClick={() => handleSelectRoute(null)}
                        className="bg-yellow-200 hover:bg-yellow-300 text-yellow-800 text-xs font-medium px-3 py-1 rounded-full transition-colors"
                      >
                        ✕ Cancelar
                      </button>
                    </div>
                  )}

                  <div className="relative">
                    <textarea
                      value={inputMessage}
                      onChange={(e) => {
                        // No permitir cambios mientras está procesando
                        if (!isSending && !currentRunId && !processingMessage) {
                          setInputMessage(e.target.value);
                        }
                      }}
                      onKeyDown={handleKeyPress}
                      placeholder={
                        isSending || currentRunId || processingMessage
                          ? "⏳ Mensaje en proceso de envío..."
                          : selectedRouteIndex !== null
                            ? `Ej: 'Cambia el destino a Cali' o 'Peso 5000 kg' o 'Producto neumáticos'...`
                            : "Ej: 'Envío de 200kg de Bogotá a Cali' o 'Transportar pallets refrigerados'..."
                      }
                      className={`w-full px-4 py-3 pr-20 border rounded-xl resize-none focus:outline-none transition-all duration-200 product-sans ${isSending || currentRunId || processingMessage
                        ? 'bg-blue-50 border-blue-300 text-gray-700 cursor-not-allowed font-medium'
                        : selectedRouteIndex !== null
                          ? 'bg-yellow-50 border-yellow-400 text-gray-800 placeholder-yellow-600 focus:ring-2 focus:ring-yellow-400 focus:border-transparent'
                          : 'bg-gray-50 border-gray-200 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-orange-400 focus:border-transparent'
                        }`}
                      rows="2"
                      disabled={isSending || currentRunId || processingMessage}
                      readOnly={isSending || currentRunId || processingMessage}
                    />

                    {/* Botones de Acción */}
                    <div className="absolute bottom-3 right-3 flex items-center space-x-2">
                      <SpeechRecognition
                        onResult={handleVoiceResult}
                        isRecording={isRecording}
                        setIsRecording={setIsRecording}
                      />

                      <button
                        type="button"
                        onClick={handleSendMessage}
                        disabled={!inputMessage.trim() || isSending || loading || !clientData.clientId || currentRunId || processingMessage}
                        className="w-8 h-8 flex items-center justify-center rounded-full bg-orange-400 hover:bg-orange-500 transition-all duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        title={
                          !clientData.clientId ? "Cliente requerido" :
                            currentRunId || processingMessage ? "Procesando respuesta anterior..." :
                              "Enviar mensaje"
                        }
                      >
                        {(isSending || loading || currentRunId || processingMessage) ? (
                          <svg className="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                          </svg>
                        ) : (
                          <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                          </svg>
                        )}
                      </button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}


        </div>
      </div>
    </Modal>
  );
};

ChatModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  onNext: PropTypes.func.isRequired,
  messages: PropTypes.array.isRequired,
  inputMessage: PropTypes.string.isRequired,
  setInputMessage: PropTypes.func.isRequired,
  onSendMessage: PropTypes.func.isRequired,
  quoteData: PropTypes.oneOfType([PropTypes.array, PropTypes.object]).isRequired, // Puede ser array (multi-ruta) u objeto (ruta única)
  setQuoteData: PropTypes.func.isRequired,
  clientData: PropTypes.object.isRequired,
  loading: PropTypes.bool,
  typeBusiness: PropTypes.string,
  onUpdateMessages: PropTypes.func
};

ChatModal.defaultProps = {
  loading: false,
  typeBusiness: 'dta',
  onUpdateMessages: null
};

export default ChatModal;