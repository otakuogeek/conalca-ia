import React, { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { FaTimes, FaCheck, FaExternalLinkAlt } from "react-icons/fa";

export default function ResponseQuoteModal({ open, onClose, groupId }) {
  const [grupo, setGrupo] = useState(null);
  const [loading, setLoading] = useState(false);
  const [decisiones, setDecisiones] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Cargar datos del grupo cuando se abre el modal
  useEffect(() => {
    if (open && groupId && !grupo) {
      loadGroupData();
    }
    
    // Limpiar datos cuando se cierra
    if (!open) {
      setGrupo(null);
      setDecisiones({});
      setLoading(false);
      setSubmitting(false);
    }
  }, [open, groupId]);

  const loadGroupData = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/grupo-cotizacion/${groupId}`);
      if (response.ok) {
        const data = await response.json();
        setGrupo(data);
        
        // Inicializar decisiones con valores actuales
        const initialDecisiones = {};
        data.cotizaciones?.forEach(cot => {
          initialDecisiones[cot.id] = cot.decision_cliente || 'pendiente';
        });
        setDecisiones(initialDecisiones);
      }
    } catch (error) {
      console.error('Error loading group data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDecisionChange = (cotizacionId, decision) => {
    setDecisiones(prev => ({
      ...prev,
      [cotizacionId]: decision
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (submitting) return;

    try {
      setSubmitting(true);
      
      const formData = new FormData();
      formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
      
      Object.entries(decisiones).forEach(([cotId, decision]) => {
        formData.append(`decisiones[${cotId}]`, decision);
      });

      const response = await fetch(`/cotizacion/grupo/${groupId}/responder`, {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        // Notificar éxito y cerrar modal
        if (window.showNotification) {
          window.showNotification('Respuestas guardadas exitosamente', 'success');
        } else {
          alert('Respuestas guardadas exitosamente');
        }
        onClose();
        
        // Recargar la página después de un pequeño delay
        setTimeout(() => window.location.reload(), 500);
      } else {
        throw new Error('Error al guardar respuestas');
      }
    } catch (error) {
      console.error('Error submitting responses:', error);
      if (window.showNotification) {
        window.showNotification('Error al guardar las respuestas', 'error');
      } else {
        alert('Error al guardar las respuestas');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const openInNewTab = () => {
    window.open(`/cotizacion/grupo/${groupId}/responder`, '_blank');
  };

  const handleBackdropClick = (e) => {
    // Solo cerrar si se hace click en el backdrop, no en el contenido del modal
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  if (!open) return null;

  const portalTarget = document.getElementById('modal-root') || document.body;

  return createPortal(
    <div 
      className="fixed inset-0 z-[99990] bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto"
      onClick={handleBackdropClick}
    >
      <div className="flex items-start justify-center min-h-screen p-4 pt-8">
        <div 
          className="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden transform transition-all scale-100"
          onClick={(e) => e.stopPropagation()}
        >
        {/* Header */}
        <div className="bg-gradient-to-r from-[#FF7C32] to-[#FF6B1A] px-6 py-4 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                <span className="text-white font-bold text-sm">
                  {groupId?.toString().slice(-2)}
                </span>
              </div>
              <div>
                <h2 className="text-xl font-bold">
                  Responder Cotización Grupo #{groupId}
                </h2>
                {grupo && (
                  <p className="text-white text-opacity-90 text-sm">
                    Cliente: {grupo.client?.name || 'N/A'} | Referencia: {grupo.reference || 'N/A'}
                  </p>
                )}
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={openInNewTab}
                className="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors"
                title="Abrir en nueva pestaña"
              >
                <FaExternalLinkAlt className="w-4 h-4" />
              </button>
              <button
                onClick={onClose}
                className="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors"
              >
                <FaTimes className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF7C32]"></div>
              <span className="ml-3 text-gray-600">Cargando cotizaciones...</span>
            </div>
          ) : grupo ? (
            <form onSubmit={handleSubmit}>
              <div className="overflow-x-auto">
                <table className="w-full border-collapse">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="px-4 py-3 text-left font-semibold text-gray-700 border-b">Ruta</th>
                      <th className="px-4 py-3 text-left font-semibold text-gray-700 border-b">Vehículo</th>
                      <th className="px-4 py-3 text-left font-semibold text-gray-700 border-b">Valor</th>
                      <th className="px-4 py-3 text-center font-semibold text-gray-700 border-b">Decisión</th>
                    </tr>
                  </thead>
                  <tbody>
                    {grupo.cotizaciones?.map((cot) => (
                      <tr key={cot.id} className="hover:bg-gray-50 transition-colors">
                        <td className="px-4 py-4 border-b">
                          <div className="font-medium text-gray-900">
                            {cot.ciudad_origen} - {cot.ciudad_destino}
                          </div>
                        </td>
                        <td className="px-4 py-4 border-b text-gray-600">
                          {cot.vehiculo_requerido || '-'}
                        </td>
                        <td className="px-4 py-4 border-b">
                          <span className="font-bold text-green-600">
                            ${(cot.pricing?.price * (1 + (cot.porcentaje || 0) / 100) || 0).toLocaleString('es-CO')}
                          </span>
                        </td>
                        <td className="px-4 py-4 border-b">
                          <div className="flex items-center justify-center gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                              <input
                                type="radio"
                                name={`decision_${cot.id}`}
                                value="aceptada"
                                checked={decisiones[cot.id] === 'aceptada'}
                                onChange={(e) => handleDecisionChange(cot.id, e.target.value)}
                                className="text-green-600 focus:ring-green-500"
                              />
                              <span className="text-green-600 font-medium flex items-center gap-1">
                                <FaCheck className="w-3 h-3" />
                                Aceptar
                              </span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                              <input
                                type="radio"
                                name={`decision_${cot.id}`}
                                value="rechazada"
                                checked={decisiones[cot.id] === 'rechazada'}
                                onChange={(e) => handleDecisionChange(cot.id, e.target.value)}
                                className="text-red-600 focus:ring-red-500"
                              />
                              <span className="text-red-600 font-medium flex items-center gap-1">
                                <FaTimes className="w-3 h-3" />
                                Rechazar
                              </span>
                            </label>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Footer with actions */}
              <div className="flex items-center justify-between pt-6 mt-6 border-t">
                <div className="text-sm text-gray-500">
                  Total de rutas: {grupo.cotizaciones?.length || 0}
                </div>
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={onClose}
                    className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-6 py-2 bg-gradient-to-r from-[#FF7C32] to-[#FF6B1A] text-white rounded-lg hover:from-[#FF6B1A] hover:to-[#FF5A00] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  >
                    {submitting ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        Guardando...
                      </>
                    ) : (
                      'Guardar Respuestas'
                    )}
                  </button>
                </div>
              </div>
            </form>
          ) : (
            <div className="text-center py-12">
              <p className="text-gray-500">Error al cargar las cotizaciones</p>
            </div>
          )}
        </div>
        </div>
      </div>
  </div>,
  portalTarget
  );
}
