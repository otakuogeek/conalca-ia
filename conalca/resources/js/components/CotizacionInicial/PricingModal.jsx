// resources/js/components/CotizacionInicial/PricingModal.jsx
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';

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
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [vehicleSuggestions, setVehicleSuggestions] = useState({});

  useEffect(() => {
    // Cargar pricings para cada ruta
    loadPricingsForRoutes();
    generateVehicleSuggestions();
  }, [quoteData]);

  const loadPricingsForRoutes = async () => {
    setLoading(true);
    try {
      // Simular carga de pricings - aquí iría la llamada real a la API
      const mockPricings = quoteData.map((route, index) => [
        {
          id: `${index}-1`,
          vehicle_type: 'Sencillo',
          price: 350000 + (index * 50000)
        },
        {
          id: `${index}-2`, 
          vehicle_type: 'Turbo',
          price: 450000 + (index * 60000)
        },
        {
          id: `${index}-3`,
          vehicle_type: 'Dobletroque',
          price: 650000 + (index * 80000)
        }
      ]);
      
      setPricings(mockPricings);
    } catch (error) {
      console.error('Error loading pricings:', error);
    } finally {
      setLoading(false);
    }
  };

  const generateVehicleSuggestions = () => {
    // Generar sugerencias de vehículos basadas en el tipo de carga y peso
    const suggestions = {};
    quoteData.forEach((route, index) => {
      const peso = parseInt(route.peso_mercancia) || 0;
      let vehicle = 'Sencillo';
      let bodywork = 'Furgón';
      
      if (peso > 10000) {
        vehicle = 'Dobletroque';
        bodywork = 'Estacas';
      } else if (peso > 5000) {
        vehicle = 'Turbo';
        bodywork = 'Carpa';
      }
      
      suggestions[index] = { vehicle, bodywork };
    });
    
    setVehicleSuggestions(suggestions);
  };

  const handleVehicleSelect = (routeIndex, pricingId) => {
    const selectedPricing = pricings[routeIndex]?.find(p => p.id === pricingId);
    if (selectedPricing) {
      setSelectedPricings(prev => ({
        ...prev,
        [routeIndex]: selectedPricing
      }));
      
      // Actualizar datos de la ruta
      setQuoteData(prev => prev.map((route, index) => 
        index === routeIndex 
          ? { ...route, select_value: pricingId, vehiculo_requerido: selectedPricing.vehicle_type }
          : route
      ));
    }
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
    
    const basePrice = pricing.price;
    const porcentaje = route.porcentaje || 0;
    const acompanamiento = parseFloat(route.itesoltra_acompanamientovalor) || 0;
    
    const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
    return valueWithMargin + acompanamiento;
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
      onNext();
    } else {
      alert('Completa todos los campos requeridos antes de continuar.');
    }
  };

  return (
    <Modal onClose={onClose} size="extra-large">
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

      <div className="p-8 bg-gray-50">
        {/* Header de información */}
        <div className="mb-8">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <div className="flex items-center space-x-2 mb-2">
                  <div className="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                  <span className="text-sm font-500 text-orange-500 product-sans">Configurando Pricing</span>
                </div>
                <h3 className="text-lg font-600 text-gray-700 product-sans mb-1">Creación de cotización</h3>
                <p className="text-sm font-500 text-gray-500 product-sans">
                  {clientData.documentClient || '-'} | <span className="font-bold uppercase">{clientData.clientName || '-'}</span>
                </p>
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
        </div>

        {/* Layout principal */}
        <div className="flex flex-row gap-6 h-[580px]">
          {/* Tabla de rutas y precios */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full w-2/4">
            <div className="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
              <h4 className="text-sm font-600 text-gray-700 product-sans">Configuración de Rutas y Precios</h4>
            </div>
            <div className="overflow-x-auto flex-1 min-h-0">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-gray-700 text-xs font-600 border-b border-gray-200 bg-gray-50">
                    <th className="text-left px-3 py-2 product-sans">Origen</th>
                    <th className="text-left px-3 py-2 product-sans">Destino</th>
                    <th className="text-left px-3 py-2 product-sans">Vehículo</th>
                    <th className="text-center px-3 py-2 product-sans">Precio Base</th>
                    <th className="text-center px-3 py-2 product-sans">Rent.(%)</th>
                    <th className="text-center px-3 py-2 product-sans">Valor Cliente</th>
                  </tr>
                </thead>
                <tbody>
                  {quoteData.map((route, index) => (
                    <tr key={index} className="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                      <td className="px-3 py-2 text-xs font-500 text-gray-700 product-sans">
                        {route.ciudad_origen || '-'}
                      </td>
                      <td className="px-3 py-2 text-xs font-500 text-gray-700 product-sans">
                        {route.ciudad_destino || '-'}
                      </td>
                      <td className="px-3 py-2">
                        {/* Sugerencia de la IA */}
                        {vehicleSuggestions[index] && (
                          <div className="text-[10px] mb-1 rounded bg-blue-50 text-blue-600 px-1.5 py-0.5">
                            IA sugiere: <strong>{vehicleSuggestions[index].vehicle}</strong>
                            <span className="text-gray-500"> / {vehicleSuggestions[index].bodywork}</span>
                          </div>
                        )}
                        
                        {/* Selector de vehículo */}
                        <select 
                          value={route.select_value || ''}
                          onChange={(e) => handleVehicleSelect(index, e.target.value)}
                          className="w-full px-2 py-2 text-xs border border-gray-300 rounded h-10 focus:outline-none focus:ring-1 focus:ring-orange-400"
                        >
                          <option value="">Selecciona vehículo</option>
                          {(pricings[index] || []).map(pricing => (
                            <option key={pricing.id} value={pricing.id}>
                              {pricing.vehicle_type}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="px-3 py-2 text-center">
                        <span className="text-xs font-600 text-gray-700 product-sans">
                          ${selectedPricings[index] ? Number(selectedPricings[index].price).toLocaleString() : '0'}
                        </span>
                      </td>
                      <td className="px-3 py-2">
                        <div className="flex flex-col items-center space-y-1">
                          <input 
                            type="number"
                            value={route.porcentaje || ''}
                            onChange={(e) => handlePorcentajeChange(index, e.target.value)}
                            className={`w-20 px-3 py-2 text-sm text-center border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent product-sans bg-white font-medium h-10 ${
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
                      <td className="px-3 py-2 text-center">
                        <span className="text-xs font-700 text-orange-600">
                          ${Number(calculateFinalValue(index)).toLocaleString()}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Tarjetas de rentabilidad */}
          <div className="flex flex-row justify-between h-full space-x-3 w-2/4">
            {/* Propuesta 1: 17% */}
            <RentabilityCard
              title="PROPUESTA #1"
              subtitle="RENTABILIDAD MÍNIMA"
              percentage={17}
              isActive={porcentajeGlobal === 17}
              onSelect={() => applyGlobalPorcentaje(17)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
            />

            {/* Propuesta 2: 24% */}
            <RentabilityCard
              title="PROPUESTA #2"
              subtitle="RENTABILIDAD PROMEDIO"
              percentage={24}
              isActive={porcentajeGlobal === 24}
              onSelect={() => applyGlobalPorcentaje(24)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
            />

            {/* Propuesta 3: 32% */}
            <RentabilityCard
              title="PROPUESTA #3"
              subtitle="RENTABILIDAD MAYOR VENDIDA"
              percentage={32}
              isActive={porcentajeGlobal === 32}
              onSelect={() => applyGlobalPorcentaje(32)}
              quoteData={quoteData}
              selectedPricings={selectedPricings}
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

const RentabilityCard = ({ title, subtitle, percentage, isActive, onSelect, quoteData, selectedPricings }) => {
  const calculateTotal = () => {
    return Object.keys(selectedPricings).reduce((total, index) => {
      const pricing = selectedPricings[index];
      if (pricing) {
        const basePrice = pricing.price;
        const withMargin = basePrice + (basePrice * percentage / 100);
        return total + withMargin;
      }
      return total;
    }, 0);
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
            {quoteData.map((route, index) => (
              <div key={index} className="flex flex-col items-center w-full">
                <div className="text-xs text-gray-500 product-sans mb-1 text-center">
                  {(route.ciudad_origen || '').substring(0, 3)}-{(route.ciudad_destino || '').substring(0, 3)}
                </div>
                <div className="text-xs font-600 text-orange-600 product-sans text-center">
                  ${selectedPricings[index] 
                    ? Number(selectedPricings[index].price + selectedPricings[index].price * percentage / 100).toLocaleString()
                    : '0'
                  }
                </div>
              </div>
            ))}
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
  selectedPricings: PropTypes.object.isRequired
};

export default PricingModal;