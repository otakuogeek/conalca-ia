// resources/js/components/CotizacionInicial/PricingModal.jsx
import React, { useState, useEffect, useMemo } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import { 
  fetchLatestPricingsByRoute,
  fetchVehicleSuggestions,
  fetchRentabilityStats
 } from '../../services/pricingService';
import { FaSpinner } from 'react-icons/fa';

const PricingModal = ({ 
  onClose, 
  onNext,
  quoteData,
  setQuoteData,
  pricings,
  setPricings,
  selectedPricings,
  setSelectedPricings,
  porcentajeGlobal,
  setPorcentajeGlobal,
  clientData 
}) => {
  // const [loading, setLoading] = useState(false);
  const [loadingPricings, setLoadingPricings] = useState(false);
  const [loadingSuggestions, setLoadingSuggestions] = useState(false);
  const [suggestionsKey, setSuggestionsKey] = useState(null);
  const [errors, setErrors] = useState({});
  const [vehicleSuggestions, setVehicleSuggestions] = useState({});
  const [initialPorcentajeApplied, setInitialPorcentajeApplied] = useState(false);
  const [rentabilityDefaults, setRentabilityDefaults] = useState({
    min: 17,
    avg: 24,
    max: 32,
    scope: 'fallback',
  });
  const showBlockingSpinner = loadingPricings || loadingSuggestions;

  const routesSignature = useMemo(() => (
    JSON.stringify(
      quoteData.map(route => ({
        ciudad_origen: route.ciudad_origen || '',
        ciudad_destino: route.ciudad_destino || '',
        peso_mercancia: route.peso_mercancia || 0,
      }))
    )
  ), [quoteData]);

  useEffect(() => {
    if (!routesSignature) return;
    loadPricingsForRoutes();
    loadRentabilityStats();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [routesSignature]);

  useEffect(() => {
    if (
      !initialPorcentajeApplied &&
      rentabilityDefaults.min &&
      rentabilityDefaults.min > 0
    ) {
      applyGlobalPorcentaje(rentabilityDefaults.min);
      setInitialPorcentajeApplied(true);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rentabilityDefaults]);

  const loadRentabilityStats = async () => {
    // pick the first valid route as reference for the global cards
    const referenceRoute = quoteData.find(route => route.ciudad_origen && route.ciudad_destino);

    try {
      const { data } = await fetchRentabilityStats({
        origin: referenceRoute?.ciudad_origen,
        destination: referenceRoute?.ciudad_destino,
      });

      setRentabilityDefaults({
        min: data.min || 0,
        avg: data.avg || 0,
        max: data.max || 0,
        scope: data.scope,
      });
    } catch (error) {
      console.error('[PricingModal] Error loading rentability stats', error);
    }
  };

const loadPricingsForRoutes = async () => {
  setLoadingPricings(true);
  try {
    const responses = await Promise.all(
      quoteData.map(route => {
        if (!route.ciudad_origen || !route.ciudad_destino) return Promise.resolve([]);
        return fetchLatestPricingsByRoute({
          origin: route.ciudad_origen,
          destination: route.ciudad_destino,
        })
          .then(({ data }) => data)
          .catch(error => {
            console.error('[PricingModal] Error loading pricing for route', route, error);
            return [];
          });
      })
    );
    setPricings(responses);
  } catch (error) {
    console.error('[PricingModal] Error loading all pricings:', error);
  } finally {
    setLoadingPricings(false);
  }
};

useEffect(() => {
  if (loadingPricings) return;
  if (!pricings.length) return;

  const currentKey = JSON.stringify(pricings);
  if (currentKey === suggestionsKey) return;

  requestAISuggestions(currentKey);
  // eslint-disable-next-line react-hooks/exhaustive-deps
}, [loadingPricings, pricings]);

const requestAISuggestions = async (currentKey) => {
  setLoadingSuggestions(true);
  
  try {
    const payload = {
      routes: quoteData.map((route, index) => ({
        ciudad_origen: route.ciudad_origen,
        ciudad_destino: route.ciudad_destino,
        peso_mercancia: route.peso_mercancia,
        pricings: pricings[index] || [],
      })),
    };
    console.log('[PricingModal] Sending AI payload', payload);   // <--- add here

    const { data } = await fetchVehicleSuggestions(payload);

    setVehicleSuggestions(data.suggestions || {});
    setSuggestionsKey(currentKey);

    autoSelectFromSuggestions(data.suggestions || {});
  } catch (error) {
    console.error('[PricingModal] Error getting AI suggestions:', {
      error,
      response: error?.response?.data,
    }); // <--- existing log, keep it
  } finally {
    setLoadingSuggestions(false);
  }
};



const autoSelectFromSuggestions = (suggestions) => {
  console.log('[PricingModal] Auto-select suggestions:', suggestions);
  Object.entries(suggestions).forEach(([routeIndex, suggestion]) => {
    const pricingForRoute = (pricings[routeIndex] || []).find(
      p => p.vehicle_type === suggestion.vehicle_type
    );

    if (!pricingForRoute) {
      console.warn(`[PricingModal] No pricing option matches AI suggestion for route ${routeIndex}`, suggestion, pricings[routeIndex]);
      return;
    }

    console.log(`[PricingModal] Auto-selecting route ${routeIndex}`, pricingForRoute);
    handleVehicleSelect(Number(routeIndex), pricingForRoute.id);
  });
};

  const handleVehicleSelect = (routeIndex, pricingId) => {
    console.log('[PricingModal] handleVehicleSelect called', { routeIndex, pricingId });

    const targetId = String(pricingId);
    const selectedPricing = (pricings[routeIndex] || []).find(
      p => String(p.id) === targetId
    );

    if (!selectedPricing) {
      console.warn('[PricingModal] Selected pricing not found', { routeIndex, pricingId, pricings: pricings[routeIndex] });
      return;
    }

    console.log('[PricingModal] Selected pricing object:', selectedPricing);

    setSelectedPricings(prev => ({
      ...prev,
      [routeIndex]: selectedPricing
    }));

    setQuoteData(prev => prev.map((route, index) => 
      index === routeIndex 
        ? { ...route, select_value: targetId, vehiculo_requerido: selectedPricing.vehicle_type }
        : route
    ));
  };

  const handleParameterChange = (routeIndex, parameterName, value) => {
    setQuoteData(prev => prev.map((route, index) => 
      index === routeIndex 
        ? { ...route, [parameterName]: parseFloat(value) || 0 }
        : route
    ));
  };

  const getAutomaticParameters = (route) => {
    const parameters = [];
    
    // Parámetros por tipo de modalidad
    if (clientData.typeBusiness === 'dta' || clientData.typeBusiness === 'otm') {
      parameters.push({
        name: 'candado_satelital',
        label: 'Candado Satelital',
        required: true,
        color: 'blue'
      });
    }
    
    // Parámetros por tipo de carga
    if (clientData.cargoType === 'refrigerado') {
      parameters.push(
        {
          name: 'jen_set',
          label: 'Jen set',
          required: true,
          color: 'green'
        },
        {
          name: 'combustible',
          label: 'Combustible',
          required: true,
          color: 'green'
        }
      );
    }
    
    if (clientData.cargoType === 'dangerous') {
      parameters.push(
        {
          name: 'kit_derrames',
          label: 'Kit de derrames',
          required: true,
          color: 'red'
        },
        {
          name: 'pictogramas',
          label: 'Pictogramas',
          required: true,
          color: 'red'
        }
      );
    }
    
    return parameters;
  };

  const handlePorcentajeChange = (routeIndex, value) => {
    const porcentaje = parseFloat(value) || 0;
    
    if (porcentaje < 17 && porcentaje > 0) {
      setErrors(prev => ({
        ...prev,
        [`porcentaje_${routeIndex}`]: 'No se puede ingresar una rentabilidad menor al 17%'
      }));
      return;
    }
    
    setErrors(prev => ({
      ...prev,
      [`porcentaje_${routeIndex}`]: null
    }));
    
    setQuoteData(prev => prev.map((route, index) => 
      index === routeIndex ? { ...route, porcentaje: porcentaje } : route
    ));
  };

  const applyGlobalPorcentaje = (porcentaje) => {
    setPorcentajeGlobal(porcentaje);
    setQuoteData(prev => prev.map(route => ({ ...route, porcentaje })));
  };

  const resetGlobalPorcentaje = () => {
    setPorcentajeGlobal(null);
  };

  const calculateFinalValue = (routeIndex) => {
    const route = quoteData[routeIndex];
    const pricing = selectedPricings[routeIndex];

    if (!pricing || !route) return 0;

    const basePrice = Number(pricing.price) || 0;  // <-- force number
    const porcentaje = Number(route.porcentaje) || 0;
    const acompanamiento = Number(route.itesoltra_acompanamientovalor) || 0;

    let parametersTotal = 0;
    const parameters = getAutomaticParameters(route);
    parameters.forEach(param => {
      parametersTotal += Number(route[param.name]) || 0;
    });

    const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
    return valueWithMargin + acompanamiento + parametersTotal;
  };

  const canContinue = () => {
    // Verificar que todas las rutas tengan vehículo seleccionado
    const hasAllVehicles = quoteData.every(route => route.select_value);
    
    // Verificar que todos los porcentajes sean >= 17
    const hasValidPercentages = quoteData.every(route => 
      (route.porcentaje || 0) >= 17
    );
    
    return hasAllVehicles && hasValidPercentages;
  };

  const handleContinue = () => {
    if (canContinue()) {
      // Calcular y guardar los valores finales antes de continuar
      const updatedQuoteData = quoteData.map((route, index) => {
        const finalValue = calculateFinalValue(index);
        console.log(`PricingModal - Ruta ${index + 1}:`, {
          basePrice: selectedPricings[index]?.price,
          porcentaje: route.porcentaje,
          finalValue: finalValue
        });
        return {
          ...route,
          finalValue: finalValue
        };
      });
      
      // Actualizar el estado con los valores finales
      setQuoteData(updatedQuoteData);
      
      // Pequeño delay para asegurar que el estado se actualice antes de continuar
      setTimeout(() => {
        onNext();
      }, 100);
    } else {
      alert('Completa todos los campos requeridos antes de continuar.');
    }
  };

  return (
    <Modal onClose={onClose} size="full-screen">
      <style jsx>{`
        @import url('https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap');
        .product-sans {
          font-family: 'Product Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
      `}</style>

      <div className="relative bg-white border-b border-gray-200 px-8 py-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <h2 className="text-xl font-600 text-gray-700 product-sans">Configuración de Pricing</h2>
          </div>
        </div>
      </div>
     
      <div className="relative p-8 bg-gray-50">
        {showBlockingSpinner && (
            <div className="absolute inset-0 z-20 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center space-y-3 text-orange-500">
              <FaSpinner className="animate-spin text-3xl" />
              <p className="text-sm font-semibold">
                {loadingPricings ? 'Cargando tarifas...' : 'Calculando sugerencias IA...'}
              </p>
            </div>
          )}
        {/* Header de información */}
        <div className="mb-8">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <div className="flex items-center space-x-2 mb-2">
                  <div className="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                  <span className="text-sm font-500 text-orange-500 product-sans">Configurando Pricing</span>
                </div>
                <h3 className="text-lg font-600 text-gray-700 product-sans mb-1">
                  {clientData.clientName || clientData.search || 'Creación de cotización'}
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
                          <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          {clientData.clientLocation}
                        </p>
                      )}
                      {clientData.clientEmail && (
                        <p className="text-xs text-gray-500 product-sans flex items-center">
                          <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                          </svg>
                          {clientData.clientEmail}
                        </p>
                      )}
                      {clientData.clientSalesRepresentative && (
                        <p className="text-xs text-gray-500 product-sans flex items-center">
                          <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                          </svg>
                          Representante: {clientData.clientSalesRepresentative}
                        </p>
                      )}
                    </>
                  ) : (
                    <p className="text-sm font-500 text-gray-500 product-sans">
                      {clientData.documentClient || '-'} | <span className="font-bold uppercase">{clientData.clientName || 'Cliente nuevo'}</span>
                    </p>
                  )}
                </div>
                <p className="text-xs text-gray-400 product-sans mt-2">
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
        </div>

        {/* Layout principal */}
        <div className="flex flex-row gap-8 h-[650px]">
          {/* Tabla de rutas y precios - mayor espacio */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full w-3/5">
            <div className="bg-gray-50 px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <h4 className="text-base font-600 text-gray-700 product-sans">Configuración de Rutas y Precios</h4>
            </div>
            <div className="overflow-x-auto flex-1 min-h-0">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-gray-700 text-sm font-600 border-b border-gray-200 bg-gray-50">
                    <th className="text-left px-4 py-3 product-sans min-w-[120px]">Origen</th>
                    <th className="text-left px-4 py-3 product-sans min-w-[120px]">Destino</th>
                    <th className="text-left px-4 py-3 product-sans min-w-[140px]">Vehículo</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[110px]">Precio Base</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[120px]">Parámetros</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[100px]">Rent.(%)</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[120px]">Valor Cliente</th>
                  </tr>
                </thead>
                <tbody>
                  {quoteData.map((route, index) => {
                    const automaticParameters = getAutomaticParameters(route);
                    
                    return (
                      <tr key={index} className="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                        <td className="px-4 py-3 text-sm font-500 text-gray-700 product-sans">
                          {route.ciudad_origen || '-'}
                        </td>
                        <td className="px-4 py-3 text-sm font-500 text-gray-700 product-sans">
                          {route.ciudad_destino || '-'}
                        </td>
                        <td className="px-4 py-3">
                          {/* Sugerencia de la IA */}
                          {vehicleSuggestions[index] && (
                            <div className="text-xs mb-2 rounded bg-blue-50 text-blue-600 px-2 py-1">
                              IA sugiere: <strong>{vehicleSuggestions[index].vehicle_type}</strong>
                              <span className="text-gray-500"> — {vehicleSuggestions[index].reason}</span>
                            </div>
                          )}
                          
                          {/* Selector de vehículo */}
                          {/* <select 
                            value={route.select_value || ''}
                            onChange={(e) => handleVehicleSelect(index, e.target.value)}
                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg h-10 focus:outline-none focus:ring-2 focus:ring-orange-400"
                          >
                            <option value="">Selecciona vehículo</option>
                            {(pricings[index] || []).map(pricing => (
                              <option key={pricing.id} value={pricing.id}>
                                {pricing.vehicle_type}
                              </option>
                            ))}
                          </select> */}
                          <select
                            value={route.select_value || ''}
                            onChange={(e) => handleVehicleSelect(index, e.target.value)}
                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg h-10 focus:outline-none focus:ring-2 focus:ring-orange-400"
                          >
                            <option value="">Selecciona vehículo</option>
                            {(pricings[index] || []).map(pricing => (
                              <option key={pricing.id} value={pricing.id}>
                                {pricing.vehicle_type} - ${Number(pricing.price).toLocaleString()}
                              </option>
                            ))}
                          </select>
                        </td>
                        <td className="px-4 py-3 text-center">
                          <span className="text-sm font-600 text-gray-700 product-sans">
                            ${selectedPricings[index] ? Number(selectedPricings[index].price).toLocaleString() : '0'}
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          {/* Parámetros automáticos */}
                          {automaticParameters.length > 0 ? (
                            <div className="space-y-3">
                              {automaticParameters.map((param) => (
                                <div key={param.name} className="flex flex-col items-center">
                                  <label className={`text-xs font-medium mb-1 text-${param.color}-600 product-sans text-center`}>
                                    {param.label}
                                  </label>
                                  <input
                                    type="number"
                                    value={route[param.name] || ''}
                                    onChange={(e) => handleParameterChange(index, param.name, e.target.value)}
                                    className={`w-24 px-3 py-2 text-sm text-center border border-${param.color}-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-${param.color}-400 product-sans`}
                                    placeholder="$"
                                    min="0"
                                  />
                                </div>
                              ))}
                            </div>
                          ) : (
                            <div className="text-sm text-gray-400 text-center">-</div>
                          )}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex flex-col items-center space-y-2">
                            <input 
                              type="number"
                              value={route.porcentaje || ''}
                              onChange={(e) => handlePorcentajeChange(index, e.target.value)}
                              className={`w-24 px-3 py-2 text-sm text-center border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent product-sans bg-white font-medium h-10 ${
                                errors[`porcentaje_${index}`] ? 'border-red-500 bg-red-50' : 'border-gray-300'
                              }`}
                              placeholder="%" 
                              min="17" 
                              max="100" 
                            />
                            {errors[`porcentaje_${index}`] && (
                              <span className="text-red-500 text-xs text-center product-sans">
                                Min 17%
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-center">
                          <span className="text-sm font-700 text-orange-600">
                            ${Number(calculateFinalValue(index)).toLocaleString()}
                          </span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Tarjetas de rentabilidad */}
          <div className="flex flex-row justify-between h-full space-x-4 w-2/5">
            <RentabilityCard
              title="PROPUESTA #1"
              subtitle={`RENTABILIDAD MÍNIMA (${rentabilityDefaults.scope === 'route' ? 'Ruta' : 'Global'})`}
              percentage={rentabilityDefaults.min}
              isActive={porcentajeGlobal === rentabilityDefaults.min}
              onSelect={() => applyGlobalPorcentaje(rentabilityDefaults.min)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
              clientData={clientData}
            />

            <RentabilityCard
              title="PROPUESTA #2"
              subtitle="RENTABILIDAD PROMEDIO"
              percentage={rentabilityDefaults.avg}
              isActive={porcentajeGlobal === rentabilityDefaults.avg}
              onSelect={() => applyGlobalPorcentaje(rentabilityDefaults.avg)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
              clientData={clientData}
            />

            <RentabilityCard
              title="PROPUESTA #3"
              subtitle="RENTABILIDAD MÁXIMA"
              percentage={rentabilityDefaults.max}
              isActive={porcentajeGlobal === rentabilityDefaults.max}
              onSelect={() => applyGlobalPorcentaje(rentabilityDefaults.max)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
              clientData={clientData}
            />
          </div>
        </div>

        {/* Botón de continuar */}
        <div className="flex justify-center mt-8">
          <button 
            onClick={handleContinue}
            disabled={!canContinue()}
            className={`px-8 py-4 text-white font-600 rounded-xl shadow-lg transition-all duration-300 product-sans group min-w-[200px] ${
              canContinue() 
                ? 'bg-orange-400 hover:bg-orange-500 hover:shadow-xl transform hover:-translate-y-1' 
                : 'bg-gray-300 cursor-not-allowed'
            }`}
          >
            <div className="flex items-center justify-center space-x-2">
              <span className="text-base">
                {canContinue() ? 'Continuar' : 'Completa la configuración'}
              </span>
              {canContinue() && (
                <svg className="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
              )}
            </div>
          </button>
        </div>
      </div>
    </Modal>
  );
};

const RentabilityCard = ({ title, subtitle, percentage, isActive, onSelect, quoteData, selectedPricings, clientData }) => {
  const calculateTotal = () => {
    return Object.keys(selectedPricings).reduce((total, index) => {
      const pricing = selectedPricings[index];
      const route = quoteData[index];

      if (pricing && route) {
        const basePrice = Number(pricing.price) || 0;
        const withMargin = basePrice + (basePrice * percentage / 100);

        let parametersTotal = 0;
        const parameters = getAutomaticParameters();
        parameters.forEach(param => {
          parametersTotal += Number(route[param.name]) || 0;
        });

        return total + withMargin + parametersTotal;
      }

      return total;
    }, 0);
  };

  const getAutomaticParameters = () => {
    const parameters = [];
    
    // Parámetros por tipo de modalidad
    if (clientData.typeBusiness === 'dta' || clientData.typeBusiness === 'otm') {
      parameters.push({
        name: 'candado_satelital',
        label: 'Candado Satelital',
        required: true,
        color: 'blue'
      });
    }
    
    if (clientData.cargoType === 'refrigerado') {
      parameters.push(
        {
          name: 'jen_set',
          label: 'Jen set',
          required: true,
          color: 'green'
        },
        {
          name: 'combustible',
          label: 'Combustible',
          required: true,
          color: 'green'
        }
      );
    }
    
    if (clientData.cargoType === 'dangerous') {
      parameters.push(
        {
          name: 'kit_derrames',
          label: 'Kit de derrames',
          required: true,
          color: 'red'
        },
        {
          name: 'pictogramas',
          label: 'Pictogramas',
          required: true,
          color: 'red'
        }
      );
    }
    
    return parameters;
  };

  return (
    <div className={`bg-white rounded-lg border-2 transition-all duration-300 overflow-hidden w-1/3 h-full ${
      isActive ? 'border-orange-400 shadow-md shadow-orange-100' : 'border-gray-200'
    }`}>
      <div className="p-3 h-full flex flex-col items-center justify-center">
        <div className="flex flex-col items-center justify-center mb-2 w-full">
          <div className="flex flex-col items-center justify-center space-y-1 w-full">
            <div className="text-xs font-500 text-gray-400 product-sans text-center">{title}</div>
            <div className="text-xs font-600 text-gray-700 product-sans text-center">{subtitle}</div>
            <div className={`text-xl font-700 product-sans text-center ${isActive ? 'text-orange-500' : 'text-gray-900'}`}>
              {percentage}%
            </div>
            <label className="relative inline-flex items-center cursor-pointer justify-center mt-2" onClick={onSelect}>
              <input 
                type="radio" 
                checked={isActive}
                onChange={() => {}} // Controlado por onSelect
                className="sr-only peer" 
              />
              <div className="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-400"></div>
            </label>
          </div>
        </div>
        
        <div className="border-t border-gray-200 pt-2 flex-1 w-full flex flex-col items-center justify-center">
          {/* Lista de precios por ruta */}
          <div className="flex flex-col space-y-3 mb-2 w-full items-center justify-center">
            {quoteData.map((route, index) => {
              const pricing = selectedPricings[index];
              let finalRoutePrice = 0;
              
              if (pricing) {
                const basePrice = Number(pricing.price) || 0;
                const withMargin = basePrice + (basePrice * percentage / 100);

                let parametersTotal = 0;
                const parameters = getAutomaticParameters();
                parameters.forEach(param => {
                  parametersTotal += Number(route[param.name]) || 0;
                });

                finalRoutePrice = withMargin + parametersTotal;
              }
              
              return (
                <div key={index} className="flex flex-col items-center w-full">
                  <div className="text-xs text-gray-500 product-sans mb-1 text-center">
                    {(route.ciudad_origen || '').substring(0, 3)}-{(route.ciudad_destino || '').substring(0, 3)}
                  </div>
                  <div className="text-xs font-600 text-orange-600 product-sans text-center">
                    ${Number(finalRoutePrice).toLocaleString()}
                  </div>
                </div>
              );
            })}
          </div>
          
          <div className="border-t border-gray-200 pt-2 mt-auto w-full flex flex-col items-center justify-center">
            <span className="text-xs font-500 text-gray-600 product-sans text-center">VALOR FINAL</span>
            <span className="text-sm font-700 text-orange-600 product-sans text-center">
              ${Number(calculateTotal()).toLocaleString()}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

PricingModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  onNext: PropTypes.func.isRequired,
  quoteData: PropTypes.array.isRequired,
  setQuoteData: PropTypes.func.isRequired,
  pricings: PropTypes.array.isRequired,
  setPricings: PropTypes.func.isRequired,
  selectedPricings: PropTypes.object.isRequired,
  setSelectedPricings: PropTypes.func.isRequired,
  porcentajeGlobal: PropTypes.number,
  setPorcentajeGlobal: PropTypes.func.isRequired,
  clientData: PropTypes.object.isRequired
};

RentabilityCard.propTypes = {
  title: PropTypes.string.isRequired,
  subtitle: PropTypes.string.isRequired,
  percentage: PropTypes.number.isRequired,
  isActive: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired,
  quoteData: PropTypes.array.isRequired,
  selectedPricings: PropTypes.object.isRequired,
  clientData: PropTypes.object.isRequired
};

export default PricingModal;