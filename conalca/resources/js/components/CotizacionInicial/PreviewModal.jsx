// resources/js/components/CotizacionInicial/PreviewModal.jsx
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import { saveQuoteFromChat, sendQuoteEmail } from '../../services/cotizationsService';


const PreviewModal = ({ onClose, onNext, quoteData, clientData, selectedPricings }) => {
  const [emailData, setEmailData] = useState({
    clientName: clientData.clientName || '',
    clientDocument: clientData.documentClient || '',
    clientLocation: '',
    clientPhoneNumbers: '',
    clientEmail: '',
    titleEmail: 'Cotización de Servicios de Transporte',
    promptResponse: 'Nos complace presentarle nuestra propuesta de servicios de transporte, diseñada específicamente para satisfacer sus necesidades logísticas.',
    greeting: 'Quedamos atentos a sus comentarios y esperamos contar con la oportunidad de servirle. Gracias por confiar en nosotros.',
    advisorName: 'Asesor Comercial',
    advisorPhone: '',
    advisorEmail: ''
  });

  const [saving, setSaving] = useState(false);

  const handleInputChange = (field, value) => {
    setEmailData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const getAutomaticParameters = (clientData) => {
    const parameters = [];
    
    // Parámetros por tipo de modalidad
    if (clientData.typeBusiness === 'dta' || clientData.typeBusiness === 'otm') {
      parameters.push({
        name: 'candado_satelital',
        label: 'Candado Satelital',
        color: 'blue'
      });
    }
    
    // Parámetros por tipo de carga
    if (clientData.cargoType === 'refrigerado') {
      parameters.push(
        {
          name: 'jen_set',
          label: 'Jen set',
          color: 'green'
        },
        {
          name: 'combustible',
          label: 'Combustible',
          color: 'green'
        }
      );
    }
    
    if (clientData.cargoType === 'dangerous') {
      parameters.push(
        {
          name: 'kit_derrames',
          label: 'Kit de derrames',
          color: 'red'
        },
        {
          name: 'pictogramas',
          label: 'Pictogramas',
          color: 'red'
        }
      );
    }
    
    return parameters;
  };

  // 🆕 Helper para detectar si el embalaje es un contenedor
  const isContainerPacking = (embalaje) => {
    if (!embalaje) return false;
    const normalized = String(embalaje).toUpperCase();
    return normalized.includes('CONTENEDOR') || 
           normalized.includes('CONTAINER') ||
           /\d+X(20|40|45)/i.test(normalized) || // Formato 1X20, 4X40, etc.
           /^(20|40|45)\s*['"]?\s*(HC|GP|OT|RF|FR)?$/i.test(normalized); // Formato 40 HC, 20', etc.
  };
  // 🆕 Helper para obtener el tipo de contenedor (20, 40 o 45 pies)
  const getContainerSize = (embalaje) => {
    if (!embalaje) return null;
    const normalized = String(embalaje).toUpperCase();
    if (normalized.includes('20') || /\d+X20/i.test(normalized)) return 20;
    if (normalized.includes('45') || /\d+X45/i.test(normalized)) return 45;
    if (normalized.includes('40') || /\d+X40/i.test(normalized)) return 40;
    return null;
  };

  // 🆕 Helper para obtener la tara según tipo de contenedor
  const getContainerTara = (embalaje) => {
    const size = getContainerSize(embalaje);
    switch (size) {
      case 20: return 2300; // kg
      case 40: return 3400; // kg
      case 45: return 3400; // kg (mismo que 40)
      default: return 0;
    }
  };

  // 🆕 Helper para detectar si es operación de exportación
  const isExportOperation = () => {
    const opType = (clientData.operationType || '').toLowerCase();
    return opType.includes('export') || opType === 'exportacion' || opType === 'exportación';
  };

  // 🆕 Helper para verificar si hay contenedores en la cotización
  const hasContainersInQuote = () => {
    return quoteData.some(route => {
      const embalaje = route.tipo_embajale || route.empaque || route.tipo_embalaje || '';
      return isContainerPacking(embalaje);
    });
  };
  const buildRouteFinancials = (route, index) => {
    const pricing = selectedPricings[index];
    const embalaje = route.tipo_embajale || route.empaque || route.tipo_embalaje || '';
    const isContainer = isContainerPacking(embalaje);
    const containerSize = getContainerSize(embalaje);
    const containerTara = getContainerTara(embalaje);
    
    if (!pricing) {
      return {
        pricing: null,
        basePrice: 0,
        porcentaje: Number(route.porcentaje) || 0,
        parametersTotal: 0,
        acompanamiento: Number(route.itesoltra_acompanamientovalor) || 0,
        valueWithMargin: 0,
        finalValue: 0,
        valuePerUnit: 0,
        totalValueInternal: 0,
        isContainer,
        containerQuantity: 1,
        containerSize,
        containerTara,
      };
    }

    const basePrice = Number(pricing.price) || 0;
    const porcentaje = Number(route.porcentaje) || 0;
    const acompanamiento = Number(route.itesoltra_acompanamientovalor) || 0;

    let parametersTotal = 0;
    const parameters = getAutomaticParameters(clientData);
    parameters.forEach(param => {
      parametersTotal += Number(route[param.name]) || 0;
    });

    const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
    const valuePerUnit = valueWithMargin + acompanamiento + parametersTotal;
    
    // 🆕 Cantidad de contenedores (solo para sistema interno)
    const containerQuantity = isContainer ? (Number(route.cantidad) || 1) : 1;
    
    // 🔥 IMPORTANTE: 
    // - valuePerUnit: valor por UNIDAD de contenedor (lo que ve el cliente)
    // - totalValueInternal: valor total multiplicado por cantidad (para sistema interno)
    // - finalValue: ahora es el valor POR UNIDAD (lo que se muestra al cliente)
    const totalValueInternal = isContainer ? valuePerUnit * containerQuantity : valuePerUnit;

    console.log(`🚛 Ruta ${index + 1} - Cálculo financiero:`, {
      embalaje,
      isContainer,
      containerSize,
      containerTara,
      cantidad: route.cantidad,
      containerQuantity,
      basePrice,
      valueWithMargin,
      valuePerUnit,
      totalValueInternal,
      '📋 AL CLIENTE': valuePerUnit,
      '💾 SISTEMA INTERNO': totalValueInternal
    });

    return {
      pricing,
      basePrice,
      porcentaje,
      parametersTotal,
      acompanamiento,
      valueWithMargin,
      finalValue: valuePerUnit, // 🔥 Al cliente se muestra valor POR UNIDAD
      valuePerUnit, // Valor unitario (por contenedor)
      totalValueInternal, // 🆕 Valor total para sistema interno
      isContainer,
      containerQuantity,
      containerSize,
      containerTara,
    };
  };

  // 🔥 Total para mostrar AL CLIENTE (suma de valores por unidad)
  const calculateRouteTotal = (route, index) => {
    const { valuePerUnit } = buildRouteFinancials(route, index);
    return valuePerUnit;
  };

  // 🔥 Total para CLIENTE (suma de valores unitarios)
  const calculateTotal = () => {
    const total = quoteData.reduce((total, route, index) => {
      const routeTotal = calculateRouteTotal(route, index);
      console.log(`Ruta ${index + 1} - Valor unitario para cliente:`, routeTotal);
      return total + routeTotal;
    }, 0);
    
    console.log('Total final para cliente (suma unitarios):', total);
    return total;
  };

  // 🆕 Total para SISTEMA INTERNO (incluye multiplicación por cantidad de contenedores)
  const calculateTotalInternal = () => {
    const total = quoteData.reduce((total, route, index) => {
      const { totalValueInternal } = buildRouteFinancials(route, index);
      console.log(`Ruta ${index + 1} - Valor total interno:`, totalValueInternal);
      return total + totalValueInternal;
    }, 0);
    
    console.log('Total final para sistema interno:', total);
    return total;
  };

    const buildQuotesPayload = (quoteData, clientData, selectedPricings, getAutomaticParameters) => {
    return quoteData.map((route, index) => {
      const pricing = selectedPricings[index];
      const basePrice = Number(pricing?.price) || 0;
      const porcentaje = Number(route.porcentaje) || 0;
      const acompanamiento = Number(route.itesoltra_acompanamientovalor) || 0;

      let parametersTotal = 0;
      getAutomaticParameters(clientData).forEach(param => {
        parametersTotal += Number(route[param.name]) || 0;
      });

      const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
      const valuePerUnit = valueWithMargin + acompanamiento + parametersTotal;
      
      // 🆕 Detectar si es contenedor y calcular valores
      const embalaje = route.tipo_embajale || route.empaque || route.tipo_embalaje || '';
      const isContainer = isContainerPacking(embalaje);
      const containerQuantity = isContainer ? (Number(route.cantidad) || 1) : 1;
      const containerSize = getContainerSize(embalaje);
      const containerTara = getContainerTara(embalaje);
      
      // 🔥 totalValueInternal: para sistema interno (cantidad * valor unitario)
      const totalValueInternal = isContainer ? valuePerUnit * containerQuantity : valuePerUnit;

      return {
        id: route.id || null, // <-- send existing cotización id to update
        ciudad_origen: String(route.ciudad_origen || ''),
        ciudad_destino: String(route.ciudad_destino || ''),
        peso_mercancia: String(route.peso_mercancia || ''),
        tipo_producto: String(route.tipo_producto || ''),
        vehiculo_requerido: String(route.vehiculo_requerido || ''),
        valor_declarado: String(route.valor_declarado || ''),
        porcentaje,
        precio_base: basePrice,
        valor_parametros: parametersTotal,
        valor_acompanamiento: acompanamiento,
        // 🔥 Sistema interno recibe el TOTAL (cantidad * valor unitario)
        finalValue: totalValueInternal,
        valor: totalValueInternal,
        valor_final: totalValueInternal,
        // 🆕 Valor por unidad/contenedor (lo que ve el cliente)
        valor_unitario: valuePerUnit,
        valor_cliente: valuePerUnit, // 🆕 Explícito para el cliente
        es_contenedor: isContainer,
        cantidad_contenedores: containerQuantity,
        tamano_contenedor: containerSize, // 🆕 20, 40 o 45 pies
        tara_contenedor: containerTara, // 🆕 Tara según tipo (2300kg o 3400kg)
        precio_pricing_id: pricing?.id ?? null, // <-- pricing id for upsert
        cantidad: String(route.cantidad || '1'),
        tipo_embajale: String(route.tipo_embajale || 'Bultos'),
        dimensiones_exactas: String(route.dimensiones_exactas || 'No especificado'),
        registro_fotografico: String(route.registro_fotografico || 'No requerido'),
        candado_satelital: Number(route.candado_satelital) || 0,
        jen_set: Number(route.jen_set) || 0,
        combustible: Number(route.combustible) || 0,
        kit_derrames: Number(route.kit_derrames) || 0,
        pictogramas: Number(route.pictogramas) || 0,
        itesoltra_acompanamientovalor: acompanamiento,
      };
    });
  };

  const buildSavePayload = (clientData, quotesToSave) => ({
    client_id: clientData.clientId,
    quote_data: quotesToSave,
    thread_id: clientData.threadId || null,
    type_business: clientData.typeBusiness || 'Terrestre',
    operation_type: clientData.operationType || null,
  });



  
  const handleSendQuote = async () => {
    if (!emailData.clientEmail) {
      alert('El correo electrónico es obligatorio para enviar la cotización');
      return;
    }

    if (!isValidEmail(emailData.clientEmail)) {
      alert('Por favor, ingrese un correo electrónico válido');
      return;
    }

    if (!clientData.clientId) {
      alert('Error: No se ha seleccionado un cliente válido.');
      return;
    }

    if (!quoteData || quoteData.length === 0) {
      alert('Error: No hay rutas de cotización para guardar.');
      return;
    }

    setSaving(true);

    try {
      const quotesToSave = buildQuotesPayload(
        quoteData,
        clientData,
        selectedPricings,
        getAutomaticParameters
      );

      const savePayload = buildSavePayload(clientData, quotesToSave);
      const { data: saveResult } = await saveQuoteFromChat(savePayload);

      const groupId = saveResult?.data?.group_id || saveResult?.group_id;
      if (!groupId) {
        throw new Error('No se pudo obtener el group_id de la respuesta del servidor');
      }

      // Support multiple emails (comma/semicolon separated)
      const emailList = parseEmails(emailData.clientEmail);

      await sendQuoteEmail({
        group_id: groupId,
        client_email: emailList,
        email_data: {
          title: emailData.titleEmail,
          text: emailData.promptResponse,
          greeting: emailData.greeting,
          client_name: emailData.clientName,
          client_document: emailData.clientDocument,
          client_location: emailData.clientLocation,
          client_phone_numbers: emailData.clientPhoneNumbers,
          asesor_name: emailData.advisorName,
          asesor_phone: emailData.advisorPhone,
          asesor_email: emailData.advisorEmail,
          routes: quotesToSave,
          total_price: calculateTotal(),
        },
      });

      onNext(); // modal éxito
    } catch (error) {
      console.error('Error sending quote:', error);
      alert(`Error al enviar la cotización: ${error.message}. Por favor, intente nuevamente.`);
    } finally {
      setSaving(false);
    }
  };

  const handleSaveDraft = async () => {
    if (!clientData.clientId) {
      alert('Error: No se ha seleccionado un cliente válido.');
      return;
    }
    if (!quoteData.length) {
      alert('Error: No hay rutas para guardar.');
      return;
    }

    try {
      setSaving(true);
      const quotesToSave = buildQuotesPayload(quoteData, clientData, selectedPricings, getAutomaticParameters);
      const payload = buildSavePayload(clientData, quotesToSave);

      const { data } = await saveQuoteFromChat(payload);
      console.log('[PreviewModal] Draft saved', data);
      alert('Cotización guardada como borrador / Pre-solicitud.');
      window.location.reload();
    } catch (error) {
      console.error('[PreviewModal] Error saving draft', error);
      alert(error.response?.data?.error || 'Error al guardar la cotización.');
    } finally {
      setSaving(false);
    }
  };

  const isValidSingleEmail = (email) => {
    const trimmed = email.trim();
    return trimmed && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
  };

  const isValidEmail = (email) => {
    if (!email) return false;
    // Support comma or semicolon separated emails
    const emails = email.split(/[,;]/).map(e => e.trim()).filter(e => e.length > 0);
    if (emails.length === 0) return false;
    return emails.every(e => isValidSingleEmail(e));
  };

  const parseEmails = (email) => {
    if (!email) return [];
    return email.split(/[,;]/).map(e => e.trim()).filter(e => e.length > 0);
  };

  return (
    <Modal onClose={onClose} size="extra-large">
      <style jsx>{`
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        .inter-font { font-family: 'Inter', sans-serif; }
        .input-field {
          border: 1px solid #e5e7eb;
          background-color: #f9f9f9;
          padding: 0.75rem;
          border-radius: 0.5rem;
          font-size: 0.75rem;
          transition: all 0.2s;
        }
        .input-field:focus {
          border-color: #fb923c;
          background-color: white;
          outline: none;
          box-shadow: 0 0 0 2px rgba(251, 146, 60, 0.1);
        }
        .scrollbar-custom::-webkit-scrollbar { width: 4px; }
        .scrollbar-custom::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
        .scrollbar-custom::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 2px; }
        .scrollbar-custom::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
      `}</style>

      {/* Header del Modal */}
      <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h2 className="text-lg font-semibold">Cotización - Vista Previa y Envío</h2>
          </div>
          <div className="flex items-center space-x-2">
            <button 
              type="button"
              onClick={handleSaveDraft}
              disabled={saving}
              className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-orange-600 bg-white hover:bg-gray-50 transition-colors duration-200 shadow-sm"
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
              </svg>
              Guardar
            </button>
          </div>
        </div>
      </div>

      {/* Contenido Principal - 40/60 layout */}
      <div className="flex bg-gray-50 overflow-hidden" style={{ height: '85vh' }}>
        {/* Columna Izquierda: Vista Previa (40%) */}
        <div className="w-2/5 bg-white border-r border-gray-200 overflow-hidden flex flex-col">
          <div className="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
            <h3 className="text-sm font-semibold text-gray-800 flex items-center">
              <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              Vista Previa del Documento
            </h3>
          </div>
          
          <div className="p-6 overflow-y-auto scrollbar-custom bg-white">
            {/* Logo y Header */}
            <div className="flex justify-end mb-4">
              <img src="/img/logo-conalca.png" alt="logo-conalca" className="w-24 h-6 object-cover" />
            </div>
            
            <div className="space-y-3 text-xs">
              <h1 className="uppercase text-sm font-bold text-gray-800 mb-4">
                Presentación y cotización conalca
              </h1>
              
              {/* Información del Cliente */}
              <div className="space-y-2 bg-gray-50 p-3 rounded-lg">
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <span className="text-gray-500">Estimado(a):</span>
                    <span className="font-medium text-gray-800 block">
                      {emailData.clientName || '[Nombre del cliente]'}
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-500">NIT:</span>
                    <span className="font-medium text-gray-800 block">
                      {emailData.clientDocument || '[NIT del cliente]'}
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-500">Ubicación:</span>
                    <span className="font-medium text-gray-800 block">
                      {emailData.clientLocation || '[Ubicación del cliente]'}
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-500">Teléfonos:</span>
                    <span className="font-medium text-gray-800 block">
                      {emailData.clientPhoneNumbers || '[Teléfonos del cliente]'}
                    </span>
                  </div>
                </div>
                <p className="text-xs text-gray-700 mt-2">
                  De parte de <span className="font-medium">{emailData.advisorName || '[Nombre del asesor]'}</span> 
                  de Conalca (NIT: 900416879), le extendemos un cordial saludo.
                </p>
              </div>

              {/* Título y Fecha */}
              <div className="my-4 p-3 bg-orange-50 rounded-lg border-l-4 border-orange-400">
                <h2 className="uppercase font-bold text-orange-800 text-sm mb-1">
                  {emailData.titleEmail || '[Título de la cotización]'}
                </h2>
                <p className="text-xs text-gray-600">
                  {new Date().toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </p>
              </div>

              {/* Descripción */}
              <div className="my-4 p-3 bg-gray-50 rounded-lg">
                <p className="text-xs text-gray-700 leading-relaxed">
                  {emailData.promptResponse}
                </p>
              </div>

              {/* Tabla de Rutas */}
              <div className="my-4">
                <div className="overflow-x-auto">
                  <table className="w-full border-collapse border border-gray-200 rounded-lg overflow-hidden shadow-sm text-xs">
                    <thead>
                      <tr className="bg-orange-100 text-gray-800 font-semibold">
                        <th className="border border-gray-200 px-2 py-2 text-center">#</th>
                        <th className="border border-gray-200 px-2 py-2 text-center">Origen</th>
                        <th className="border border-gray-200 px-2 py-2 text-center">Destino</th>
                        <th className="border border-gray-200 px-2 py-2 text-center">Vehículo</th>
                        <th className="border border-gray-200 px-2 py-2 text-center">Parámetros</th>
                        <th className="border border-gray-200 px-2 py-2 text-center">
                          {hasContainersInQuote() ? 'Valor x Unidad' : 'Valor'}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {quoteData.map((route, index) => {
                        const {
                          basePrice,
                          porcentaje,
                          parametersTotal,
                          acompanamiento,
                          valueWithMargin,
                          finalValue,
                          valuePerUnit,
                          totalValueInternal,
                          isContainer,
                          containerQuantity,
                          containerSize,
                          containerTara,
                        } = buildRouteFinancials(route, index);

                        const automaticParameters = getAutomaticParameters(clientData);
                        const activeParameters = automaticParameters.reduce((list, param) => {
                          const value = Number(route[param.name]) || 0;
                          if (value > 0) {
                            list.push({ ...param, value });
                          }
                          return list;
                        }, []);

                        const isReturn = route.isReturnRoute || route.is_return_route;

                        return (
                          <tr key={index} className={`hover:bg-orange-100 transition-colors duration-150 ${isReturn ? 'bg-blue-50' : 'bg-orange-50'}`}>
                            <td className="border border-gray-200 px-2 py-2 text-center font-medium">
                              {isReturn ? (
                                <span className="text-[10px] font-semibold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded">↩ DEV</span>
                              ) : (
                                index + 1
                              )}
                            </td>
                            <td className="border border-gray-200 px-2 py-2 text-center">
                              {isReturn && (
                                <div className="text-[10px] font-semibold text-blue-600 mb-0.5">DEVOLUCIÓN</div>
                              )}
                              {route.ciudad_origen || '-'}
                            </td>
                            <td className="border border-gray-200 px-2 py-2 text-center">{route.ciudad_destino || '-'}</td>
                            <td className="border border-gray-200 px-2 py-2 text-center">{route.vehiculo_requerido || '-'}</td>
                            <td className="border border-gray-200 px-2 py-2 text-center">
                              {/* 🆕 Mostrar cantidad de contenedores si aplica */}
                              {isContainer && containerQuantity > 1 && (
                                <div className="text-purple-600 text-[10px] font-medium mb-1">
                                  Candado Satelital<br />
                                  ${Number(route.candado_satelital || 10500).toLocaleString()}
                                </div>
                              )}
                              {activeParameters.length > 0 ? (
                                <div className="space-y-1">
                                  {activeParameters.map((param, paramIndex) => (
                                    <div key={paramIndex} className={`text-${param.color}-600 font-medium`}>
                                      <div className="text-[10px]">{param.label}</div>
                                      <div className="text-xs">${param.value.toLocaleString()}</div>
                                    </div>
                                  ))}
                                  {acompanamiento > 0 && (
                                    <div className="text-blue-600 text-[10px] font-medium">
                                      Acompañamiento: ${acompanamiento.toLocaleString()}
                                    </div>
                                  )}
                                  <div className="text-[10px] text-gray-500 mt-1">
                                    Total: ${(parametersTotal + acompanamiento).toLocaleString()}
                                  </div>
                                </div>
                              ) : (
                                <span className="text-gray-400">
                                  {acompanamiento > 0
                                    ? `Acompañamiento: $${acompanamiento.toLocaleString()}`
                                    : '-'}
                                </span>
                              )}
                            </td>
                            <td className="border border-gray-200 px-2 py-2 text-center">
                              <div className="space-y-1">
                                {/* 🔥 Mostrar siempre el valor POR UNIDAD al cliente */}
                                <div className="font-bold text-green-700">
                                  ${Number(valuePerUnit).toLocaleString()}
                                </div>
                                {/* 🆕 Si es contenedor, indicar claramente que es por unidad */}
                                {isContainer && (
                                  <div className="text-[10px] text-purple-600 font-medium">
                                    por contenedor {containerSize ? `${containerSize}'` : ''}
                                  </div>
                                )}
                                {/* Informar cantidad solicitada si es > 1 */}
                                {isContainer && containerQuantity > 1 && (
                                  <div className="text-[10px] text-gray-500 italic">
                                    (Ud. solicitó {containerQuantity} unidades)
                                  </div>
                                )}
                                {/* Mostrar tara si es contenedor */}
                                {isContainer && containerTara > 0 && (
                                  <div className="text-[10px] text-blue-500">
                                    Tara: {containerTara.toLocaleString()} kg
                                  </div>
                                )}
                                <div className="text-[10px] text-gray-500">
                                  Base (${basePrice.toLocaleString()}) + {porcentaje}%
                                </div>
                                {(parametersTotal + acompanamiento) > 0 && (
                                  <div className="text-[10px] text-gray-500">
                                    Parámetros/Acomp.: ${(parametersTotal + acompanamiento).toLocaleString()}
                                  </div>
                                )}
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
                
                {/* Información de parámetros aplicados automáticamente */}
                {(() => {
                  const appliedParameters = getAutomaticParameters(clientData);
                  const hasAnyParameterValues = quoteData.some(route => 
                    appliedParameters.some(param => parseFloat(route[param.name]) > 0)
                  );
                  
                  if (appliedParameters.length > 0) {
                    return (
                      <div className="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 className="text-xs font-semibold text-blue-800 mb-2 flex items-center">
                          <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"></path>
                          </svg>
                          Parámetros aplicados automáticamente
                        </h4>
                        <div className="text-xs text-blue-700">
                          <div className="space-y-1">
                            {clientData.typeBusiness === 'dta' && (
                              <div>• <strong>Modalidad DTA:</strong> Incluye Candado Satelital automáticamente</div>
                            )}
                            {clientData.typeBusiness === 'otm' && (
                              <div>• <strong>Modalidad OTM:</strong> Incluye Candado Satelital automáticamente</div>
                            )}
                            {clientData.cargoType === 'refrigerado' && (
                              <div>• <strong>Carga Refrigerada:</strong> Incluye Jen set y Combustible automáticamente</div>
                            )}
                            {clientData.cargoType === 'dangerous' && (
                              <div>• <strong>Mercancía Peligrosa:</strong> Incluye Kit de derrames y Pictogramas automáticamente</div>
                            )}
                          </div>
                          {hasAnyParameterValues && (
                            <div className="mt-2 text-blue-600 font-medium">
                              ✓ Los costos de estos parámetros están incluidos en el valor total de cada ruta
                            </div>
                          )}
                        </div>
                      </div>
                    );
                  }
                  return null;
                })()}

                {/* 🆕 Notas aclaratorias para CONTENEDORES */}
                {hasContainersInQuote() && (
                  <div className="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                    <h4 className="text-xs font-semibold text-purple-800 mb-2 flex items-center">
                      <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"></path>
                      </svg>
                      Información importante sobre contenedores
                    </h4>
                    <div className="text-xs text-purple-700 space-y-1">
                      <div>• <strong>Cotización por unidad:</strong> Los valores mostrados corresponden al costo <strong>por cada contenedor</strong>.</div>
                      <div>• <strong>Tara de contenedores:</strong></div>
                      <div className="ml-4">- Contenedor 20': <strong>2,300 kg</strong></div>
                      <div className="ml-4">- Contenedor 40'/45': <strong>3,400 kg</strong></div>
                      <div>• <strong>Pesos diferentes:</strong> Si los contenedores tienen pesos distintos, cada uno se cotiza como ruta separada.</div>
                    </div>
                  </div>
                )}

                {/* 🆕 Notas aclaratorias para EXPORTACIÓN */}
                {isExportOperation() && hasContainersInQuote() && (
                  <div className="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <h4 className="text-xs font-semibold text-amber-800 mb-2 flex items-center">
                      <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                      </svg>
                      Nota importante - Operación de Exportación
                    </h4>
                    <div className="text-xs text-amber-700 space-y-1">
                      <div>• <strong>Lugar de retiro:</strong> Esta tarifa aplica <strong>únicamente</strong> si el contenedor se retira en patios de <strong>Bogotá, Medellín o Cali</strong>.</div>
                      <div>• <strong>Retiro en Tocancipá:</strong> Si el retiro se realiza en Tocancipá u otras ubicaciones, se aplicará un <strong>costo adicional de transporte</strong>.</div>
                      <div>• <strong>Llenado del contenedor:</strong> Es importante especificar el lugar exacto de llenado para aplicar correctamente las tarifas.</div>
                      <div className="mt-2 p-2 bg-amber-100 rounded text-amber-800 font-medium">
                        ⚠️ Por favor confirme el lugar de retiro y llenado del contenedor para validar la tarifa.
                      </div>
                    </div>
                  </div>
                )}
                
                <div className="mt-3 flex justify-end">
                  <div className="bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                    <span className="text-sm font-bold text-green-700">
                      {hasContainersInQuote() ? 'Total (suma de valores unitarios): ' : 'Total: '}
                      ${Number(calculateTotal()).toLocaleString()}
                    </span>
                  </div>
                </div>
              </div>

              {/* Saludo Final */}
              <div className="my-4 text-xs text-gray-700 bg-gray-50 p-3 rounded-lg">
                {emailData.greeting}
              </div>

              {/* Información del Asesor */}
              <div className="mt-4 pt-3 border-t border-gray-200">
                <div className="text-xs text-gray-700 space-y-1 bg-gray-50 p-3 rounded-lg">
                  <p><span className="font-medium">Asesor:</span> {emailData.advisorName}</p>
                  <p><span className="font-medium">Tel:</span> {emailData.advisorPhone || '[Teléfono del asesor]'}</p>
                  <p><span className="font-medium">Email:</span> {emailData.advisorEmail || '[Email del asesor]'}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Columna Derecha: Panel de Configuración (60%) */}
        <div className="w-3/5 bg-gray-50 overflow-y-auto scrollbar-custom flex flex-col">
          <div className="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
            <h3 className="text-xs font-semibold text-gray-800 flex items-center">
              <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Edición del Documento
            </h3>
          </div>
          
          <div className="p-6 overflow-y-auto scrollbar-custom bg-white">
            {/* Información del Cliente */}
            <div className="mb-6 bg-white rounded-lg shadow-sm border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-800 mb-3 flex items-center">
                <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Cliente
              </h3>
              <div className="space-y-2">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs text-gray-600 mb-1 font-medium">Nombre</label>
                    <input 
                      type="text" 
                      value={emailData.clientName}
                      onChange={(e) => handleInputChange('clientName', e.target.value)}
                      className="input-field w-full text-xs" 
                      placeholder="Nombre completo"
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-gray-600 mb-1 font-medium">NIT/Doc</label>
                    <input 
                      type="text" 
                      value={emailData.clientDocument}
                      onChange={(e) => handleInputChange('clientDocument', e.target.value)}
                      className="input-field w-full text-xs" 
                      placeholder="NIT o documento"
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-gray-600 mb-1 font-medium">Ubicación</label>
                    <input 
                      type="text" 
                      value={emailData.clientLocation}
                      onChange={(e) => handleInputChange('clientLocation', e.target.value)}
                      className="input-field w-full text-xs" 
                      placeholder="Ciudad/Dirección"
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-gray-600 mb-1 font-medium">Teléfonos</label>
                    <input 
                      type="text" 
                      value={emailData.clientPhoneNumbers}
                      onChange={(e) => handleInputChange('clientPhoneNumbers', e.target.value)}
                      className="input-field w-full text-xs" 
                      placeholder="Contactos"
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* Información de la Cotización */}
            <div className="mb-6 bg-white rounded-lg shadow-sm border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-800 mb-3 flex items-center">
                <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Cotización
              </h3>
              <div className="space-y-2">
                <div>
                  <label className="block text-xs text-gray-600 mb-1 font-medium">Título</label>
                  <input 
                    type="text" 
                    value={emailData.titleEmail}
                    onChange={(e) => handleInputChange('titleEmail', e.target.value)}
                    className="input-field w-full text-xs" 
                    placeholder="Título de la cotización"
                  />
                </div>
                <div>
                  <label className="block text-xs text-gray-600 mb-1 font-medium">Descripción</label>
                  <textarea 
                    value={emailData.promptResponse}
                    onChange={(e) => handleInputChange('promptResponse', e.target.value)}
                    className="input-field w-full min-h-[60px] resize-none text-xs"
                    rows="3"
                    placeholder="Descripción de servicios..."
                  />
                </div>
              </div>
            </div>

            {/* Información del Asesor */}
            <div className="mb-6 bg-white rounded-lg shadow-sm border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-800 mb-3 flex items-center">
                <svg className="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Confirmar correo del cliente para envío
                <span className="text-red-500 ml-1">*</span>
              </h3>
              <div className="space-y-3">
                <div>
                  <label className="block text-xs text-gray-600 mb-1 font-medium">
                    Email de destino <span className="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    value={emailData.clientEmail}
                    onChange={(e) => handleInputChange('clientEmail', e.target.value)}
                    className={`input-field w-full text-xs ${
                      !emailData.clientEmail ? 'border-red-300 bg-red-50' :
                      isValidEmail(emailData.clientEmail) ? 'border-green-300 bg-green-50' : 'border-orange-300 bg-orange-50'
                    }`}
                    placeholder="correo1@ejemplo.com, correo2@ejemplo.com"
                    required
                  />
                  <p className="text-gray-400 text-xs mt-1">
                    Puede ingresar varios correos separados por coma (,) o punto y coma (;)
                  </p>
                  {!emailData.clientEmail && (
                    <p className="text-red-500 text-xs mt-1 flex items-center">
                      <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                      </svg>
                      El correo electrónico es obligatorio para enviar la cotización
                    </p>
                  )}
                  {emailData.clientEmail && !isValidEmail(emailData.clientEmail) && (
                    <p className="text-orange-500 text-xs mt-1 flex items-center">
                      <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd"></path>
                      </svg>
                      Uno o más correos no tienen formato válido
                    </p>
                  )}
                  {isValidEmail(emailData.clientEmail) && (
                    <p className="text-green-600 text-xs mt-1 flex items-center">
                      <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path>
                      </svg>
                      La cotización se enviará a: <strong>{parseEmails(emailData.clientEmail).join(', ')}</strong>
                      {parseEmails(emailData.clientEmail).length > 1 && (
                        <span className="ml-1 text-green-500">({parseEmails(emailData.clientEmail).length} destinatarios)</span>
                      )}
                    </p>
                  )}
                </div>
              </div>
            </div>

            {/* Botón de Envío */}
            <div className="p-4 bg-white border-t border-gray-200 space-y-3">
              <button 
                onClick={handleSendQuote}
                disabled={saving || !emailData.clientEmail || !isValidEmail(emailData.clientEmail)}
                className={`w-full py-3 rounded-lg font-semibold text-white transition-all duration-200 text-xs ${
                  (saving || !emailData.clientEmail || !isValidEmail(emailData.clientEmail))
                    ? 'bg-gray-400 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 shadow-md hover:shadow-lg'
                }`}
              >
                <div className="flex items-center justify-center space-x-2">
                  {saving ? (
                    <>
                      <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                      </svg>
                      <span>Enviando...</span>
                    </>
                  ) : (
                    <>
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                      </svg>
                      <span>
                        {!emailData.clientEmail 
                          ? 'Ingrese el correo para enviar'
                          : !isValidEmail(emailData.clientEmail)
                          ? 'Correo inválido'
                          : 'Enviar Cotización'
                        }
                      </span>
                    </>
                  )}
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
};

PreviewModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  onNext: PropTypes.func.isRequired,
  quoteData: PropTypes.array.isRequired,
  clientData: PropTypes.object.isRequired,
  selectedPricings: PropTypes.object.isRequired
};

export default PreviewModal;