// resources/js/components/CotizacionInicial/ChatModal.jsx
import React, { useEffect, useRef, useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import SpeechRecognition from './ui/SpeechRecognition';

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
  typeBusiness = 'dta', // Añadir typeBusiness con valor por defecto
  onUpdateMessages // Callback para actualizar mensajes desde API
}) => {
  const conversationRef = useRef(null);
  const [isRecording, setIsRecording] = useState(false);
  const [threadId, setThreadId] = useState(null);
  const [isSending, setIsSending] = useState(false);
  const [currentRunId, setCurrentRunId] = useState(null);
  const [pollingInterval, setPollingInterval] = useState(null);
  const [processingMessage, setProcessingMessage] = useState(null); // Mensaje de "Pensando..."
  const [processingProgress, setProcessingProgress] = useState(0); // Progreso 0-100
  const sendingRef = useRef(false); // Ref adicional para bloqueo
  const [shouldAutoScroll, setShouldAutoScroll] = useState(true);

  const stripMarkdown = (text = '') => {
    return text
      // remove headings like ### Title
      .replace(/^#{1,6}\s*/gm, '')
      // bold / italic (**text**, __text__, *text*, _text_)
      .replace(/(\*\*|__)(.*?)\1/g, '$2')
      .replace(/(\*|_)(.*?)\1/g, '$2')
      // inline code `code`
      .replace(/`([^`]+)`/g, '$1')
      // links [label](url)
      .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
      // collapse multiple spaces (but keep newlines intact)
      .replace(/[ \t]{2,}/g, ' ')
      // optional: limit extra blank lines if needed
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  };

  // Debug: Log clientData para verificar qué información llega
  useEffect(() => {
    console.log('🔍 ChatModal - clientData completo:', clientData);
    console.log('🆔 clientData.clientId:', clientData.clientId);
    console.log('📋 clientData.clientName:', clientData.clientName);
    console.log('📄 clientData.documentClient:', clientData.documentClient);
    console.log('🏢 clientData.clientLocation:', clientData.clientLocation);
    console.log('📧 clientData.clientEmail:', clientData.clientEmail);
    console.log('👤 clientData.clientContact:', clientData.clientContact);
  }, [clientData]);


  useEffect(() => {
    const resetConversation = async () => {
      if (!clientData.clientId) return;

      // Clear the stored messages in the parent/local state
      if (onUpdateMessages) {
        onUpdateMessages([]);
      }
      if (setQuoteData) {
        setQuoteData([]);
      }

      // Tell the backend to discard the previous thread so a brand-new one is created
      try {
        if (clientData.threadId) {
          await fetch('/api/chat/clear-thread', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
              thread_id: clientData.threadId,
              client_id: clientData.clientId
            })
          });
        }
      } catch (error) {
        console.error('Error clearing previous thread:', error);
      } finally {
        setThreadId(null); // force ChatModal to request a new thread
      }
    };

    resetConversation();
  }, [clientData.clientId]);

  // Auto-enviar mensaje inicial con datos del formulario si existen
  useEffect(() => {
    // Solo ejecutar una vez cuando el modal se abre
    if (!quoteData || messages.length > 0) return;

    const hasData = quoteData.ciudadOrigen || quoteData.ciudadDestino || 
                    quoteData.pesoMercancia || quoteData.valorMercancia;
    
    if (hasData) {
      console.log('📝 Detectados datos en formulario, construyendo mensaje automático...');
      
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
      
      console.log('✅ Mensaje construido:', mensaje);
      
      // Establecer el mensaje en el input y simular envío
      setInputMessage(mensaje);
      
      // Enviar después de un breve delay para que el componente se monte completamente
      setTimeout(() => {
        console.log('🚀 Enviando mensaje automático...');
        onSendMessage(mensaje);
      }, 500);
    }
  }, []); // Solo ejecutar al montar

  useEffect(() => {
    if (shouldAutoScroll && conversationRef.current) {
      conversationRef.current.scrollTop = conversationRef.current.scrollHeight;
    }
  }, [messages, processingMessage, shouldAutoScroll]);

  // Cleanup polling on unmount
  useEffect(() => {
    return () => {
      if (pollingInterval) {
        clearInterval(pollingInterval);
      }
    };
  }, [pollingInterval]);

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
    setProcessingProgress(50); // Progreso al iniciar polling
    
    let pollAttempts = 0;
    const maxPollAttempts = 300; // Máximo 60 segundos de polling (30 * 2 segundos)
    
    const pollRun = async () => {
      try {
        pollAttempts++;
        
        // Si superamos el máximo de intentos, detener polling y mostrar opción de cancelar
        if (pollAttempts > maxPollAttempts) {
          console.warn('⏰ Timeout de polling alcanzado, deteniendo...');
          if (pollingInterval) {
            clearInterval(pollingInterval);
            setPollingInterval(null);
          }
          
          // Mostrar mensaje de timeout con opción de reiniciar
          setProcessingMessage({
            role: 'system',
            text: '⏰ El procesamiento está tardando más de lo esperado. Puedes cancelar y reintentar.',
            created_at: new Date().toLocaleTimeString(),
            status: 'timeout',
            isTemporary: true
          });
          
          // NO limpiar currentRunId para mantener el botón de cancelar visible
          
          // Intentar obtener mensajes finales
          try {
            const messagesResponse = await fetch(`/api/chat/messages/${threadId}`, {
              headers: {
                'Accept': 'application/json'
              }
            });
            
            const messagesData = await messagesResponse.json();
            if (messagesData.success && messagesData.data.messages && onUpdateMessages) {
              onUpdateMessages(messagesData.data.messages);
            }
          } catch (error) {
            console.error('Error obteniendo mensajes después de timeout:', error);
          }
          return;
        }
        
        const response = await fetch(`/api/chat/run/${threadId}/${runId}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        
        const data = await response.json();
        
        // Actualizar progreso basado en intentos
        const progress = Math.min(50 + (pollAttempts / maxPollAttempts) * 40, 90);
        setProcessingProgress(progress);
        
        console.log(`🔍 Polling intento ${pollAttempts}/${maxPollAttempts}, status:`, data.data?.status);
        
        if (data.success) {
          if (data.data.status === 'completed_with_data' && data.data.quote_data) {
            console.log('✅ Datos de cotización extraídos:', data.data.quote_data);
            
            // Actualizar quoteData con los datos extraídos
            if (setQuoteData && Array.isArray(data.data.quote_data)) {
              setQuoteData(data.data.quote_data);
              
              // Guardar las rutas en la base de datos automáticamente
            }
            
            // Detener polling
            if (pollingInterval) {
              clearInterval(pollingInterval);
              setPollingInterval(null);
            }
            setCurrentRunId(null);
            
            // Quitar mensaje de "Pensando..."
            setProcessingMessage(null);
            
            // Obtener mensajes actualizados
            setTimeout(async () => {
              try {
                const messagesResponse = await fetch(`/api/chat/messages/${threadId}`, {
                  headers: {
                    'Accept': 'application/json'
                  }
                });
                
                const messagesData = await messagesResponse.json();
                if (messagesData.success && messagesData.data.messages && onUpdateMessages) {
                  onUpdateMessages(messagesData.data.messages);
                }
              } catch (error) {
                console.error('Error obteniendo mensajes después de polling:', error);
              }
            }, 1000);
          } else if (data.data.status === 'completed_with_indication') {
            // El asistente indicó que las cotizaciones están completadas
            console.log('✅ Asistente indica cotizaciones completadas:', data.data.message);
            
            // Detener polling
            if (pollingInterval) {
              clearInterval(pollingInterval);
              setPollingInterval(null);
            }
            setCurrentRunId(null);
            
            // Quitar mensaje de "Pensando..."
            setProcessingMessage(null);
            
            // Obtener mensajes actualizados
            setTimeout(async () => {
              try {
                const messagesResponse = await fetch(`/api/chat/messages/${threadId}`, {
                  headers: {
                    'Accept': 'application/json'
                  }
                });
                
                const messagesData = await messagesResponse.json();
                if (messagesData.success && messagesData.data.messages && onUpdateMessages) {
                  // Verificar si hay mensajes del asistente
                  const assistantMessages = messagesData.data.messages.filter(msg => msg.role === 'assistant');
                  const userMessages = messagesData.data.messages.filter(msg => msg.role === 'user');
                  
                  console.log('📊 Análisis de mensajes (completed_with_indication):', {
                    total: messagesData.data.messages.length,
                    assistant: assistantMessages.length,
                    user: userMessages.length
                  });
                  
                  // Si hay mensajes del usuario pero ninguno del asistente después de completion,
                  // puede indicar que el run no se ejecutó correctamente
                  if (userMessages.length > 0 && assistantMessages.length === 0) {
                    console.warn('⚠️ Se detectó mensaje del usuario sin respuesta del asistente');
                    // Mostrar mensaje de error al usuario
                    setProcessingMessage({
                      role: 'assistant',
                      text: 'Parece que hubo un problema procesando tu solicitud. Por favor, inténtalo de nuevo.',
                      created_at: new Date().toLocaleTimeString(),
                      status: 'error',
                      isTemporary: true
                    });
                  }
                  
                  onUpdateMessages(messagesData.data.messages);
                }
              } catch (error) {
                console.error('Error obteniendo mensajes después de polling:', error);
              }
            }, 1000);
          } else if (data.data.status === 'in_progress') {
            // Si status es 'in_progress', continúa el polling
            console.log('🔄 Run aún en progreso, continuando polling...');
          } else if (data.data.status === 'completed') {
            // Run completado sin datos específicos, detener polling y actualizar mensajes
            console.log('🏁 Run completado sin datos, actualizando mensajes...');
            
            // Detener polling
            if (pollingInterval) {
              clearInterval(pollingInterval);
              setPollingInterval(null);
            }
            setCurrentRunId(null);
            
            // Quitar mensaje de "Pensando..."
            setProcessingMessage(null);
            
            // Obtener mensajes actualizados inmediatamente
            try {
              const messagesResponse = await fetch(`/api/chat/messages/${threadId}`, {
                headers: {
                  'Accept': 'application/json'
                }
              });
              
              const messagesData = await messagesResponse.json();
              if (messagesData.success && messagesData.data.messages && onUpdateMessages) {
                onUpdateMessages(messagesData.data.messages);
              }
            } catch (error) {
              console.error('Error obteniendo mensajes después de completar:', error);
            }
          } else {
            // Si es cualquier otro estado (finished, completed, etc.), detener polling
            console.log('🏁 Run completado sin datos específicos, deteniendo polling');
            
            // Detener polling
            if (pollingInterval) {
              clearInterval(pollingInterval);
              setPollingInterval(null);
            }
            setCurrentRunId(null);
            
            // Quitar mensaje de "Pensando..."
            setProcessingMessage(null);
            
            // Obtener mensajes actualizados
            setTimeout(async () => {
              try {
                const messagesResponse = await fetch(`/api/chat/messages/${threadId}`, {
                  headers: {
                    'Accept': 'application/json'
                  }
                });
                
                const messagesData = await messagesResponse.json();
                if (messagesData.success && messagesData.data.messages && onUpdateMessages) {
                  onUpdateMessages(messagesData.data.messages);
                }
              } catch (error) {
                console.error('Error obteniendo mensajes después de polling:', error);
              }
            }, 1000);
          }
        }
      } catch (error) {
        console.error('Error en polling run:', error);
      }
    };
    
    // Polling cada 2 segundos
    const interval = setInterval(pollRun, 2000);
    setPollingInterval(interval);
    
    // Primera verificación inmediata
    pollRun();
  };

  const handleSendMessage = async (retryAttempt = false) => {
    if (!inputMessage.trim()) return;
    if (!clientData.clientId) return;
    if (isSending || sendingRef.current) return;
    if (currentRunId || processingMessage) return;

    const messageText = inputMessage.trim();
    setIsSending(true);
    sendingRef.current = true;
    setProcessingProgress(10);

    const activeThreadId = threadId; // <-- only use local thread id

    try {
      if (activeThreadId) {
        try {
          const existingMessagesResponse = await fetch(`/api/chat/messages/${activeThreadId}`, {
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
          type_business: clientData.typeBusiness || typeBusiness
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
          setThreadId(data.data.thread_id); // <-- store new thread locally
        }

        if (data.data.run_id) {
          startPollingRun(data.data.thread_id || activeThreadId, data.data.run_id);
        } else {
          setProcessingMessage(null);
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
    if (!clientData.groupId) {
      alert('You need a quote group before saving routes.');
      return;
    }
    if (!quoteData || !quoteData.length) {
      alert('No routes to save.');
      return;
    }

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
          routes: quoteData
        })
      });

      const saveResult = await saveResponse.json();

      if (saveResult.success) {
        // Merge returned IDs into quoteData so later steps update instead of create
        if (saveResult.data?.routes) {
          setQuoteData(prev =>
            prev.map((r, i) => ({
              ...r,
              id: saveResult.data.routes[i]?.id || r.id || null,
            }))
          );
        }

        // proceed to next step
        onNext && onNext(saveResult.data);
      } else {
        console.error('Error saving routes:', saveResult.error);
        alert('There was a problem saving routes. Please try again.');
      }
    } catch (err) {
      console.error('Network error saving routes:', err);
      alert('Network error while saving routes. Please retry.');
    }
  };

  const canProceed = quoteData && quoteData.length > 0 && 
                   quoteData.some(route => route.ciudad_origen && route.ciudad_destino);

  const sanitizedMessages = useMemo(() => {
    return messages.map(msg => ({
      ...msg,
      text: stripMarkdown(msg.text)
    }));
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

      <div className="grid grid-cols-1 lg:grid-cols-2 min-h-[80vh] product-sans">
        {/* Columna Izquierda - Panel de Información */}
        <div className="bg-gray-50 p-8 border-r border-gray-200">
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
                className={`h-2 rounded-full transition-all duration-500 ${
                  currentRunId ? 'bg-orange-400 animate-pulse' : 'bg-orange-400'
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

          {/* Información de Rutas */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="bg-gray-100 px-6 py-4 border-b border-gray-200">
              <h4 className="text-base font-600 text-gray-600 product-sans">Detalles de la Cotización</h4>
            </div>
            <div className="max-h-96 overflow-y-auto scrollbar-thin">
              {quoteData.length > 0 ? (
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
        </div>

        {/* Columna Derecha - Chat y Acciones */}
        <div className="bg-white flex flex-col">
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

          {/* Área de Conversación */}
          <div 
            ref={conversationRef}
            onScroll={handleConversationScroll}
            className="flex-1 p-6 overflow-y-auto scrollbar-thin bg-gray-50" 
            style={{ maxHeight: '500px' }}
          >
            {messages.length > 0 || processingMessage ? (
              <>
                {sanitizedMessages.map((message, index) => (
                  <div 
                    key={index} 
                    className={`mb-4 ${message.role === 'user' ? 'flex justify-end' : 'flex justify-start'} chat-message`}
                  >
                    <div className={`max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm ${
                      message.role === 'user' 
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
                      <p className="text-sm product-sans leading-relaxed whitespace-pre-wrap">
                        {message.text}
                      </p>
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

          {/* Área de Input */}
          {!canProceed && (
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
                        if (pollingInterval) {
                          clearInterval(pollingInterval);
                          setPollingInterval(null);
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
                          
                          if (pollingInterval) {
                            clearInterval(pollingInterval);
                            setPollingInterval(null);
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
                          : "Ej: 'Envío de 200kg de Bogotá a Cali' o 'Transportar pallets refrigerados'..."
                      }
                      className={`w-full px-4 py-3 pr-20 border rounded-xl resize-none focus:outline-none transition-all duration-200 product-sans ${
                        isSending || currentRunId || processingMessage 
                          ? 'bg-blue-50 border-blue-300 text-gray-700 cursor-not-allowed font-medium' 
                          : 'bg-gray-50 border-gray-200 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-orange-400 focus:border-transparent'
                      }`}
                      rows="3"
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

          {/* Botón de Crear Cotización */}
          {canProceed && (
            <div className="border-t border-gray-200 p-6 bg-gray-50">
              <button 
                onClick={handleCreateQuote}
                className="w-full py-4 px-6 bg-orange-400 hover:bg-orange-500 text-white font-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:scale-105 product-sans group"
              >
                <div className="flex items-center justify-center space-x-2">
                  <svg className="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span className="text-base">Crear Cotización</span>
                  <div className="w-2 h-2 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
              </button>
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
  quoteData: PropTypes.array.isRequired,
  setQuoteData: PropTypes.func.isRequired,
  clientData: PropTypes.object.isRequired,
  loading: PropTypes.bool,
  typeBusiness: PropTypes.string,
  onUpdateMessages: PropTypes.func
};

export default ChatModal;