import { useRef, useState, useCallback } from 'react';
import { mapBackendRouteToQuote } from '../utils/quoteDataMapper';

/**
 * Hook para manejar el polling de status de AI runs
 * @param {Object} options - Opciones del hook
 * @param {Function} options.setQuoteData - Función para actualizar datos de cotización
 * @param {Function} options.setSelectedProduct - Función para seleccionar producto
 * @param {Function} options.setSelectedEmpaque - Función para seleccionar empaque
 * @returns {Object} Estado y funciones del polling
 */
export const usePolling = ({ setQuoteData, setSelectedProduct, setSelectedEmpaque }) => {
    const [currentRunId, setCurrentRunId] = useState(null);
    const [processingMessage, setProcessingMessage] = useState(null);
    const [processingProgress, setProcessingProgress] = useState(0);
    const pollingIntervalRef = useRef(null);

    /**
     * Limpia el estado de procesamiento
     */
    const clearProcessingState = useCallback(() => {
        setProcessingMessage(null);
        setProcessingProgress(100);
        setTimeout(() => setProcessingProgress(0), 500);
        setCurrentRunId(null);
    }, []);

    /**
     * Inicia el polling para un run específico
     * @param {string} threadId - ID del thread
     * @param {string} runId - ID del run a monitorear
     */
    const startPollingRun = useCallback((threadId, runId) => {
        setCurrentRunId(runId);
        setProcessingProgress(50);

        let pollAttempts = 0;
        const maxPollAttempts = 20;
        let isPollingActive = true;

        const stopPolling = (reason = '') => {
            isPollingActive = false;
            if (pollingIntervalRef.current) {
                clearInterval(pollingIntervalRef.current);
                pollingIntervalRef.current = null;
            }
            setCurrentRunId(null);
            setProcessingMessage(null);
        };

        const pollRun = async () => {
            try {
                if (!isPollingActive) return;

                pollAttempts++;

                if (pollAttempts > maxPollAttempts) {
                    stopPolling('Timeout');
                    return;
                }

                const response = await fetch(`/api/chat/run/${threadId}/${runId}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();

                if (!data.success) {
                    console.error('Error en polling:', data);
                    return;
                }

                const status = data.data?.status;

                if (status === 'completed_with_data') {
                    // Procesar rutas extraídas
                    if (data.data.extracted_data && Array.isArray(data.data.extracted_data)) {
                        const allRoutes = data.data.extracted_data;

                        if (setQuoteData && allRoutes.length > 0) {
                            const processedRoutes = allRoutes.map(mapBackendRouteToQuote);
                            setQuoteData(processedRoutes);

                            // Auto-seleccionar empaque de primera ruta
                            const firstRoute = allRoutes[0];
                            if (firstRoute.empaque && firstRoute.empaque_id) {
                                setSelectedEmpaque({
                                    id: firstRoute.empaque_id,
                                    nome: firstRoute.empaque,
                                    nombre: firstRoute.empaque
                                });
                            }

                            // Auto-seleccionar producto de primera ruta
                            if (firstRoute.tipo_producto || firstRoute.producto) {
                                const prodNombre = firstRoute.tipo_producto || firstRoute.producto;
                                setSelectedProduct({
                                    id: firstRoute.producto_id || 0,
                                    codigo: firstRoute.producto_codigo || prodNombre.toUpperCase(),
                                    producto_codigo: firstRoute.producto_codigo || prodNombre.toUpperCase(),
                                    nombre: prodNombre.toUpperCase(),
                                    producto: prodNombre.toUpperCase()
                                });
                            }
                        }
                    }

                    stopPolling('Datos completados');
                    return;
                }

                if (status === 'completed' || status === 'failed' || status === 'cancelled') {
                    stopPolling(`Estado final: ${status}`);
                    return;
                }

            } catch (error) {
                console.error('Error en polling:', error);
                stopPolling('Error');
            }
        };

        // Ejecutar primera vez inmediatamente
        pollRun();

        // Configurar intervalo
        const interval = setInterval(pollRun, 2000);
        pollingIntervalRef.current = interval;
    }, [setQuoteData, setSelectedProduct, setSelectedEmpaque]);

    /**
     * Detiene el polling activo
     */
    const stopPolling = useCallback(() => {
        if (pollingIntervalRef.current) {
            clearInterval(pollingIntervalRef.current);
            pollingIntervalRef.current = null;
        }
        setCurrentRunId(null);
        setProcessingMessage(null);
    }, []);

    /**
     * Cleanup del polling (para usar en useEffect cleanup)
     */
    const cleanup = useCallback(() => {
        if (pollingIntervalRef.current) {
            clearInterval(pollingIntervalRef.current);
            pollingIntervalRef.current = null;
        }
    }, []);

    return {
        currentRunId,
        processingMessage,
        processingProgress,
        setProcessingMessage,
        setProcessingProgress,
        startPollingRun,
        stopPolling,
        clearProcessingState,
        cleanup,
        pollingIntervalRef
    };
};

export default usePolling;
