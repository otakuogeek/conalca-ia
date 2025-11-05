// resources/js/components/CotizacionInicial/ui/Modal.jsx
import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { createPortal } from 'react-dom';

const Modal = ({ children, onClose, size = 'default', showCloseButton = true }) => {
  useEffect(() => {
    // Prevenir scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';
    
    // Limpiar al desmontar
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, []);

  const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  const handleEscape = (e) => {
    if (e.key === 'Escape') {
      onClose();
    }
  };

  useEffect(() => {
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, []);

  const getSizeClasses = () => {
    switch (size) {
      case 'small':
        return 'max-w-sm sm:max-w-md';
      case 'medium':
        return 'max-w-md sm:max-w-lg';
      case 'large':
        return 'max-w-full sm:max-w-3xl lg:max-w-4xl xl:max-w-5xl';
      case 'extra-large':
        return 'max-w-full sm:max-w-6xl lg:max-w-7xl';
      case 'full':
        return 'max-w-full mx-2 sm:mx-4';
      case 'full-screen':
        return 'max-w-full mx-1 sm:mx-2 w-[98vw] h-[95vh]';
      default:
        return 'max-w-md sm:max-w-lg';
    }
  };

  const modalContent = (
    <div className="relative z-20" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div className="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm"></div>
      <div className="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div className="flex min-h-full w-full items-center justify-center p-2 sm:p-4 lg:p-6">
          <div 
            className={`relative w-full ${getSizeClasses()} transform overflow-hidden rounded-xl sm:rounded-2xl bg-white text-left shadow-2xl transition-all max-h-[95vh] overflow-y-auto`}
            onClick={(e) => e.stopPropagation()}
          >
            {showCloseButton && (
              <div className="absolute top-2 right-2 sm:top-4 sm:right-4 z-10">
                <button 
                  onClick={onClose}
                  className="group flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white bg-opacity-20 hover:bg-white hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm"
                >
                  <svg className="w-4 h-4 sm:w-5 sm:h-5 text-white group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            )}
            
            <div className="bg-white">
              {children}
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  // Usar portal para renderizar en el body
  const modalRoot = document.getElementById('modal-root') || document.body;
  return createPortal(modalContent, modalRoot);
};

Modal.propTypes = {
  children: PropTypes.node.isRequired,
  onClose: PropTypes.func.isRequired,
  size: PropTypes.oneOf(['small', 'medium', 'large', 'extra-large', 'full', 'full-screen', 'default']),
  showCloseButton: PropTypes.bool
};

export default Modal;