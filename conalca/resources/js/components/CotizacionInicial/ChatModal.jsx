// resources/js/components/CotizacionInicial/ChatModal.jsx
import React, { useEffect, useRef, useState } from 'react';
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
  loading 
}) => {
  const conversationRef = useRef(null);
  const [isRecording, setIsRecording] = useState(false);

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
    // Auto-scroll al final de la conversación
    if (conversationRef.current) {
      conversationRef.current.scrollTop = conversationRef.current.scrollHeight;
    }
  }, [messages]);

  const handleSendMessage = () => {
    if (inputMessage.trim()) {
      onSendMessage(inputMessage.trim());
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  const handleVoiceResult = (transcript) => {
    setInputMessage(transcript);
  };

  const canProceed = quoteData && quoteData.length > 0 && 
                   quoteData.some(route => route.ciudad_origen && route.ciudad_destino);

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
                
                {/* DEBUG: Mostrar información de debug temporalmente */}
                <div className="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                  <p><strong>DEBUG clientId:</strong> {clientData.clientId || 'null'}</p>
                  <p><strong>DEBUG clientName:</strong> {clientData.clientName || 'vacío'}</p>
                  <p><strong>DEBUG documentClient:</strong> {clientData.documentClient || 'vacío'}</p>
                  <p><strong>DEBUG search:</strong> {clientData.search || 'vacío'}</p>
                </div>
                
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
                <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <h4 className="text-base font-600 text-gray-600 product-sans">Validando Información</h4>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-2 mb-4">
              <div 
                className="bg-orange-400 h-2 rounded-full transition-all duration-500" 
                style={{ width: `${quoteData.length > 0 ? '70' : '30'}%` }}
              ></div>
            </div>
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
            className="flex-1 p-6 overflow-y-auto scrollbar-thin bg-gray-50" 
            style={{ maxHeight: '500px' }}
          >
            {messages.length > 0 ? (
              messages.map((message, index) => (
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
                      <span className="text-xs opacity-75 product-sans">
                        {message.created_at}
                      </span>
                    </div>
                    <p className="text-sm product-sans leading-relaxed">
                      {message.text}
                    </p>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                </div>
                <p className="text-gray-500 product-sans">
                  ¡Hola! Estoy aquí para ayudarte a crear tu cotización. Comparte los detalles de tu envío.
                </p>
              </div>
            )}
          </div>

          {/* Área de Input */}
          {!canProceed && (
            <div className="border-t border-gray-200 p-6 bg-white">
              <div className="space-y-4">
                <div className="relative">
                  <textarea 
                    value={inputMessage}
                    onChange={(e) => setInputMessage(e.target.value)}
                    onKeyDown={handleKeyPress}
                    placeholder="Describe tu envío: origen, destino, peso, tipo de carga..."
                    className="w-full px-4 py-3 pr-20 text-gray-800 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200 product-sans"
                    rows="3"
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
                      disabled={!inputMessage.trim() || loading}
                      className="w-8 h-8 flex items-center justify-center rounded-full bg-orange-400 hover:bg-orange-500 transition-all duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                      title="Enviar mensaje"
                    >
                      {loading ? (
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
            </div>
          )}

          {/* Botón de Crear Cotización */}
          {canProceed && (
            <div className="border-t border-gray-200 p-6 bg-gray-50">
              <button 
                onClick={onNext}
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
  loading: PropTypes.bool
};

export default ChatModal;