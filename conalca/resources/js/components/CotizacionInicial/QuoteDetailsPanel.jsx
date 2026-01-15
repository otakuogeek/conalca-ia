import React from 'react';

/**
 * Panel de detalles de cotización - Cards individuales por ruta con selector
 * Muestra los datos extraídos del chat en tiempo real sin polling
 * Permite seleccionar rutas individuales para edición vía chat
 */
const QuoteDetailsPanel = ({ 
  routes = [], 
  selectedProduct = null, 
  selectedEmpaque = null,
  isLoading = false,
  onCreateQuote = null,
  canCreate = false,
  selectedRouteIndex = null,  // 🆕 Índice de ruta seleccionada para edición
  onSelectRoute = null,       // 🆕 Callback cuando se selecciona una ruta
  isCreating = false          // 🔒 Indica si se está guardando la cotización
}) => {
  
  // 🆕 Función para detectar campos faltantes críticos
  const getMissingFields = (route) => {
    const missing = [];
    
    if (!route.ciudadOrigen && !route.ciudad_origen) missing.push('origen');
    if (!route.ciudadDestino && !route.ciudad_destino) missing.push('destino');
    if (!route.pesoMercancia && !route.peso_mercancia) missing.push('peso');
    if (!route.producto && !route.tipo_producto && !selectedProduct) missing.push('producto');
    
    return missing;
  };

  // 🆕 Función para obtener mensaje de campo faltante
  const getMissingFieldLabel = (field) => {
    const labels = {
      'origen': 'Origen',
      'destino': 'Destino',
      'peso': 'Peso',
      'producto': 'Producto'
    };
    return labels[field] || field;
  };
  
  // Debug: verificar qué llega
  console.log('🔍 QuoteDetailsPanel recibió:', {
    routes,
    routesLength: routes?.length,
    isArray: Array.isArray(routes),
    selectedProduct,
    selectedProductNombre: selectedProduct?.nombre,
    selectedEmpaque,
    isLoading,
    canCreate
  });
  
  if (isLoading) {
    return (
      <div className="space-y-4">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h4 className="text-base font-semibold text-white">Detalles de la Cotización</h4>
          </div>
          <div className="p-6">
            <div className="flex items-center justify-center space-x-2">
              <div className="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
              <div className="w-2 h-2 bg-orange-500 rounded-full animate-pulse" style={{animationDelay: '0.2s'}}></div>
              <div className="w-2 h-2 bg-orange-500 rounded-full animate-pulse" style={{animationDelay: '0.4s'}}></div>
            </div>
            <p className="text-sm text-gray-500 text-center mt-2">Extrayendo datos de cotización...</p>
          </div>
        </div>
      </div>
    );
  }

  if (!routes || routes.length === 0) {
    return (
      <div className="space-y-4">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h4 className="text-base font-semibold text-white">Detalles de la Cotización</h4>
          </div>
          <div className="p-6 text-center">
            <svg className="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p className="text-sm text-gray-500">Comparte los detalles de tu envío para comenzar</p>
          </div>
        </div>
      </div>
    );
  }

  // Calcular resumen total
  const totalRoutes = routes.length;
  const totalPeso = routes.reduce((sum, r) => sum + (parseFloat(r.pesoMercancia) || 0), 0);
  const totalValor = routes.reduce((sum, r) => sum + (parseFloat(r.valorMercancia) || 0), 0);

  return (
    <div className="space-y-4">
      {/* Header con resumen cuando hay múltiples rutas */}
      {totalRoutes > 1 && (
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-4 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className="bg-white/20 rounded-full p-2">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
              </div>
              <div>
                <h3 className="text-lg font-bold">Cotización Multi-Ruta</h3>
                <p className="text-blue-100 text-sm">
                  {totalRoutes} rutas detectadas
                  {selectedRouteIndex !== null && (
                    <span className="ml-2 bg-yellow-400 text-yellow-900 px-2 py-0.5 rounded-full text-xs font-bold">
                      ✏️ Editando Ruta {selectedRouteIndex + 1}
                    </span>
                  )}
                </p>
              </div>
            </div>
            <div className="text-right">
              <p className="text-xs text-blue-200">Peso Total</p>
              <p className="text-lg font-bold">{totalPeso.toLocaleString('es-CO')} kg</p>
            </div>
          </div>
          {totalValor > 0 && (
            <div className="mt-3 pt-3 border-t border-white/20 text-center">
              <p className="text-xs text-blue-200">Valor Total Declarado</p>
              <p className="text-xl font-bold">${totalValor.toLocaleString('es-CO')}</p>
            </div>
          )}
        </div>
      )}

      {/* Cards individuales para cada ruta */}
      {routes.map((route, index) => {
        // Resolver producto: priorizar route específico, luego selectedProduct
        const productoMostrar = route.producto || route.tipo_producto || selectedProduct?.nombre || null;
        const productoValido = !!productoMostrar;
        
        // Resolver empaque: priorizar route específico, luego selectedEmpaque
        const empaqueMostrar = route.empaque || route.tipo_embalaje || selectedEmpaque?.nome || selectedEmpaque?.nombre || null;
        
        // 🆕 Verificar si esta ruta está seleccionada para edición
        const isSelected = selectedRouteIndex === index;
        
        console.log(`📋 Ruta ${index + 1} - Datos resueltos:`, {
          productoMostrar,
          productoValido,
          empaqueMostrar,
          isSelected,
          route
        });
        
        // Colores para diferentes rutas
        const routeColors = [
          { bg: 'from-orange-500 to-orange-600', badge: 'bg-orange-100 text-orange-700', icon: 'text-orange-500', border: 'border-orange-500' },
          { bg: 'from-emerald-500 to-emerald-600', badge: 'bg-emerald-100 text-emerald-700', icon: 'text-emerald-500', border: 'border-emerald-500' },
          { bg: 'from-purple-500 to-purple-600', badge: 'bg-purple-100 text-purple-700', icon: 'text-purple-500', border: 'border-purple-500' },
          { bg: 'from-blue-500 to-blue-600', badge: 'bg-blue-100 text-blue-700', icon: 'text-blue-500', border: 'border-blue-500' },
          { bg: 'from-pink-500 to-pink-600', badge: 'bg-pink-100 text-pink-700', icon: 'text-pink-500', border: 'border-pink-500' },
        ];
        const colorScheme = routeColors[index % routeColors.length];
        
        return (
          <div 
            key={index} 
            className={`bg-white rounded-xl shadow-sm overflow-hidden transition-all duration-200 cursor-pointer
              ${isSelected 
                ? `ring-2 ring-offset-2 ${colorScheme.border} ring-current shadow-lg border-2 ${colorScheme.border}` 
                : 'border border-gray-200 hover:shadow-md'
              }`}
            onClick={() => onSelectRoute && onSelectRoute(isSelected ? null : index)}
          >
            {/* Header de la ruta con color */}
            <div className={`bg-gradient-to-r ${colorScheme.bg} px-5 py-3`}>
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3 flex-1">
                  {/* 🆕 Checkbox de selección */}
                  <div 
                    className={`w-6 h-6 rounded-full flex items-center justify-center transition-all duration-200 flex-shrink-0
                      ${isSelected 
                        ? 'bg-white text-green-600' 
                        : 'bg-white/20 text-white hover:bg-white/40'
                      }`}
                    onClick={(e) => {
                      e.stopPropagation();
                      onSelectRoute && onSelectRoute(isSelected ? null : index);
                    }}
                  >
                    {isSelected ? (
                      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                      </svg>
                    ) : (
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    )}
                  </div>
                  <span className="text-white font-bold text-base">
                    Ruta {index + 1}
                    {isSelected && <span className="ml-2 text-xs font-normal bg-white/30 px-2 py-0.5 rounded-full">Editando</span>}
                  </span>
                </div>
                <div className="flex items-center space-x-2">
                  {/* 🆕 Indicador de campos faltantes */}
                  {getMissingFields(route).length > 0 && (
                    <div className="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full flex items-center space-x-1">
                      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                      </svg>
                      <span>{getMissingFields(route).length} campos</span>
                    </div>
                  )}
                  <span className="bg-white/20 text-white text-xs font-medium px-2 py-1 rounded-full">
                    {(route.pesoMercancia || route.peso_mercancia) ? `${parseFloat(route.pesoMercancia || route.peso_mercancia).toLocaleString('es-CO')} kg` : 'Sin peso'}
                  </span>
                </div>
              </div>
            </div>

            {/* Origen y Destino destacados */}
            <div className={`px-5 py-4 bg-gray-50 border-b border-gray-100 ${
              getMissingFields(route).includes('origen') || getMissingFields(route).includes('destino')
                ? 'ring-1 ring-yellow-300 bg-yellow-50'
                : ''
            }`}>
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3 flex-1">
                  <div className={`w-3 h-3 rounded-full ${
                    route.ciudadOrigen || route.ciudad_origen ? colorScheme.icon : 'text-red-500'
                  } bg-current`}></div>
                  <div className="flex-1">
                    <p className="text-xs text-gray-500 uppercase tracking-wide">Origen</p>
                    <p className={`text-sm font-bold ${
                      route.ciudadOrigen || route.ciudad_origen ? 'text-gray-900' : 'text-red-500'
                    }`}>
                      {route.ciudadOrigen || route.ciudad_origen || '❌ Faltante'}
                    </p>
                  </div>
                </div>
                <div className="flex items-center px-4">
                  <svg className={`w-6 h-6 ${colorScheme.icon}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                  </svg>
                </div>
                <div className="flex items-center space-x-3 flex-1 justify-end text-right">
                  <div className="flex-1">
                    <p className="text-xs text-gray-500 uppercase tracking-wide">Destino</p>
                    <p className={`text-sm font-bold ${
                      route.ciudadDestino || route.ciudad_destino ? 'text-gray-900' : 'text-red-500'
                    }`}>
                      {route.ciudadDestino || route.ciudad_destino || '❌ Faltante'}
                    </p>
                  </div>
                  <div className={`w-3 h-3 rounded-full ${
                    route.ciudadDestino || route.ciudad_destino ? colorScheme.icon : 'text-red-500'
                  } bg-current`}></div>
                </div>
              </div>
            </div>

            {/* Detalles de la mercancía */}
            <div className="p-5">
              <div className="grid grid-cols-2 gap-4">
                {/* Peso */}
                <div className={`rounded-lg p-3 ${
                  getMissingFields(route).includes('peso') 
                    ? 'bg-red-50 ring-1 ring-red-300' 
                    : 'bg-gray-50'
                }`}>
                  <div className="flex items-center space-x-2 mb-1">
                    <svg className={`w-4 h-4 ${
                      getMissingFields(route).includes('peso') ? 'text-red-400' : 'text-gray-400'
                    }`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                    </svg>
                    <span className={`text-xs uppercase ${
                      getMissingFields(route).includes('peso') ? 'text-red-600 font-bold' : 'text-gray-500'
                    }`}>Peso</span>
                  </div>
                  <p className={`text-lg font-bold ${
                    (route.pesoMercancia || route.peso_mercancia) ? 'text-gray-900' : 'text-red-500'
                  }`}>
                    {(route.pesoMercancia || route.peso_mercancia) ? `${parseFloat(route.pesoMercancia || route.peso_mercancia).toLocaleString('es-CO')} kg` : '❌ Faltante'}
                  </p>
                </div>

                {/* Cantidad */}
                <div className="bg-gray-50 rounded-lg p-3">
                  <div className="flex items-center space-x-2 mb-1">
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span className="text-xs text-gray-500 uppercase">Cantidad</span>
                  </div>
                  <p className="text-lg font-bold text-gray-900">
                    {route.cantidadMercancia || route.cantidad || '-'}
                  </p>
                </div>

                {/* Tipo de Embalaje */}
                <div className="bg-gray-50 rounded-lg p-3">
                  <div className="flex items-center space-x-2 mb-1">
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    <span className="text-xs text-gray-500 uppercase">Embalaje</span>
                  </div>
                  <p className="text-sm font-bold text-gray-900">
                    {empaqueMostrar || '-'}
                  </p>
                </div>

                {/* Vehículo */}
                <div className="bg-gray-50 rounded-lg p-3">
                  <div className="flex items-center space-x-2 mb-1">
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-8 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <span className="text-xs text-gray-500 uppercase">Vehículo</span>
                  </div>
                  <p className="text-sm font-bold text-gray-900">
                    {route.claseVehiculo || route.vehiculo_requerido || '-'}
                  </p>
                </div>
              </div>

              {/* Producto - Fila completa */}
              <div className={`mt-3 rounded-lg p-3 ${
                getMissingFields(route).includes('producto')
                  ? 'bg-red-50 ring-1 ring-red-300'
                  : 'bg-gray-50'
              }`}>
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <svg className={`w-4 h-4 ${
                      getMissingFields(route).includes('producto') ? 'text-red-400' : 'text-gray-400'
                    }`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span className={`text-xs uppercase ${
                      getMissingFields(route).includes('producto') ? 'text-red-600 font-bold' : 'text-gray-500'
                    }`}>
                      Tipo Producto
                    </span>
                  </div>
                  <p className={`text-sm font-bold ${
                    productoMostrar ? 'text-gray-900' : 'text-red-500'
                  }`}>
                    {productoMostrar || '❌ Faltante'}
                  </p>
                </div>
              </div>

              {/* Valor Declarado */}
              {(route.valorMercancia || route.valor_declarado) && (
                <div className="mt-3 bg-gray-50 rounded-lg p-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      <span className="text-xs text-gray-500 uppercase">Valor Declarado</span>
                    </div>
                    <p className="text-lg font-bold text-gray-900">
                      ${parseInt(route.valorMercancia || route.valor_declarado).toLocaleString('es-CO')}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        );
      })}

      {/* Botón Crear Cotización */}
      {onCreateQuote && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <button
            onClick={onCreateQuote}
            disabled={!canCreate || isCreating}
            className={`w-full py-4 px-4 rounded-lg font-bold text-white transition-all duration-200 flex items-center justify-center space-x-2 text-lg
              ${(canCreate && !isCreating)
                ? 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 active:scale-95 cursor-pointer shadow-lg hover:shadow-xl' 
                : 'bg-gray-300 cursor-not-allowed opacity-60'
              }`}
          >
            {isCreating ? (
              <>
                <svg className="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Guardando...</span>
              </>
            ) : (
              <>
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Crear Cotización{routes.length > 1 ? ` (${routes.length} rutas)` : ''}</span>
              </>
            )}
          </button>
          {!canCreate && !isCreating && (
            <p className="text-xs text-gray-500 text-center mt-2">
              Complete todos los campos requeridos para continuar
            </p>
          )}
        </div>
      )}
    </div>
  );
};

/**
 * Componente auxiliar para renderizar filas de detalles
 */
const DetailRow = ({ label, value, valueClassName = '', rowClassName = '' }) => (
  <div className={`flex justify-between items-center py-1 ${rowClassName}`}>
    <span className="text-sm font-medium text-gray-600">
      {label}
    </span>
    <span className={`text-sm ${valueClassName || 'text-gray-900 font-semibold'}`}>
      {value}
    </span>
  </div>
);

export default QuoteDetailsPanel;
