// resources/js/components/CotizacionInicial/PricingModal.jsx
import React, { useState, useEffect, useMemo, useRef } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import { 
  fetchLatestPricingsByRoute,
  fetchVehicleSuggestions,
  fetchRentabilityStats,
  fetchPercentageSettings,
  fetchVehicleCapacityGuide         
 } from '../../services/pricingService';
import { requestPricingRoute } from '../../services/solicitations';
import { FaSpinner, FaInfoCircle, FaTrashAlt, FaUndoAlt, FaExclamationTriangle, FaCopy, FaPlus } from 'react-icons/fa';

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
  const [loadingPricings, setLoadingPricings] = useState(false);
  const [loadingSuggestions, setLoadingSuggestions] = useState(false);
  const [suggestionsKey, setSuggestionsKey] = useState(null);
  const [errors, setErrors] = useState({});
  const [vehicleSuggestions, setVehicleSuggestions] = useState({});
  const [initialPorcentajeApplied, setInitialPorcentajeApplied] = useState(false);
  const [showGuideModal, setShowGuideModal] = useState(false);
  const [vehicleGuide, setVehicleGuide] = useState([]);
  const [loadingGuide, setLoadingGuide] = useState(false);
  const [guideError, setGuideError] = useState(null);
  const [removedReturnRoutes, setRemovedReturnRoutes] = useState([]);
  const [missingPricingRoutes, setMissingPricingRoutes] = useState([]);
  const [pricingRequestSent, setPricingRequestSent] = useState(false);
  const [sendingPricingRequest, setSendingPricingRequest] = useState(false);
  const [rentabilityDefaults, setRentabilityDefaults] = useState({
    min: 17,
    avg: 24,
    max: 32,
    scope: 'fallback',
  });
  const [percentageSettings, setPercentageSettings] = useState({
    use_custom: false,
    min: null,
    avg: null,
    max: null,
  });
  const [percentageSettingsLoaded, setPercentageSettingsLoaded] = useState(false);
  const minPercentageAllowed = useMemo(() => {
    if (percentageSettings.min !== null) {
      return Number(percentageSettings.min);
    }
    return rentabilityDefaults.min ?? 0;
  }, [percentageSettings.min, rentabilityDefaults.min]);
    
  const showBlockingSpinner = loadingPricings || loadingSuggestions;

  // Track whether return routes (devolución) for import containers have been injected
  const returnRoutesInjected = useRef(false);

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
  }, [routesSignature]);

  useEffect(() => {
    if (!routesSignature || !percentageSettingsLoaded) return;
    loadRentabilityStats();
  }, [
    routesSignature,
    percentageSettingsLoaded,
    percentageSettings.use_custom,
    percentageSettings.min,
    percentageSettings.avg,
    percentageSettings.max,
  ]);

  useEffect(() => {
    loadPercentageSettings();
  }, []);

  useEffect(() => {
    if (
      !initialPorcentajeApplied &&
      rentabilityDefaults.min &&
      rentabilityDefaults.min > 0
    ) {
      applyGlobalPorcentaje(rentabilityDefaults.min);
      setInitialPorcentajeApplied(true);
    }
  }, [rentabilityDefaults]);

 
  const loadPercentageSettings = async () => {
    try {
      const { data } = await fetchPercentageSettings();
      setPercentageSettings({
        use_custom: Boolean(data.use_custom),
        min: data.min !== null ? Number(data.min) : null,
        avg: data.avg !== null ? Number(data.avg) : null,
        max: data.max !== null ? Number(data.max) : null,
      });
    } catch (error) {
      console.error('[PricingModal] Error loading percentage settings', error);
    } finally {
      setPercentageSettingsLoaded(true);
    }
  };

  const openGuideModal = async () => {
    setShowGuideModal(true);
    if (vehicleGuide.length) return; // already loaded

    setLoadingGuide(true);
    setGuideError(null);

    try {
      const { data } = await fetchVehicleCapacityGuide();
      setVehicleGuide(data.data || []);
    } catch (error) {
      console.error('[PricingModal] Error loading vehicle guide', error);
      setGuideError('Unable to load vehicle guide. Please try again.');
    } finally {
      setLoadingGuide(false);
    }
  };

  const loadRentabilityStats = async () => {
    const adminMin = percentageSettings.min !== null ? Number(percentageSettings.min) : null;
    const adminAvg = percentageSettings.avg !== null ? Number(percentageSettings.avg) : null;
    const adminMax = percentageSettings.max !== null ? Number(percentageSettings.max) : null;

    if (percentageSettings.use_custom && adminMin !== null) {
      setRentabilityDefaults({
        min: adminMin,
        avg: adminAvg ?? adminMin,
        max: adminMax ?? adminMin,
        scope: 'custom',
      });
      return;
    }

    const referenceRoute = quoteData.find(route => route.ciudad_origen && route.ciudad_destino);

    try {
      const { data } = await fetchRentabilityStats({
        origin: referenceRoute?.ciudad_origen,
        destination: referenceRoute?.ciudad_destino,
      });

      setRentabilityDefaults({
        min: adminMin ?? (data.min || 0),    // ALWAYS keep admin min if it exists
        avg: data.avg || 0,
        max: data.max || 0,
        scope: data.scope,
      });
    } catch (error) {
      console.error('[PricingModal] Error loading rentability stats', error);
      setRentabilityDefaults(prev => ({
        ...prev,
        min: adminMin ?? prev.min,
      }));
    }
  };
