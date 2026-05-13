import React, { useState } from 'react';
import { Draggable } from "react-beautiful-dnd";
import {
  FaExternalLinkAlt,
  FaRegEye,
  FaRegEdit,
  FaTruckMoving,
  FaCheckCircle,
  FaTimesCircle,
  FaTrash
} from "react-icons/fa";
import Wizard from "../SolicitudWizard/Wizard";  

export default function ChannelColumnContent({ groups = [], onTransitoGroupClick = () => {}, onDeleteGroup = () => {}, currentColumn = "" }) {
  const [wizardOpen, setWizardOpen]   = useState(false);
  const [cotizacionId, setCotizacion] = useState(null);
  const [groupId, setGroupId] = useState(null);

  const openWizard = (id, gId = null) => { 
    setCotizacion(id); 
    setGroupId(gId);
    setWizardOpen(true); 
  };
  const closeWizard= ()    => { 
    setWizardOpen(false); 
    setCotizacion(null); 
    setGroupId(null);
  };

  const goToResponsePage = (groupId) => {
    window.open(`/cotizacion/grupo/${groupId}/responder`, '_blank');
  };

  const handleContinueQuote = (group) => {
    // Obtener información del cliente para reconstruir la URL
    const clientDocument = group.client?.documento;
    if (clientDocument) {
      // Redirigir a la página de cotizaciones con el cliente pre-cargado
      window.location.href = `/cotizacion?search=${clientDocument}&continue=${group.id}`;
    } else {
      // Si no hay documento, redirigir solo con el ID del grupo
      window.location.href = `/cotizacion?continue=${group.id}`;
    }
  };

  const handleDeleteGroup = (group) => {
    const groupId = group.id;
    const groupName = `IDC00${groupId}`;
    
    if (window.confirm(`¿Estás seguro de que quieres eliminar el grupo ${groupName}?\n\nEsta acción no se puede deshacer.`)) {
      onDeleteGroup(groupId);
    }
  };

  if (!groups || groups.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 px-4 text-center">
        <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
          <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
        <p className="text-sm font-medium text-gray-500 mb-1">Sin grupos disponibles</p>
        <p className="text-xs text-gray-400">Los grupos aparecerán aquí cuando sean creados</p>
      </div>
    );
  }

  const NOT_ALLOWED_CUSTOM = [
    "enviada",
    "aceptada",
    "pendiente",
    "en transito",
    "en tránsito",
    "en facturacion",
    "en facturación",
    "completada"
  ];

  return (
    <>
      {groups.map((group, idx) => (
        <Draggable key={group.id} draggableId={String(group.id)} index={idx}>
          {(provided, snapshot) => (
            <div
              ref={provided.innerRef}
              {...provided.draggableProps}
              {...provided.dragHandleProps}
              className={`relative flex flex-col bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-md hover:shadow-xl w-full mb-4 transition-all duration-300 border border-gray-100 overflow-hidden quote-card ${
                snapshot.isDragging ? "ring-2 ring-orange ring-opacity-50 scale-102 shadow-2xl dragging" : ""
              }`}
              style={provided.draggableProps.style}
            >
              {/* CABECERA DEL CARD */}
              <div className="px-6 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3 flex-1 min-w-0">
                    <div className="w-10 h-10 bg-gradient-to-br from-[#FF7C32] to-[#FF6B1A] rounded-xl flex items-center justify-center shadow-sm">
                      <span className="text-white font-bold text-sm">
                        {group.id.toString().slice(-2)}
                      </span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <h6 className="text-lg font-bold text-gray-800 flex items-center gap-2 mb-1">
                        IDC00{group.id}
                        {/* Botón Responder para grupos pendientes */}
                        {["pendiente", "aceptada"].includes(group.status?.toLowerCase()) && (
                          <button
                            onClick={() => goToResponsePage(group.id)}
                            className="inline-flex items-center text-xs px-3 py-1.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg hover:from-emerald-600 hover:to-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md"
                            title="Responder grupo"
                          >
                            <FaRegEye className="w-3 h-3 mr-1.5" />
                            Responder
                          </button>
                        )}
                      </h6>
                      
                      {/* Estado del grupo debajo del ID */}
                      <div className={`inline-block px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm mb-2 ${
                        group.status?.toLowerCase() === 'completada' ? 'bg-green-100 text-green-700' :
                        group.status?.toLowerCase() === 'en tránsito' ? 'bg-blue-100 text-blue-700' :
                        group.status?.toLowerCase() === 'en facturación' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {group.status}
                      </div>
                      
                      <div className="flex items-center gap-3">
                        <span className="text-sm text-gray-500 font-medium">
                          {(() => {
                            // Calcular cantidad de rutas con monto > 0
                            const rutasConMonto = group.cotizaciones.filter((quote) => {
                              const valorGuardado = parseFloat(quote.valor ?? 0);
                              const precioBase = parseFloat(quote.pricing?.price ?? 0);
                              const porcentaje = parseFloat(quote.porcentaje ?? 0);
                              const total = valorGuardado > 0 ? valorGuardado : (precioBase + (precioBase * porcentaje) / 100);
                              return total > 0;
                            }).length;
                            return `${rutasConMonto} ${rutasConMonto === 1 ? 'ruta' : 'rutas'}`;
                          })()}
                        </span>
                        <div className="w-1 h-1 bg-gray-300 rounded-full"></div>
                        <span className="text-sm font-bold text-emerald-600">
                          ${group.valor_total?.toLocaleString("es-CO")}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  {/* Botones de acción */}
                  <div className="flex items-start gap-2 flex-shrink-0">
                    {/* Botón "Eliminar" para grupos en Pre-Solicitud */}
                    {currentColumn === "Pre-Solicitud" && (
                      <button
                        title="Eliminar grupo de cotización"
                        onClick={() => handleDeleteGroup(group)}
                        className="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl p-2.5 hover:scale-110 transition-all duration-200 shadow-lg hover:shadow-xl btn-hover-lift"
                      >
                        <FaTrash className="w-4 h-4" />
                      </button>
                    )}

                    {/* Botón "Continuar cotización" para grupos incompletos */}
                    {(
                      group.status?.toLowerCase() === "borrador" ||
                      (group.status?.toLowerCase() === "pre-solicitud" && group.cotizaciones.length === 0)
                    ) && (
                      <button
                        title="Continuar cotización"
                        onClick={() => handleContinueQuote(group)}
                        className="bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl p-2.5 hover:scale-110 transition-all duration-200 shadow-lg hover:shadow-xl btn-hover-lift"
                      >
                        <FaRegEdit className="w-4 h-4" />
                      </button>
                    )}

                    {/* Botón "En tránsito" */}
                    {(group.status?.toLowerCase() === "en tránsito" ||
                      group.status?.toLowerCase() === "en transito") && (
                      <button
                        title="Ver detalles de grupo en tránsito"
                        onClick={() => onTransitoGroupClick(group)}
                        className="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-2.5 hover:scale-110 transition-all duration-200 shadow-lg hover:shadow-xl btn-hover-lift"
                      >
                        <FaTruckMoving className="w-4 h-4" />
                      </button>
                    )}

                    {/* Botón "Ver" */}
                    {!NOT_ALLOWED_CUSTOM.includes(group.status?.toLowerCase()) && (
                      <button
                        onClick={() => window.open(`/cotizacion/grupo/${group.id}/responder`, '_blank')}
                        className="bg-gradient-to-r from-[#FF7C32] to-[#FF6B1A] text-white rounded-xl p-2.5 hover:scale-110 transition-all duration-200 shadow-lg hover:shadow-xl btn-hover-lift"
                      >
                        <FaRegEye className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* INFORMACIÓN DEL CLIENTE */}
              <div className="px-6 py-3 bg-gray-50 border-b border-gray-100">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-500 rounded-full flex items-center justify-center">
                    <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">
                      {group.client?.cliente ?? group.client?.name ?? "Cliente no asignado"}
                    </p>
                    <p className="text-xs text-gray-500">Cliente</p>
                  </div>
                </div>
              </div>

              {/* LISTA DE COTIZACIONES */}
              <div className="flex flex-col px-6 py-4 gap-3">
                {group.cotizaciones
                  .filter((quote) => {
                    // Filtrar rutas con monto $0
                    const valorGuardado = parseFloat(quote.valor ?? 0);
                    const precioBase = parseFloat(quote.pricing?.price ?? 0);
                    const porcentaje = parseFloat(quote.porcentaje ?? 0);
                    const total = valorGuardado > 0 ? valorGuardado : (precioBase + (precioBase * porcentaje) / 100);
                    return total > 0; // Solo mostrar rutas con monto mayor a 0
                  })
                  .map((quote, i) => {
                  // Usar el precio guardado directamente o calcular si no existe
                  const valorGuardado = parseFloat(quote.valor ?? 0);
                  const precioBase = parseFloat(quote.pricing?.price ?? 0);
                  const porcentaje = parseFloat(quote.porcentaje ?? 0);
                  const total = valorGuardado > 0 ? valorGuardado : (precioBase + (precioBase * porcentaje) / 100);

                  const solicitudCompletada =
                    quote.solicitud && quote.solicitud.estado === "completada";

                  const isAccepted = quote.decision_cliente === "aceptada";
                  const isRejected = quote.decision_cliente === "rechazada";

                  const statusIcon = isAccepted ? (
                    <FaCheckCircle className="text-emerald-500 w-5 h-5" />
                  ) : isRejected ? (
                    <FaTimesCircle className="text-red-500 w-5 h-5" />
                  ) : (
                    <div className="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center">
                      <div className="w-2 h-2 bg-gray-400 rounded-full"></div>
                    </div>
                  );

                  return (
                    <div
                      key={i}
                      className={`relative flex gap-4 p-4 rounded-xl shadow-sm border transition-all duration-200 ${
                        isRejected 
                          ? "bg-red-50 border-red-100 opacity-60" 
                          : isAccepted 
                            ? "bg-emerald-50 border-emerald-100 hover:shadow-md" 
                            : "bg-white border-gray-200 hover:shadow-md hover:border-gray-300"
                      }`}
                    >
                      <div className="flex-shrink-0 flex flex-col items-center gap-2">
                        {statusIcon}
                        <div className="w-3 h-3 bg-gradient-to-br from-[#FF7C32] to-[#FF6B1A] rounded-full shadow-sm" />
                      </div>
                      
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between mb-2">
                          <div className="flex-1">
                            <p className="text-sm font-semibold text-gray-900 mb-1">
                              {quote.ciudad_origen} → {quote.ciudad_destino}
                            </p>
                            <div className="flex flex-wrap gap-2 text-xs">
                              <span className="px-2 py-1 bg-blue-100 text-blue-700 rounded-md font-medium">
                                ${total.toLocaleString("es-CO")}
                              </span>
                              <span className="px-2 py-1 bg-gray-100 text-gray-600 rounded-md">
                                {new Date(quote.created_at).toLocaleDateString('es-CO')}
                              </span>
                            </div>
                          </div>
                          
                          {/* Botón para crear solicitud de transporte */}
                          {group.status?.toLowerCase() === "aceptada" &&
                            !solicitudCompletada &&
                            quote.decision_cliente?.toLowerCase() === "aceptada" && (
                              <button title="Crear solicitud"
                                  onClick={()=>openWizard(quote.id, group.id)}
                                  className="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white p-2 rounded-lg">
                                  <FaRegEdit className="w-4 h-4"/>
                              </button>
                            )}
                        </div>
                        
                        {/* Estado adicional para cotizaciones aceptadas */}
                        {isAccepted && (
                          <div className="flex items-center gap-2 mt-2">
                            <div className="w-2 h-2 bg-emerald-400 rounded-full"></div>
                            <span className="text-xs text-emerald-600 font-medium">
                              {solicitudCompletada ? "Solicitud completada" : "Pendiente solicitud"}
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </Draggable>
      ))}

      {/* ——— único Wizard para toda la lista ——— */}
      {wizardOpen && (
        <Wizard
          open={wizardOpen}
          cotizacionId={cotizacionId}
          groupId={groupId}
          onClose={closeWizard}
        />
      )}
    </>
  );
}
