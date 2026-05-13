import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { prepareRoutesForPanel, hasAllRequiredData as checkHasAllRequiredData } from '../utils/quoteDataMapper';

/**
 * Hook para manejar los datos de cotización y selecciones
 * @param {Object} options - Opciones del hook
 * @param {Object|Array} options.quoteData - Datos de cotización del componente padre
 * @param {Function} options.setQuoteData - Función para actualizar datos
 * @param {Function} options.onUpdateMessages - Callback para actualizar mensajes
 * @returns {Object} Estado y funciones de gestión de datos
 */
export const useQuoteData = ({ quoteData, setQuoteData, onUpdateMessages }) => {
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [selectedEmpaque, setSelectedEmpaque] = useState(null);
    const [selectedRouteIndex, setSelectedRouteIndex] = useState(null);

    // Ref para evitar closures obsoletos en callbacks asíncronos
    const selectedRouteIndexRef = useRef(null);

    // Ref para evitar procesamiento duplicado de datos
    const lastProcessedDataHashRef = useRef(null);

    // Sincronizar ref con estado
    useEffect(() => {
        selectedRouteIndexRef.current = selectedRouteIndex;
    }, [selectedRouteIndex]);

    /**
     * Calcula routesData para QuoteDetailsPanel (memoizado)
     */
    const routesData = useMemo(() => {
        return prepareRoutesForPanel(quoteData, selectedProduct, selectedEmpaque);
    }, [quoteData, selectedProduct, selectedEmpaque]);

    /**
     * Verifica si todos los datos requeridos están presentes
     */
    const hasAllRequiredData = useMemo(() => {
        return checkHasAllRequiredData(quoteData, selectedProduct, selectedEmpaque);
    }, [quoteData, selectedProduct, selectedEmpaque]);

    /**
     * Maneja la selección de una ruta para edición
     * @param {number|null} index - Índice de la ruta (null para deseleccionar)
     */
    const handleSelectRoute = useCallback((index) => {
        setSelectedRouteIndex(index);

        if (onUpdateMessages && index !== null) {
            onUpdateMessages(prev => [
                ...prev,
                {
                    role: 'system',
                    text: `📝 Ruta ${index + 1} seleccionada para edición. Los próximos cambios se aplicarán solo a esta ruta.`,
                    created_at: new Date().toLocaleTimeString()
                }
            ]);
        }
    }, [onUpdateMessages]);

    /**
     * Actualiza el producto en la ruta actual o en todas
     * @param {Object} product - Producto a establecer
     */
    const updateProductInRoutes = useCallback((product) => {
        setSelectedProduct(product);

        const currentRouteIndex = selectedRouteIndexRef.current;

        setQuoteData(prev => {
            if (Array.isArray(prev) && prev.length > 0) {
                if (currentRouteIndex !== null && currentRouteIndex < prev.length) {
                    return prev.map((route, idx) => {
                        if (idx === currentRouteIndex) {
                            return {
                                ...route,
                                producto: product.nombre,
                                producto_codigo: product.codigo,
                                tipo_producto: product.nombre
                            };
                        }
                        return route;
                    });
                }
                // Sin ruta seleccionada - actualizar todas
                return prev.map(route => ({
                    ...route,
                    producto: product.nombre,
                    producto_codigo: product.codigo,
                    tipo_producto: product.nombre
                }));
            }
            return prev;
        });
    }, [setQuoteData]);

    /**
     * Actualiza el empaque en la ruta actual o en todas
     * @param {Object} empaque - Empaque a establecer
     */
    const updateEmpaqueInRoutes = useCallback((empaque) => {
        setSelectedEmpaque(empaque);

        const nombre = empaque.nome || empaque.nombre;

        setQuoteData(prev => {
            if (Array.isArray(prev) && prev.length > 0) {
                return prev.map(route => ({
                    ...route,
                    empaque: nombre,
                    empaqueId: empaque.id
                }));
            }
            return {
                ...prev,
                empaque: nombre,
                empaqueId: empaque.id
            };
        });
    }, [setQuoteData]);

    /**
     * Limpia el hash de datos procesados
     */
    const clearProcessedDataHash = useCallback(() => {
        lastProcessedDataHashRef.current = null;
    }, []);

    /**
     * Resetea todas las selecciones
     */
    const resetSelections = useCallback(() => {
        setSelectedProduct(null);
        setSelectedEmpaque(null);
        setSelectedRouteIndex(null);
        lastProcessedDataHashRef.current = null;
    }, []);

    return {
        selectedProduct,
        selectedEmpaque,
        selectedRouteIndex,
        selectedRouteIndexRef,
        lastProcessedDataHashRef,
        routesData,
        hasAllRequiredData,
        setSelectedProduct,
        setSelectedEmpaque,
        setSelectedRouteIndex,
        handleSelectRoute,
        updateProductInRoutes,
        updateEmpaqueInRoutes,
        clearProcessedDataHash,
        resetSelections
    };
};

export default useQuoteData;
