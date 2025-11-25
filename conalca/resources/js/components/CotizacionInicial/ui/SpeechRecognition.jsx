// resources/js/components/CotizacionInicial/ui/SpeechRecognition.jsx
import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

const SpeechRecognition = ({ onResult, isRecording, setIsRecording }) => {
  const [isSupported, setIsSupported] = useState(false);
  const recognitionRef = useRef(null);
  const [transcript, setTranscript] = useState('');

  useEffect(() => {
    // Verificar soporte para Web Speech API
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    setIsSupported(!!SpeechRecognition);

    if (SpeechRecognition) {
      const recognition = new SpeechRecognition();
      recognition.continuous = true;
      recognition.interimResults = true;
      recognition.lang = 'es-ES';

      recognition.onstart = () => {
        setIsRecording(true);
        setTranscript('');
      };

      recognition.onresult = (event) => {
        let finalTranscript = '';
        let interimTranscript = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
          const transcript = event.results[i][0].transcript;
          if (event.results[i].isFinal) {
            finalTranscript += transcript;
          } else {
            interimTranscript += transcript;
          }
        }

        const fullTranscript = finalTranscript || interimTranscript;
        setTranscript(fullTranscript);
        
        if (finalTranscript && onResult) {
          onResult(finalTranscript.trim());
        }
      };

      recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        setIsRecording(false);
        
        // Mostrar mensaje de error específico
        switch (event.error) {
          case 'not-allowed':
            console.error('Permiso denegado para el micrófono');
            break;
          case 'no-speech':
            console.log('No se detectó voz');
            break;
          case 'audio-capture':
            console.error('No se pudo acceder al micrófono');
            break;
          case 'network':
            console.error('Error de red durante el reconocimiento');
            break;
          default:
            console.error('Error en reconocimiento de voz:', event.error);
        }
      };

      recognition.onend = () => {
        setIsRecording(false);
        if (transcript.trim() && onResult) {
          onResult(transcript.trim());
        }
      };

      recognitionRef.current = recognition;
    }

    return () => {
      if (recognitionRef.current) {
        recognitionRef.current.onstart = null;
        recognitionRef.current.onresult = null;
        recognitionRef.current.onerror = null;
        recognitionRef.current.onend = null;
      }
    };
  }, [onResult, setIsRecording, transcript]);

  const startRecording = () => {
    if (recognitionRef.current && !isRecording) {
      try {
        recognitionRef.current.start();
      } catch (error) {
        console.error('Error starting speech recognition:', error);
      }
    }
  };

  const stopRecording = () => {
    if (recognitionRef.current && isRecording) {
      try {
        recognitionRef.current.stop();
      } catch (error) {
        console.error('Error stopping speech recognition:', error);
      }
    }
  };

  const toggleRecording = () => {
    if (isRecording) {
      stopRecording();
    } else {
      startRecording();
    }
  };

  if (!isSupported) {
    return null; // No mostrar nada si no hay soporte
  }

  return (
    <>
      {/* Indicador de grabación */}
      {isRecording && (
        <div className="absolute top-2 left-2 flex items-center space-x-2 bg-red-500 text-white px-3 py-1 rounded-full text-xs z-10">
          <div className="w-2 h-2 bg-white rounded-full animate-pulse"></div>
          <span>Grabando...</span>
        </div>
      )}
      
      {/* Botón de micrófono */}
      <button
        type="button"
        onClick={toggleRecording}
        className={`w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-200 ${
          isRecording 
            ? 'bg-red-400 hover:bg-red-500' 
            : 'bg-green-400 hover:bg-green-500'
        }`}
        title={isRecording ? "Detener grabación" : "Grabar audio"}
      >
        {isRecording ? (
          <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clipRule="evenodd"></path>
          </svg>
        ) : (
          <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z"></path>
            <path d="M5.5 9.643a.75.75 0 00-1.5 0V10c0 3.06 2.29 5.585 5.25 5.954V17.5h-1.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5H10.5v-1.546A6.001 6.001 0 0016 10v-.357a.75.75 0 00-1.5 0V10a4.5 4.5 0 01-9 0v-.357z"></path>
          </svg>
        )}
      </button>
    </>
  );
};

SpeechRecognition.propTypes = {
  onResult: PropTypes.func.isRequired,
  isRecording: PropTypes.bool.isRequired,
  setIsRecording: PropTypes.func.isRequired
};

export default SpeechRecognition;