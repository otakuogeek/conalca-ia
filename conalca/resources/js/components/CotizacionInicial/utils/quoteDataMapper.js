/**
 * Utilidades para mapeo de datos de cotización
 * @module CotizacionInicial/utils/quoteDataMapper
 */

/**
 * Mapea datos del backend a formato de quoteData normalizado
 * @param {Object} route - Datos crudos del backend
 * @returns {Object} Datos normalizados en formato camelCase
 */
export const mapBackendRouteToQuote = (route) => {
    return {
        ciudadOrigen: route.ciudad_origen ?? route.origen ?? null,
        ciudadDestino: route.ciudad_destino ?? route.destino ?? null,
        pesoMercancia: route.peso_mercancia ?? route.peso_kg ?? null,
        cantidadMercancia: route.cantidad_unidades ?? route.cantidad ?? null,
        valorMercancia: route.valor_mercancia ?? route.valor_declarado ?? null,
        vehiculo: route.vehiculo ?? null,
        claseVehiculo: route.vehiculo ?? route.claseVehiculo ?? route.vehiculo_requerido ?? null,
        producto: route.tipo_producto ?? route.producto ?? null,
        tipo_producto: route.tipo_producto ?? route.producto ?? null,
        producto_codigo: route.producto_codigo ?? null,
        empaque: route.empaque ?? null,
        empaque_id: route.empaque_id ?? null,
        contenedor: route.tipo_contenedor ?? route.contenedor ?? null,
        incoterm: route.incoterm ?? null,
        observaciones: route.observaciones ?? null
    };
};

/**
 * Mapea un array de rutas del backend a formato normalizado
 * @param {Array} routes - Array de rutas del backend
 * @returns {Array} Array de rutas normalizadas
 */
export const mapBackendRoutesToQuotes = (routes) => {
    if (!Array.isArray(routes)) return [];
    return routes.map(mapBackendRouteToQuote);
};

/**
 * Prepara datos de quoteData para el QuoteDetailsPanel
 * @param {Object|Array} quoteData - Datos de cotización (objeto o array)
 * @param {Object|null} selectedProduct - Producto seleccionado globalmente
 * @param {Object|null} selectedEmpaque - Empaque seleccionado globalmente
 * @returns {Array} Array de rutas preparadas para el panel
 */
export const prepareRoutesForPanel = (quoteData, selectedProduct, selectedEmpaque) => {
    // Caso 1: quoteData es array (multi-ruta)
    if (Array.isArray(quoteData) && quoteData.length > 0) {
        return quoteData.map((route, index) => ({
            ...route,
            // Priorizar el producto de la ruta sobre selectedProduct global
            producto: route.producto || route.tipo_producto || route.producto_nombre || (index === 0 ? selectedProduct?.nombre : null),
            producto_codigo: route.producto_codigo || (index === 0 ? selectedProduct?.codigo : null),
            tipo_producto: route.tipo_producto || route.producto || route.producto_nombre || (index === 0 ? selectedProduct?.nombre : null),
            // Priorizar el empaque de la ruta sobre selectedEmpaque global
            empaque: route.empaque || route.tipo_embajale || route.tipo_embalaje || (index === 0 ? (selectedEmpaque?.nome || selectedEmpaque?.nombre) : null),
            empaque_id: route.empaque_id || route.empaqueId || null,
            tipo_embalaje: route.tipo_embalaje || route.tipo_embajale || route.empaque || (index === 0 ? (selectedEmpaque?.nome || selectedEmpaque?.nombre) : null)
        }));
    }

    // Caso 2: quoteData es objeto con datos
    if (quoteData && typeof quoteData === 'object' && Object.keys(quoteData).length > 0) {
        return [{
            ...quoteData,
            producto: selectedProduct?.nombre || quoteData.producto || quoteData.tipo_producto,
            producto_codigo: selectedProduct?.codigo || quoteData.producto_codigo,
            tipo_producto: selectedProduct?.nombre || quoteData.tipo_producto || quoteData.producto,
            empaque: selectedEmpaque?.nome || selectedEmpaque?.nombre || quoteData.empaque || quoteData.tipo_embajale || quoteData.tipo_embalaje,
            empaque_id: quoteData.empaque_id || quoteData.empaqueId || null,
            tipo_embalaje: selectedEmpaque?.nome || selectedEmpaque?.nombre || quoteData.tipo_embalaje || quoteData.tipo_embajale || quoteData.empaque
        }];
    }

    // Caso 3: NO hay quoteData pero SÍ hay selectedProduct o selectedEmpaque
    if (selectedProduct || selectedEmpaque) {
        return [{
            producto: selectedProduct?.nombre,
            producto_codigo: selectedProduct?.codigo,
            tipo_producto: selectedProduct?.nombre,
            empaque: selectedEmpaque?.nome || selectedEmpaque?.nombre,
            empaque_id: selectedEmpaque?.id || null,
            tipo_embalaje: selectedEmpaque?.nome || selectedEmpaque?.nombre
        }];
    }

    return [];
};

/**
 * Crea un hash simple de datos para comparar y evitar duplicados
 * @param {Array} routes - Array de rutas
 * @returns {string} Hash de los datos
 */
export const createDataHash = (routes) => {
    if (!Array.isArray(routes)) return '';
    return JSON.stringify(routes.map(r =>
        `${r.origen || r.ciudad_origen}-${r.destino || r.ciudad_destino}-${r.peso_kg}-${r.producto || r.producto_nombre || ''}`
    ));
};

/**
 * Verifica si todos los datos requeridos están presentes
 * @param {Object|Array} quoteData - Datos de cotización
 * @param {Object|null} selectedProduct - Producto seleccionado
 * @param {Object|null} selectedEmpaque - Empaque seleccionado
 * @returns {boolean} true si todos los datos están presentes
 */
export const hasAllRequiredData = (quoteData, selectedProduct, selectedEmpaque) => {
    const data = Array.isArray(quoteData) ? quoteData[0] : quoteData;

    if (!data) return false;

    return (
        (selectedProduct !== null || data.producto || data.tipo_producto) &&
        (selectedEmpaque !== null || data.empaque || data.tipo_embajale) &&
        (data.ciudadOrigen || data.ciudad_origen) &&
        (data.ciudadDestino || data.ciudad_destino) &&
        (data.pesoMercancia || data.peso_mercancia) &&
        (data.cantidadMercancia || data.cantidad)
    );
};
