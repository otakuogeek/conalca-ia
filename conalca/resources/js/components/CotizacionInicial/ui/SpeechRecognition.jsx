import React, { useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';

const SpeechRecognitionButton = ({ onResult, isRecording, setIsRecording }) => {
  const [isSupported, setIsSupported] = useState(false);
  const recognitionRef = useRef(null);
  const latestCallbackRef = useRef(onResult);
  const transcriptRef = useRef('');

  // keep the latest onResult without re-running the effect
  useEffect(() => {
    latestCallbackRef.current = onResult;
  }, [onResult]);

  useEffect(() => {
    const SpeechRecognitionAPI =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognitionAPI) {
      setIsSupported(false);
      return;
    }

    setIsSupported(true);
    const recognition = new SpeechRecognitionAPI();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'es-ES';

    recognition.onstart = () => {
      transcriptRef.current = '';
      setIsRecording(true);
    };

    recognition.onresult = (event) => {
      let chunk = '';

      for (let i = event.resultIndex; i < event.results.length; i += 1) {
        if (event.results[i].isFinal) {
          chunk += event.results[i][0].transcript;
        }
      }

      if (chunk.trim()) {
        transcriptRef.current += ` ${chunk}`.trim();
        latestCallbackRef.current?.(transcriptRef.current.trim());
      }
    };

    recognition.onerror = (event) => {
      console.error('Speech recognition error:', event.error);
      setIsRecording(false);
    };

    recognition.onend = () => {
      setIsRecording(false);
      if (transcriptRef.current.trim()) {
        latestCallbackRef.current?.(transcriptRef.current.trim());
      }
      transcriptRef.current = '';
    };

    recognitionRef.current = recognition;

    return () => {
      recognition.stop();
      recognitionRef.current = null;
    };
  }, [setIsRecording]); // <-- no onResult dependency

  const toggleRecording = () => {
    if (!recognitionRef.current) return;

    try {
      if (isRecording) {
        recognitionRef.current.stop();
      } else {
        recognitionRef.current.start();
      }
    } catch (err) {
      console.error('Speech recognition start/stop error:', err);
    }
  };

  if (!isSupported) return null;

  return (
    <>
      {isRecording && (
        <div className="absolute top-2 left-2 flex items-center space-x-2 bg-red-500 text-white px-3 py-1 rounded-full text-xs z-10">
          <div className="w-2 h-2 bg-white rounded-full animate-pulse" />
          <span>...</span>
        </div>
      )}

      <button
        type="button"
        onClick={toggleRecording}
        className={`w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-200 ${
          isRecording ? 'bg-red-400 hover:bg-red-500' : 'bg-green-400 hover:bg-green-500'
        }`}
        title={isRecording ? 'Stop recording' : 'Start recording'}
      >
        {isRecording ? (
          <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path
              fillRule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z"
              clipRule="evenodd"
            />
          </svg>
        ) : (
          <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z" />
            <path d="M5.5 9.643a.75.75 0 00-1.5 0V10c0 3.06 2.29 5.585 5.25 5.954V17.5h-1.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5H10.5v-1.546A6.001 6.001 0 0016 10v-.357a.75.75 0 00-1.5 0V10a4.5 4.5 0 01-9 0v-.357z" />
          </svg>
        )}
      </button>
    </>
  );
};

SpeechRecognitionButton.propTypes = {
  onResult: PropTypes.func.isRequired,
  isRecording: PropTypes.bool.isRequired,
  setIsRecording: PropTypes.func.isRequired,
};

export default SpeechRecognitionButton;