const loadPricingsForRoutes = async () => {
  setLoadingPricings(true);
  try {
    // --- Auto-inject return (devolución) routes for IMPORTACION with containers ---
    let routesToProcess = [...quoteData];
    const isImport = (clientData?.operationType || '').toUpperCase() === 'IMPORTACION';

    if (!returnRoutesInjected.current && isImport) {
      const withReturns = [];
      quoteData.forEach((route) => {
        withReturns.push(route);
        const empaque = (route.tipo_embajale || '').toUpperCase();
        if (empaque.includes('CONTENEDOR') && !route.isReturnRoute) {
          withReturns.push({
            ...route,
            id: null,
            // Display shows reversed direction (city → port)
            ciudad_origen: route.ciudad_destino,
            ciudad_destino: route.ciudad_origen,
            // Keep original direction for pricing lookup
            _pricingOrigin: route.ciudad_origen,
            _pricingDestination: route.ciudad_destino,
            isReturnRoute: true,
            select_value: null,
            vehiculo_requerido: null,
            porcentaje: route.porcentaje || 0,
          });
        }
      });
      returnRoutesInjected.current = true;
      if (withReturns.length > quoteData.length) {
        routesToProcess = withReturns;
        setQuoteData(withReturns);
      }
    }

    // --- Fetch pricings for each route ---
    const isImportOp = (clientData?.operationType || '').toUpperCase() === 'IMPORTACION';
    const isExportOp = (clientData?.operationType || '').toUpperCase() === 'EXPORTACION';
    const responses = await Promise.all(
      routesToProcess.map(route => {
        const fetchOrigin = route.isReturnRoute ? route._pricingOrigin : route.ciudad_origen;
        const fetchDestination = route.isReturnRoute ? route._pricingDestination : route.ciudad_destino;

        if (!fetchOrigin || !fetchDestination) return Promise.resolve([]);

        // Determine condition: return routes that coexist with their forward route
        // are IDA-REGRESO (round-trip) — use special pricing
        let condition;
        if (route.isReturnRoute) {
          condition = 'IMPORTACION IDA-REGRESO';
        } else if (isImportOp) {
          condition = 'IMPORTACION';
        } else if (isExportOp) {
          condition = 'EXPORTACION';
        }

        // Extract container size (20 or 40) from tipo_embajale
        // Handles: CONTENEDOR 20, CONTENEDOR (1) 20 PIES, 1X40' HC, 40' GP, etc.
        const empaque = (route.tipo_embajale || '').toUpperCase();
        let containerSize;
        if (/CONTENEDOR|CONTAINER|\d+\s*[Xx']|PIES|\bGP\b|\bHC\b|\bHQ\b|\bOT\b|\bFR\b|\bRF\b/.test(empaque)) {
          if (/40/.test(empaque)) containerSize = '40';
          else if (/20/.test(empaque)) containerSize = '20';
        }

        return fetchLatestPricingsByRoute({
          origin: fetchOrigin,
          destination: fetchDestination,
          cargo_weight: route.isReturnRoute ? 0 : (route.peso_mercancia || 0),
          condition,
          is_return: route.isReturnRoute ? true : undefined,
          container_size: containerSize,
        })
          .then(({ data }) => {
            // Client-side filter: if container route, keep only matching container size
            if (containerSize) {
              return data.filter(p => {
                const vt = (p.vehicle_type || '').toUpperCase();
                const isContainerType = vt.includes('CONTENEDOR') || vt.includes('DEV CNT');
                if (!isContainerType) return true;
                return vt.includes(containerSize);
              });
            }
            return data;
          })
          .catch(error => {
            console.error('[PricingModal] Error loading pricing for route', route, error);
            return [];
          });
      })
    );

    // === FILTRO POR TAMAÑO DE CONTENEDOR ===
    // Filtrar responses según el tipo_embajale de cada ruta
    // Si es CONTENEDOR 20, solo mostrar opciones de 20'. Si es 40, solo 40'.
    const filteredResponses = responses.map((routePricings, idx) => {
      const route = routesToProcess[idx];
      const empaque = (route?.tipo_embajale || route?.empaque || '').toUpperCase();
      
      // Detectar tamaño de contenedor
      let containerSize = null;
      if (/CONTENEDOR|CONTAINER|\d+\s*[Xx']|PIES|\bGP\b|\bHC\b|\bHQ\b|\bOT\b|\bFR\b|\bRF\b/.test(empaque)) {
        if (/40/.test(empaque)) containerSize = '40';
        else if (/20/.test(empaque)) containerSize = '20';
      }

      console.log(`[PricingModal] Route ${idx} container filter:`, { empaque, containerSize, totalOptions: routePricings.length });

      if (!containerSize) return routePricings; // No es contenedor, devolver todo

      // Filtrar: solo contenedores del tamaño correcto + vehículos no-contenedor
      // Incluye tanto "CONTENEDOR 20'" como "DEV CNT 20'" (devolución)
      const filtered = routePricings.filter(p => {
        const vt = (p.vehicle_type || '').toUpperCase();
        const isContainerType = vt.includes('CONTENEDOR') || vt.includes('DEV CNT');
        if (!isContainerType) return true;
        return vt.includes(containerSize);
      });

      console.log(`[PricingModal] Route ${idx} after filter:`, { filteredCount: filtered.length });
      return filtered;
    });

    setPricings(filteredResponses);

    // Detect routes with no pricing options available
    const missing = [];
    routesToProcess.forEach((route, idx) => {
      if (!responses[idx] || responses[idx].length === 0) {
        const origin = route.ciudad_origen || '';
        const destination = route.ciudad_destino || '';
        if (origin && destination) {
          // Avoid duplicates in the list
          const key = `${origin}-${destination}`;
          if (!missing.find(m => `${m.origin}-${m.destination}` === key)) {
            missing.push({ origin, destination, routeIndex: idx, isReturn: route.isReturnRoute || false });
          }
        }
      }
    });
    setMissingPricingRoutes(missing);
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
      const route = quoteData[Number(routeIndex)];
      const routePricings = pricings[routeIndex] || [];
      let pricingForRoute = null;

      // For return routes: auto-select based on parent container size
      if (route?.isReturnRoute) {
        const parentEmpaque = (route.tipo_embajale || '').toUpperCase();
        const containerSize = parentEmpaque.includes("40") ? "40'" : "20'";
        // Find cheapest DEV CNT matching the container size (COMPENSACIÓN first)
        pricingForRoute = routePricings.find(p =>
          p.vehicle_type.includes(containerSize) && p.vehicle_type.includes('COMPENSACIÓN')
        ) || routePricings.find(p =>
          p.vehicle_type.includes(containerSize)
        );

        if (pricingForRoute) {
          // Set the suggestion text to match the auto-selected option
          setVehicleSuggestions(prev => ({
            ...prev,
            [routeIndex]: { vehicle_type: pricingForRoute.vehicle_type }
          }));
        }
      } else {
        // For main routes: use AI suggestion
        // Prefer matching by pricing_id (guarantees exact row)
        if (suggestion.pricing_id) {
          pricingForRoute = routePricings.find(
            p => Number(p.id) === Number(suggestion.pricing_id)
          );
        }

        // Fallback: match by vehicle type (and optionally the price) if no ID
        if (!pricingForRoute) {
          pricingForRoute = routePricings.find(p =>
            p.vehicle_type === suggestion.vehicle_type &&
            (
              suggestion.price === undefined ||
              Number(p.price) === Number(suggestion.price)
            )
          );
        }
      }

      if (!pricingForRoute) {
        console.warn(`[PricingModal] No pricing option matches for route ${routeIndex}`, suggestion, routePricings);
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

  // --- Extras (dynamic "Otro" items) ---
  const addExtra = (routeIndex) => {
    setQuoteData(prev => prev.map((route, index) => {
      if (index !== routeIndex) return route;
      const extras = [...(route.extras || [])];
      extras.push({ nombre: '', valor: 0 });
      return { ...route, extras };
    }));
  };

  const removeExtra = (routeIndex, extraIndex) => {
    setQuoteData(prev => prev.map((route, index) => {
      if (index !== routeIndex) return route;
      const extras = [...(route.extras || [])].filter((_, i) => i !== extraIndex);
      return { ...route, extras };
    }));
  };

  const updateExtra = (routeIndex, extraIndex, field, value) => {
    setQuoteData(prev => prev.map((route, index) => {
      if (index !== routeIndex) return route;
      const extras = [...(route.extras || [])];
      extras[extraIndex] = { ...extras[extraIndex], [field]: value };
      return { ...route, extras };
    }));
  };

  const getExtrasTotal = (route) => {
    return (route.extras || []).reduce((sum, e) => sum + (Number(e.valor) || 0), 0);
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

    if (porcentaje < minPercentageAllowed && porcentaje > 0) {
      setErrors(prev => ({
        ...prev,
        [`porcentaje_${routeIndex}`]: `No se puede ingresar una rentabilidad menor al ${minPercentageAllowed}%`
      }));
      return;
    }

    setErrors(prev => ({
      ...prev,
      [`porcentaje_${routeIndex}`]: null
    }));

    setQuoteData(prev => prev.map((route, index) =>
      index === routeIndex ? { ...route, porcentaje } : route
    ));
  };

  const applyGlobalPorcentaje = (porcentaje) => {
    setPorcentajeGlobal(porcentaje);
    setQuoteData(prev => prev.map(route => ({ ...route, porcentaje })));
  };

  const resetGlobalPorcentaje = () => {
    setPorcentajeGlobal(null);
  };

  // --- Duplicate a route ---
  const duplicateRoute = (routeIndex) => {
    const route = quoteData[routeIndex];
    if (!route) return;

    // Find where to insert: after the route and its devolución (if any)
    let insertIndex = routeIndex + 1;
    // If this is a main route with a devolución right after, skip past it
    if (!route.isReturnRoute && quoteData[insertIndex]?.isReturnRoute) {
      insertIndex++;
    }

    const newRoute = {
      ...route,
      id: null,
      select_value: null,
      vehiculo_requerido: null,
      isDuplicate: true,
      _duplicateOf: routeIndex,
    };

    // Insert into quoteData
    setQuoteData(prev => [
      ...prev.slice(0, insertIndex),
      newRoute,
      ...prev.slice(insertIndex),
    ]);

    // Copy pricings options for the duplicated route
    setPricings(prev => [
      ...prev.slice(0, insertIndex),
      prev[routeIndex] || [],
      ...prev.slice(insertIndex),
    ]);

    // Shift selectedPricings indices
    setSelectedPricings(prev => {
      const newSelected = {};
      Object.keys(prev).map(Number).sort((a, b) => a - b).forEach(idx => {
        if (idx >= insertIndex) {
          newSelected[idx + 1] = prev[idx];
        } else {
          newSelected[idx] = prev[idx];
        }
      });
      return newSelected;
    });
  };

  // --- Remove a duplicated route ---
  const removeDuplicateRoute = (routeIndex) => {
    const route = quoteData[routeIndex];
    if (!route || !route.isDuplicate) return;

    setQuoteData(prev => prev.filter((_, i) => i !== routeIndex));
    setPricings(prev => prev.filter((_, i) => i !== routeIndex));

    setSelectedPricings(prev => {
      const newSelected = {};
      let newIdx = 0;
      Object.keys(prev).map(Number).sort((a, b) => a - b).forEach(idx => {
        if (idx === routeIndex) return;
        newSelected[newIdx] = prev[idx];
        newIdx++;
      });
      return newSelected;
    });
  };

  // --- Remove a return route (devolución) ---
  const removeReturnRoute = (routeIndex) => {
    const route = quoteData[routeIndex];
    if (!route || !route.isReturnRoute) return;

    // Store the removed route with its associated data for recovery
    setRemovedReturnRoutes(prev => [
      ...prev,
      {
        route: { ...route },
        pricing: pricings[routeIndex] || [],
        selectedPricing: selectedPricings[routeIndex] || null,
        // Track which parent route it belongs to (the route just before it)
        parentOrigin: route.ciudad_destino, // reversed, so parent origin = return destination
        parentDestination: route.ciudad_origen,
      }
    ]);

    // Remove from quoteData
    setQuoteData(prev => prev.filter((_, i) => i !== routeIndex));

    // Remove from pricings array
    setPricings(prev => prev.filter((_, i) => i !== routeIndex));

    // Rebuild selectedPricings with updated indices
    setSelectedPricings(prev => {
      const newSelected = {};
      let newIdx = 0;
      Object.keys(prev).sort((a, b) => Number(a) - Number(b)).forEach(key => {
        const idx = Number(key);
        if (idx === routeIndex) return; // skip removed
        newSelected[newIdx] = prev[idx];
        newIdx++;
      });
      return newSelected;
    });
  };

  // --- Recover a previously removed return route ---
  const recoverReturnRoute = (removedIndex) => {
    const removed = removedReturnRoutes[removedIndex];
    if (!removed) return;

    // Find where to insert: after the parent route
    let insertAfter = -1;
    quoteData.forEach((r, i) => {
      if (
        r.ciudad_origen === removed.parentOrigin &&
        r.ciudad_destino === removed.parentDestination &&
        !r.isReturnRoute
      ) {
        insertAfter = i;
      }
    });

    const insertIndex = insertAfter >= 0 ? insertAfter + 1 : quoteData.length;

    // Re-insert into quoteData
    setQuoteData(prev => [
      ...prev.slice(0, insertIndex),
      removed.route,
      ...prev.slice(insertIndex),
    ]);

    // Re-insert into pricings
    setPricings(prev => [
      ...prev.slice(0, insertIndex),
      removed.pricing,
      ...prev.slice(insertIndex),
    ]);

    // Rebuild selectedPricings with shifted indices
    setSelectedPricings(prev => {
      const newSelected = {};
      const sortedKeys = Object.keys(prev).map(Number).sort((a, b) => a - b);
      sortedKeys.forEach(idx => {
        if (idx >= insertIndex) {
          newSelected[idx + 1] = prev[idx];
        } else {
          newSelected[idx] = prev[idx];
        }
      });
      // Restore selected pricing for recovered route
      if (removed.selectedPricing) {
        newSelected[insertIndex] = removed.selectedPricing;
      }
      return newSelected;
    });

    // Remove from removed list
    setRemovedReturnRoutes(prev => prev.filter((_, i) => i !== removedIndex));
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

    const extrasTotal = getExtrasTotal(route);

    const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
    return valueWithMargin + acompanamiento + parametersTotal + extrasTotal;
  };

  const canContinue = () => {
    const hasAllVehicles = quoteData.every(route => route.select_value);
    const hasValidPercentages = quoteData.every(route =>
      (route.porcentaje || 0) >= minPercentageAllowed
    );
    return hasAllVehicles && hasValidPercentages;
  };

  // const handleContinue = () => {
  //   if (canContinue()) {
  //     // Calcular y guardar los valores finales antes de continuar
  //     const updatedQuoteData = quoteData.map((route, index) => {
  //       const finalValue = calculateFinalValue(index);
  //       console.log(`PricingModal - Ruta ${index + 1}:`, {
  //         basePrice: selectedPricings[index]?.price,
  //         porcentaje: route.porcentaje,
  //         finalValue: finalValue
  //       });
  //       return {
  //         ...route,
  //         finalValue: finalValue
  //       };
  //     });
      
  //     // Actualizar el estado con los valores finales
  //     setQuoteData(updatedQuoteData);
      
  //     // Pequeño delay para asegurar que el estado se actualice antes de continuar
  //     setTimeout(() => {
  //       onNext();
  //     }, 100);
  //   } else {
  //     alert('Completa todos los campos requeridos antes de continuar.');
  //   }
  // };

    const handleRequestPricingRoutes = async () => {
      if (missingPricingRoutes.length === 0) return;
      setSendingPricingRequest(true);
      try {
        const { data } = await requestPricingRoute({
          routes: missingPricingRoutes.map(r => ({
            origin: r.origin,
            destination: r.destination,
          })),
          group_id: clientData?.groupId || null,
        });
        setPricingRequestSent(true);
        console.log('[PricingModal] Pricing route request sent:', data);
      } catch (error) {
        console.error('[PricingModal] Error sending pricing route request:', error);
        alert('Error al enviar la solicitud. Intenta nuevamente.');
      } finally {
        setSendingPricingRequest(false);
      }
    };

    const handleContinue = async () => {
      if (!canContinue()) {
        alert('Completa todos los campos requeridos antes de continuar.');
        return;
      }

      // 1) Compute final values locally
      const updatedQuoteData = quoteData.map((route, index) => {
        const finalValue = calculateFinalValue(index);
        return {
          ...route,
          finalValue,
        };
      });
      setQuoteData(updatedQuoteData);

      // 2) Persist updates (upsert) to backend
      try {
        const payloadRoutes = updatedQuoteData.map((route, index) => ({
          id: route.id || null, // IMPORTANT: send existing id to update
          ciudad_origen: route.ciudad_origen,
          ciudad_destino: route.ciudad_destino,
          peso_mercancia: route.peso_mercancia,
          cantidad: route.cantidad,
          tipo_embajale: route.tipo_embajale,
          tipo_producto: route.tipo_producto,
          vehiculo_requerido: route.vehiculo_requerido || selectedPricings[index]?.vehicle_type,
          valor_declarado: route.valor_declarado,
          pricing_id: selectedPricings[index]?.id ?? null,
          porcentaje: route.porcentaje,
          valor_cliente: route.finalValue,
          is_return_route: route.isReturnRoute || false,
          // add any extra fields you expect to persist (candado_satelital, etc.)
        }));

        const resp = await fetch('/api/chat/quote/save-routes', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            Accept: 'application/json',
          },
          body: JSON.stringify({
            group_id: clientData.groupId,
            routes: payloadRoutes,
          }),
        });

        const data = await resp.json();

        if (data.success && data.data?.routes) {
          const updatedQuoteDataWithIds = updatedQuoteData.map((route, idx) => ({
            ...route,
            id: data.data.routes[idx]?.id ?? route.id ?? null,
          }));
          setQuoteData(updatedQuoteDataWithIds);
        }

        if (!resp.ok || !data.success) {
          console.error('Error saving/updating routes:', data);
          alert('Ocurrió un problema guardando el pricing. Intenta de nuevo.');
          return;
        }

        // Optional: refresh local IDs from server response if needed
        // setQuoteData(prev => prev.map((r, i) => ({ ...r, id: data.data.routes[i]?.id || r.id })));

        // 3) Proceed to next step
        onNext();
      } catch (err) {
        console.error('Network error saving/updating routes:', err);
        alert('Error de red al guardar el pricing. Intenta nuevamente.');
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
      <button
        type="button"
        onClick={openGuideModal}
        className="inline-flex items-center px-3 py-2 text-sm font-semibold text-orange-600 border border-orange-300 rounded-lg hover:bg-orange-50"
      >
        <FaInfoCircle className="mr-2" />
        Guía de capacidad del vehículo
      </button>
      {showGuideModal && (
        <Modal onClose={() => setShowGuideModal(false)} size="md">
          <div className="p-6 space-y-4">
            <div className="flex items-center space-x-2 text-orange-600">
              <FaInfoCircle />
              <h3 className="text-lg font-semibold">Referencia de capacidad del vehículo</h3>
            </div>

            {loadingGuide && (
              <div className="text-sm text-gray-500">Cargando...</div>
            )}

            {guideError && (
              <div className="text-sm text-red-500">{guideError}</div>
            )}

            {!loadingGuide && !guideError && (
              <div className="overflow-x-auto border rounded-lg">
                <table className="min-w-full text-sm">
                  <thead className="bg-gray-50">
                    <tr className="text-left text-gray-600 uppercase text-xs tracking-wider">
                      <th className="px-4 py-2">Vehículo (Silogtran)</th>
                      <th className="px-4 py-2">Categoría logística</th>
                      <th className="px-4 py-2 text-right">Peso máximo (kg)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {vehicleGuide.map((row, idx) => (
                      <tr
                        key={`${row.vehiculo_silogtran}-${row.tabla_pricing}-${idx}`}
                        className="odd:bg-white even:bg-gray-50"
                      >
                        <td className="px-4 py-2 font-medium text-gray-800">
                          {row.vehiculo_silogtran}
                        </td>
                        <td className="px-4 py-2 text-gray-600">
                          {row.tabla_pricing}
                        </td>
                        <td className="px-4 py-2 text-right text-gray-900">
                          {Number(row.peso_maximo).toLocaleString()} kg
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            <div className="flex justify-end">
              <button
                type="button"
                onClick={() => setShowGuideModal(false)}
                className="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cerrar
              </button>
            </div>
          </div>
        </Modal>
      )}
     
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
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full w-[70%]">
            <div className="bg-gray-50 px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <div className="flex items-center justify-between">
                <h4 className="text-base font-600 text-gray-700 product-sans">Configuración de Rutas y Precios</h4>
                {removedReturnRoutes.length > 0 && (
                  <div className="flex items-center space-x-2">
                    {removedReturnRoutes.map((removed, rIdx) => (
                      <button
                        key={rIdx}
                        type="button"
                        onClick={() => recoverReturnRoute(rIdx)}
                        className="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:border-blue-300 transition-all duration-200 shadow-sm"
                        title={`Recuperar devolución ${removed.route.ciudad_origen} → ${removed.route.ciudad_destino}`}
                      >
                        <FaUndoAlt className="w-3 h-3 mr-1.5" />
                        Recuperar {removed.route.ciudad_origen?.substring(0, 3)}-{removed.route.ciudad_destino?.substring(0, 3)}
                      </button>
                    ))}
                  </div>
                )}
              </div>
            </div>
            <div className="overflow-x-auto flex-1 min-h-0">
              {/* Alert for routes without pricing */}
              {missingPricingRoutes.length > 0 && (
                <div className={`mx-4 mt-3 mb-2 rounded-lg border p-4 ${pricingRequestSent ? 'bg-green-50 border-green-300' : 'bg-amber-50 border-amber-300'}`}>
                  <div className="flex items-start space-x-3">
                    <FaExclamationTriangle className={`mt-0.5 flex-shrink-0 ${pricingRequestSent ? 'text-green-500' : 'text-amber-500'}`} />
                    <div className="flex-1">
                      {pricingRequestSent ? (
                        <>
                          <p className="text-sm font-semibold text-green-700 product-sans">
                            ✅ Solicitud enviada al equipo de Pricing
                          </p>
                          <p className="text-xs text-green-600 mt-1 product-sans">
                            Se ha notificado al equipo de Pricing para que creen las tarifas de las siguientes rutas. 
                            Podrás continuar con esta cotización una vez estén configuradas.
                          </p>
                          <div className="mt-2 flex flex-wrap gap-2">
                            {missingPricingRoutes.map((r, i) => (
                              <span key={i} className="inline-flex items-center text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium product-sans">
                                {r.isReturn ? '↩ ' : ''}{r.origin} → {r.destination}
                              </span>
                            ))}
                          </div>
                        </>
                      ) : (
                        <>
                          <p className="text-sm font-semibold text-amber-800 product-sans">
                            Rutas sin tarifa configurada
                          </p>
                          <p className="text-xs text-amber-700 mt-1 product-sans">
                            Las siguientes rutas no tienen precios en el sistema. Envía una solicitud al equipo de Pricing para que los configuren.
                          </p>
                          <div className="mt-2 flex flex-wrap gap-2">
                            {missingPricingRoutes.map((r, i) => (
                              <span key={i} className="inline-flex items-center text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded-full font-medium product-sans">
                                {r.isReturn ? '↩ ' : ''}{r.origin} → {r.destination}
                              </span>
                            ))}
                          </div>
                          <button
                            type="button"
                            onClick={handleRequestPricingRoutes}
                            disabled={sendingPricingRequest}
                            className="mt-3 inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg shadow-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            {sendingPricingRequest ? (
                              <>
                                <FaSpinner className="animate-spin mr-2" />
                                Enviando solicitud...
                              </>
                            ) : (
                              <>
                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Solicitar creación de precio al equipo Pricing
                              </>
                            )}
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              )}
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-gray-700 text-sm font-600 border-b border-gray-200 bg-gray-50">
                    <th className="text-left px-4 py-3 product-sans min-w-[120px]">Origen</th>
                    <th className="text-left px-4 py-3 product-sans min-w-[120px]">Destino</th>
                    <th className="text-left px-4 py-3 product-sans min-w-[140px]">Vehículo</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[110px]">Precio Base</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[100px]">Parámetros</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[160px]">Extras</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[80px]">Rent.(%)</th>
                    <th className="text-center px-4 py-3 product-sans min-w-[120px]">Valor Cliente</th>
                  </tr>
                </thead>
                <tbody>
                  {quoteData.map((route, index) => {
                    const automaticParameters = getAutomaticParameters(route);
                    const isReturn = route.isReturnRoute;
                    const isDuplicate = route.isDuplicate;
                    
                    // Determine if separator needed: new group starts at non-return, non-duplicate routes after index 0
                    const isGroupStart = index > 0 && !isReturn && !isDuplicate;
                    
                    return (
                      <React.Fragment key={index}>
                        {isGroupStart && (
                          <tr>
                            <td colSpan="8" className="px-0 py-0">
                              <div className="border-t-2 border-orange-200 mx-4 my-1"></div>
                            </td>
                          </tr>
                        )}
                        <tr className={`border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150 ${isReturn ? 'bg-blue-50/40' : ''} ${isDuplicate ? 'bg-amber-50/30' : ''}`}>
                        <td className="px-4 py-3 text-sm font-500 text-gray-700 product-sans">
                          <div className="flex items-center flex-wrap gap-1">
                            {isReturn && (
                              <>
                                <span className="text-[10px] font-semibold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded inline-flex items-center gap-0.5 whitespace-nowrap">
                                  ↩ DEVOLUCIÓN
                                </span>
                                <button
                                  type="button"
                                  onClick={() => removeReturnRoute(index)}
                                  title="Eliminar ruta de devolución"
                                  className="p-0.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors duration-150"
                                >
                                  <FaTrashAlt className="w-2.5 h-2.5" />
                                </button>
                              </>
                            )}
                            {isDuplicate && (
                              <>
                                <span className="text-[10px] font-semibold text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded inline-flex items-center gap-0.5 whitespace-nowrap">
                                  📋 DUPLICADA
                                </span>
                                <button
                                  type="button"
                                  onClick={() => removeDuplicateRoute(index)}
                                  title="Eliminar ruta duplicada"
                                  className="p-0.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors duration-150"
                                >
                                  <FaTrashAlt className="w-2.5 h-2.5" />
                                </button>
                              </>
                            )}
                            <span>{route.ciudad_origen || '-'}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3 text-sm font-500 text-gray-700 product-sans">
                          {route.ciudad_destino || '-'}
                        </td>
                        <td className="px-4 py-3">
                          {/* Sugerencia de la IA */}
                          {vehicleSuggestions[index] && (
                            <div className="text-xs mb-2 rounded bg-blue-50 text-blue-600 px-2 py-1">
                              IA sugiere: <strong>{vehicleSuggestions[index].vehicle_type}</strong>
                            </div>
                          )}
                          
                          <select
                            value={route.select_value || ''}
                            onChange={(e) => handleVehicleSelect(index, e.target.value)}
                            className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg h-10 focus:outline-none focus:ring-2 focus:ring-orange-400"
                          >
                            <option value="">{isReturn ? 'Selecciona tipo devolución' : 'Selecciona vehículo'}</option>
                            {(() => {
                              // Filter pricings by container size from tipo_embajale (safety net)
                              const routeEmpaque = (route.tipo_embajale || '').toUpperCase();
                              let routeContainerSize = null;
                              if (/CONTENEDOR|CONTAINER|\d+\s*[Xx']|PIES|\bGP\b|\bHC\b|\bHQ\b|\bOT\b|\bFR\b|\bRF\b/.test(routeEmpaque)) {
                                if (/40/.test(routeEmpaque)) routeContainerSize = '40';
                                else if (/20/.test(routeEmpaque)) routeContainerSize = '20';
                              }

                              return (pricings[index] || []).filter(pricing => {
                                const vt = (pricing.vehicle_type || '').toUpperCase();
                                const isContainerType = vt.includes('CONTENEDOR') || vt.includes('DEV CNT');

                                if (routeContainerSize) {
                                  if (isContainerType) return vt.includes(routeContainerSize);
                                  return true;
                                }
                                return true;
                              });
                            })().map(pricing => (
                              <option key={pricing.id} value={pricing.id}>
                                {pricing.vehicle_type}{pricing.extra ? ` (${pricing.extra})` : ''} - ${Number(pricing.price).toLocaleString()}
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
                            <div className="space-y-1.5">
                              {automaticParameters.map((param) => (
                                <div key={param.name} className="flex items-center gap-1.5">
                                  <label className={`text-[10px] font-medium text-${param.color}-600 product-sans whitespace-nowrap w-16 text-right`}>
                                    {param.label}
                                  </label>
                                  <input
                                    type="number"
                                    value={route[param.name] || ''}
                                    onChange={(e) => handleParameterChange(index, param.name, e.target.value)}
                                    className={`w-20 px-2 py-1 text-xs text-center border border-${param.color}-300 rounded focus:outline-none focus:ring-1 focus:ring-${param.color}-400 product-sans`}
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
                          <div className="space-y-1.5">
                            {(route.extras || []).map((extra, eIdx) => (
                              <div key={eIdx} className="flex items-center gap-1">
                                <input
                                  type="text"
                                  value={extra.nombre || ''}
                                  onChange={(e) => updateExtra(index, eIdx, 'nombre', e.target.value)}
                                  className="w-20 px-1.5 py-1 text-[11px] border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-orange-400 product-sans placeholder-gray-400"
                                  placeholder="Nombre"
                                />
                                <input
                                  type="number"
                                  value={extra.valor || ''}
                                  onChange={(e) => updateExtra(index, eIdx, 'valor', e.target.value)}
                                  className="w-20 px-1.5 py-1 text-[11px] text-center border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-orange-400 product-sans"
                                  placeholder="$ Valor"
                                  min="0"
                                />
                                <button
                                  type="button"
                                  onClick={() => removeExtra(index, eIdx)}
                                  className="p-0.5 text-red-400 hover:text-red-600 rounded transition-colors"
                                  title="Quitar"
                                >
                                  <FaTrashAlt className="w-2.5 h-2.5" />
                                </button>
                              </div>
                            ))}
                            <button
                              type="button"
                              onClick={() => addExtra(index)}
                              className="inline-flex items-center gap-1 text-[10px] text-orange-500 hover:text-orange-600 font-medium product-sans"
                            >
                              <FaPlus className="w-2.5 h-2.5" /> Agregar
                            </button>
                          </div>
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
                              min={minPercentageAllowed || 0}
                              max="100" 
                            />
                            {errors[`porcentaje_${index}`] && (
                              <span className="text-red-500 text-xs text-center product-sans">
                                Min {minPercentageAllowed}%
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-center">
                          <div className="flex flex-col items-center space-y-1">
                            <span className="text-sm font-700 text-orange-600">
                              ${Number(calculateFinalValue(index)).toLocaleString()}
                            </span>
                            {!isDuplicate && (
                              <button
                                type="button"
                                onClick={() => duplicateRoute(index)}
                                title="Duplicar ruta con otro precio"
                                className="p-1 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded transition-colors duration-150"
                              >
                                <FaCopy className="w-3 h-3" />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                      </React.Fragment>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Tarjetas de rentabilidad */}
          <div className="flex flex-row justify-between h-full space-x-4 w-[30%]">
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

        const extrasTotal = (route.extras || []).reduce((sum, e) => sum + (Number(e.valor) || 0), 0);
        return total + withMargin + parametersTotal + extrasTotal;
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
          <div className="flex flex-col space-y-1 mb-2 w-full items-center justify-center">
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

                const extrasTotal = (route.extras || []).reduce((sum, e) => sum + (Number(e.valor) || 0), 0);
                finalRoutePrice = withMargin + parametersTotal + extrasTotal;
              }

              const isReturn = route.isReturnRoute;
              const isDuplicate = route.isDuplicate;
              const isGroupStart = index > 0 && !isReturn && !isDuplicate;
              
              return (
                <React.Fragment key={index}>
                  {isGroupStart && (
                    <div className="w-4/5 border-t border-gray-200 my-1"></div>
                  )}
                  <div className="flex flex-col items-center w-full">
                    <div className="text-xs text-gray-500 product-sans mb-0.5 text-center">
                      {isReturn ? '↩ ' : ''}{isDuplicate ? '📋 ' : ''}{(route.ciudad_origen || '').substring(0, 3)}-{(route.ciudad_destino || '').substring(0, 3)}
                    </div>
                    <div className={`text-xs font-600 product-sans text-center ${isDuplicate ? 'text-amber-600' : 'text-orange-600'}`}>
                      ${Number(finalRoutePrice).toLocaleString()}
                    </div>
                  </div>
                </React.Fragment>
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