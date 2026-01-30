// resources/js/components/CotizacionInicial/EditRoutesModal.jsx
import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import Modal from './ui/Modal';
import Select from 'react-select';
import { fetchCities } from '../../services/citiesService';
import { fetchPackings } from '../../services/packingService';
import { fetchProducts } from '../../services/productService';

const EditRoutesModal = ({
  onClose,
  onNext,
  groupId,
  quoteData,
  setQuoteData,
  clientData
}) => {
  // Normalizar quoteData: puede venir como array (recuperación) u objeto (chat)
  const normalizedRoutes = React.useMemo(() => {
    if (!quoteData) return [];
    if (Array.isArray(quoteData)) return quoteData;
    // Si es objeto, convertir a array
    return [quoteData];
  }, [quoteData]);
  
  // 🔥 FIX: Inicializar vacío para evitar duplicación - se llenará desde BD
  const [localRoutes, setLocalRoutes] = useState([]);
  const [cities, setCities] = useState([]);
  const [packings, setPackings] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [catalogsLoaded, setCatalogsLoaded] = useState(false);

  // 1️⃣ PRIMERO: Cargar cities, packings, products EN PARALELO
  useEffect(() => {
    const loadCatalogs = async () => {
      setLoading(true);
      try {
        const [citiesResp, packingsResp, productsResp] = await Promise.all([
          fetchCities(),
          fetchPackings(),
          fetchProducts()
        ]);
        
        if (citiesResp.data.success) {
          console.log('🌍 Ciudades cargadas:', citiesResp.data.data.length);
          setCities(citiesResp.data.data || []);
        } else {
          setError('Error cargando ciudades');
        }
        
        if (packingsResp.data.success) {
          console.log('📦 Embalajes cargados:', packingsResp.data.data.length);
          console.log('Muestra:', packingsResp.data.data.slice(0, 5));
          setPackings(packingsResp.data.data || []);
        } else {
          setError('Error cargando embalajes');
        }
        
        if (productsResp.data.success) {
          console.log('📊 Productos cargados:', productsResp.data.data.length);
          setProducts(productsResp.data.data || []);
        } else {
          setError('Error cargando productos');
        }
        
        // Marcar catálogos como cargados
        setCatalogsLoaded(true);
        console.log('✅ Todos los catálogos cargados - listo para cargar rutas');
        
      } catch (err) {
        console.error('❌ Error cargando catálogos:', err);
        setError('Error de red al cargar catálogos');
      } finally {
        setLoading(false);
      }
    };
    
    loadCatalogs();
  }, []);

  // Normaliza una ruta usando extracted_data si viene como JSON/array en BD
  const applyExtractedFallback = (route, idx = 0, allExtractedData = null) => {
    // Si se pasa allExtractedData directamente desde el padre, usarlo
    let extracted = allExtractedData || route.extracted_data;
    
    if (!extracted) {
      console.log(`⚠️ Ruta ${idx}: No hay extracted_data, usando route tal cual`);
      return route;
    }

    try {
      if (typeof extracted === 'string') {
        extracted = JSON.parse(extracted);
      }
    } catch (err) {
      console.warn('⚠️ No se pudo parsear extracted_data:', err);
      return route;
    }

    const arr = Array.isArray(extracted)
      ? extracted
      : Object.values(extracted).filter(v => v && typeof v === 'object');

    const extractedRoute = arr[idx];
    if (!extractedRoute) {
      console.warn(`⚠️ Ruta ${idx}: No se encontró extracted_data en índice ${idx}, usando route directamente`);
      return route;
    }

    // PRIORIZAR extracted_data porque tiene los valores del prompt inicial
    // route.empaque puede estar vacío o con valor incorrecto en la primera extracción
    const empaqueValue = extractedRoute.empaque || extractedRoute.tipo_embajale || extractedRoute.embalaje || route.empaque || route.tipo_embajale || '';
    
    console.log(`🔧 applyExtractedFallback Ruta ${idx + 1}:`, {
      '✅ USANDO': empaqueValue,
      'extracted.empaque (PRIORIDAD)': extractedRoute.empaque,
      'extracted.tipo_embajale': extractedRoute.tipo_embajale,
      'route.empaque (fallback)': route.empaque,
      'route.tipo_embajale': route.tipo_embajale,
      'extracted.empaque_id': extractedRoute.empaque_id,
      'route.empaque_id': route.empaque_id,
      'incluye_tara': extractedRoute.incluye_tara || route.incluye_tara
    });
    
    return {
      ...route,
      tipo_embajale: empaqueValue,
      tipo_embalaje: empaqueValue,
      empaque: empaqueValue,
      // IGNORAR empaque_id de extracted_data - viene incorrecto (6 para todos)
      // Usar SOLO el nombre del empaque
      empaque_id: null,
      producto: extractedRoute.producto || extractedRoute.tipo_producto || route.producto || '',
      tipo_producto: extractedRoute.tipo_producto || extractedRoute.producto || route.tipo_producto || '',
      producto_codigo: extractedRoute.producto_codigo || route.producto_codigo || '',
      peso_mercancia: extractedRoute.peso_kg || extractedRoute.peso || extractedRoute.peso_mercancia || route.peso_mercancia || '',
      cantidad: extractedRoute.cantidad || extractedRoute.cantidad_unidades || route.cantidad || '',
      ciudad_origen: extractedRoute.origen || extractedRoute.ciudad_origen || route.ciudad_origen || '',
      ciudad_destino: extractedRoute.destino || extractedRoute.ciudad_destino || route.ciudad_destino || '',
      valor_declarado: extractedRoute.valor_declarado || extractedRoute.valor || extractedRoute.valor_mercancia || route.valor_declarado || '',
      incluye_tara: extractedRoute.incluye_tara === true || route.incluye_tara === true
    };
  };

  // 2️⃣ SEGUNDO: Cargar rutas desde BD SOLO cuando catalogsLoaded sea true
  useEffect(() => {
    if (!catalogsLoaded) {
      console.log('⏳ Esperando a que se carguen los catálogos...');
      return;
    }
    
    const loadRoutes = async () => {
      if (!groupId) {
        console.warn('⚠️ EditRoutesModal: No groupId provided');
        return;
      }
      
      console.log('💾 Cargando rutas desde BD para group_id:', groupId);
      console.log('📊 Estado inicial de localRoutes:', localRoutes.length, 'ruta(s)');
      console.log('📊 Estado inicial de quoteData:', Array.isArray(quoteData) ? quoteData.length : (quoteData ? 1 : 0), 'ruta(s)');
      setLoading(true);
      
      try {
        const resp = await fetch(`/api/chat/quote/routes/${groupId}`, {
          headers: { 
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
        });
        
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
        }
        
        const data = await resp.json();
        console.log('✅ Rutas recuperadas desde BD:', data);
        
        if (data.success && data.data?.routes) {
          console.log('🔍 RUTAS CRUDAS ANTES DE APLICAR FALLBACK:', JSON.stringify(data.data.routes, null, 2));
          
          // Obtener extracted_data del primer elemento (todas las rutas comparten el mismo extracted_data)
          const firstRoute = data.data.routes[0];
          let parsedExtractedData = null;
          
          if (firstRoute?.extracted_data) {
            try {
              parsedExtractedData = typeof firstRoute.extracted_data === 'string'
                ? JSON.parse(firstRoute.extracted_data)
                : firstRoute.extracted_data;
              console.log('📋 extracted_data parseado:', parsedExtractedData);
            } catch (err) {
              console.warn('⚠️ Error parseando extracted_data:', err);
            }
          }
          
          const routes = data.data.routes.map((r, i) => applyExtractedFallback(r, i, parsedExtractedData));
          console.log(`📦 ${routes.length} ruta(s) encontrada(s)`);
          
          // Debug: verificar tipo_embajale Y que los IDs se preserven
          routes.forEach((r, i) => {
            console.log(`Ruta ${i + 1} - id: ${r.id}, tipo_embajale: ${r.tipo_embajale}, empaque: ${r.empaque}`);
          });
          
          setLocalRoutes(routes);
          // NO actualizar quoteData aquí - solo trabajar con localRoutes para evitar duplicación
        } else {
          console.warn('⚠️ No se encontraron rutas guardadas');
          // Si no hay rutas en BD, usar quoteData pasado como prop
          if (normalizedRoutes.length > 0) {
            console.log('🔄 Usando rutas desde props (recién creadas)');
            setLocalRoutes(normalizedRoutes.map((r, i) => applyExtractedFallback(r, i)));
          } else {
            setError('No se encontraron rutas para este grupo');
          }
        }
      } catch (err) {
        console.error('❌ Error cargando rutas:', err);
        setError('Error de red al cargar rutas: ' + err.message);
        // Fallback: usar normalizedRoutes si hay error
        if (normalizedRoutes.length > 0) {
          setLocalRoutes(normalizedRoutes.map((r, i) => applyExtractedFallback(r, i)));
        }
      } finally {
        setLoading(false);
      }
    };
    
    loadRoutes();
  }, [catalogsLoaded, groupId]); // Depende de catalogsLoaded Y groupId

  // Función auxiliar para normalizar texto (quitar tildes, mayúsculas, espacios)
  const normalizeText = (text) => {
    if (!text) return '';
    return text
      .toString()
      .toUpperCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Quitar tildes/acentos
      .trim();
  };

  // Normalizar con manejo de plural/singular para embalajes
  const normalizePackingName = (text) => {
    if (!text) return '';
    let normalized = normalizeText(text);
    
    // Remover plural común en español (terminaciones -S, -ES)
    if (normalized.endsWith('S') && normalized.length > 2) {
      normalized = normalized.slice(0, -1); // BOLSAS -> BOLSA, CAJAS -> CAJA
    }
    if (normalized.endsWith('ES') && normalized.length > 3) {
      normalized = normalized.slice(0, -2); // TONELES -> TONEL
    }
    
    return normalized;
  };

  // 🆕 Ciudades principales que deben tener prioridad cuando hay ambigüedad
  // (ej: CARTAGENA debe ser BOLIVAR, no NARIÑO)
  const CIUDADES_PRINCIPALES = {
    'CARTAGENA': 'BOLIVAR',
    'ARMENIA': 'QUINDIO',
    'CALI': 'VALLE DEL CAUCA',
    'MEDELLIN': 'ANTIOQUIA',
    'BOGOTA': 'CUNDINAMARCA',
    'BARRANQUILLA': 'ATLANTICO',
    'BUCARAMANGA': 'SANTANDER',
    'PEREIRA': 'RISARALDA',
    'MANIZALES': 'CALDAS',
    'IBAGUE': 'TOLIMA',
    'CUCUTA': 'NORTE DE SANTANDER',
    'SANTA MARTA': 'MAGDALENA',
    'VILLAVICENCIO': 'META',
    'PASTO': 'NARINO',
    'NEIVA': 'HUILA',
    'MONTERIA': 'CORDOBA',
    'VALLEDUPAR': 'CESAR',
    'TUNJA': 'BOYACA',
    'POPAYAN': 'CAUCA',
    'SINCELEJO': 'SUCRE',
    'RIOHACHA': 'LA GUAJIRA',
    'QUIBDO': 'CHOCO',
    'FLORENCIA': 'CAQUETA',
    'YOPAL': 'CASANARE',
    'BUENAVENTURA': 'VALLE DEL CAUCA',
  };

  // Helpers to preselect if route has code or name
  const resolveCityCode = (routeValue) => {
    if (!routeValue) return '';
    
    console.log('🔍 resolveCityCode INPUT:', routeValue);
    
    // Buscar por código exacto
    const byCode = cities.find(c => String(c.ciudad_codigo) === String(routeValue));
    if (byCode) {
      console.log(`✅ Ciudad encontrada por código: ${byCode.ciudad_codigo}`);
      return byCode.ciudad_codigo;
    }

    // Buscar por nombre normalizado (sin tildes, sin case-sensitive)
    const normalizedRoute = normalizeText(routeValue);
    
    // 🆕 PRIMERO: Extraer solo el nombre de la ciudad (sin departamento)
    // Para poder aplicar la lógica de prioridad incluso si viene "CARTAGENA - NARIÑO"
    const ciudadSinDepto = normalizedRoute.split('-')[0].trim();
    
    // Verificar si esta ciudad tiene un departamento principal definido
    const deptoPrincipal = CIUDADES_PRINCIPALES[ciudadSinDepto];
    
    if (deptoPrincipal) {
      // Buscar la ciudad principal (ej: CARTAGENA - BOLIVAR)
      const principal = cities.find(c => {
        const normalizedCity = normalizeText(c.ciudad_nombre || '');
        const nombreCiudad = normalizedCity.split('-')[0].trim();
        return nombreCiudad === ciudadSinDepto && normalizedCity.includes(normalizeText(deptoPrincipal));
      });
      
      if (principal) {
        console.log(`✅ Ciudad PRINCIPAL encontrada: "${routeValue}" → ${principal.ciudad_nombre} (${principal.ciudad_codigo})`);
        return principal.ciudad_codigo;
      }
    }
    
    // Si no tiene ciudad principal o no se encontró, búsqueda exacta
    const byExactName = cities.find(c => {
      const normalizedCity = normalizeText(c.ciudad_nombre || '');
      return normalizedCity === normalizedRoute;
    });
    
    if (byExactName) {
      console.log(`✅ Ciudad encontrada (exacta): "${routeValue}" → código ${byExactName.ciudad_codigo} (${byExactName.ciudad_nombre})`);
      return byExactName.ciudad_codigo;
    }
    
    // Búsqueda parcial: buscar ciudades que comiencen con el nombre
    const candidatas = cities.filter(c => {
      const normalizedCity = normalizeText(c.ciudad_nombre || '');
      const nombreCiudad = normalizedCity.split('-')[0].trim();
      return nombreCiudad === ciudadSinDepto || normalizedCity.startsWith(ciudadSinDepto + ' -');
    });
    
    if (candidatas.length > 0) {
      console.log(`✅ Ciudad encontrada (parcial): "${routeValue}" → código ${candidatas[0].ciudad_codigo} (${candidatas[0].ciudad_nombre})`);
      return candidatas[0].ciudad_codigo;
    }
    
    console.warn(`⚠️ Ciudad NO encontrada: "${routeValue}" (normalizado: "${normalizedRoute}")`);
    return '';
  };

  const resolvePackingCode = (routeValue) => {
    if (!routeValue) {
      console.log('📦 resolvePackingCode: valor vacío, retornando string vacío');
      return '';
    }

    const value = routeValue?.Codigo ?? routeValue?.codigo ?? routeValue?.empaque_id ?? routeValue?.id ?? routeValue;
    const isNumeric = !isNaN(value) && !isNaN(parseFloat(value));

    console.log('🔍 resolvePackingCode INPUT:', {
      routeValueOriginal: routeValue,
      valueExtraido: value,
      tipo: typeof value,
      esNumerico: isNumeric,
      packingsDisponibles: packings.length,
      todosLosEmbalajes: packings.map(p => ({ Codigo: p.Codigo, Nombre: p.Nombre }))
    });
    
    // Si el valor es un string (nombre), buscar primero por nombre
    if (!isNumeric) {
      // Normalizar con manejo de plural/singular
      const normalizedRoute = normalizePackingName(value);
      console.log('🔤 Buscando por NOMBRE primero:', { original: value, normalizado: normalizedRoute });
      
      const byName = packings.find(p => {
        const name = p.Nombre || p.nombre || p.nome || '';
        const normalized = normalizePackingName(name);
        const match = normalized === normalizedRoute;
        console.log(`  "${normalizedRoute}" === "${normalized}" (${name})? ${match}`);
        return match;
      });
      
      if (byName) {
        console.log('✅ Embalaje encontrado por NOMBRE:', byName);
        return byName.Codigo;
      }
      
      // Segundo intento: buscar con match parcial (contiene)
      const byPartialMatch = packings.find(p => {
        const name = p.Nombre || p.nombre || p.nome || '';
        const normalized = normalizePackingName(name);
        const match = normalized.includes(normalizedRoute) || normalizedRoute.includes(normalized);
        if (match) console.log(`  Match parcial: "${normalizedRoute}" <-> "${normalized}" (${name})`);
        return match;
      });
      
      if (byPartialMatch) {
        console.log('✅ Embalaje encontrado por MATCH PARCIAL:', byPartialMatch);
        return byPartialMatch.Codigo;
      }
    }
    
    // Buscar por código solo si es numérico
    const byCode = packings.find(p => String(p.Codigo ?? p.codigo ?? p.empaque_id ?? p.id) === String(value));
    if (byCode) {
      console.log('✅ Embalaje encontrado por CÓDIGO:', byCode);
      return byCode.Codigo;
    }

    // Último intento: buscar por nombre normalizado si no se encontró por código
    const normalizedRoute = normalizePackingName(value);
    console.log('🔤 Último intento por nombre normalizado:', { original: value, normalizado: normalizedRoute });
    
    const byName = packings.find(p => {
      const name = p.Nombre || p.nombre || p.nome || '';
      const normalized = normalizePackingName(name);
      console.log(`  Comparando: "${normalizedRoute}" === "${normalized}" (${name})`);
      return normalized === normalizedRoute;
    });
    
    if (byName) {
      console.log('✅ Embalaje encontrado por NOMBRE:', byName);
      return byName.Codigo;
    }
    
    console.warn('⚠️ Embalaje NO encontrado para:', value, 'en', packings.length, 'opciones');
    console.warn('   Embalajes disponibles:', packings.map(p => p.Nombre || p.nome).join(', '));
    return '';
  };

  const resolveProductCode = (routeValue) => {
    if (!routeValue) {
      console.log('📦 resolveProductCode: valor vacío');
      return '';
    }
    
    console.log('🔍 resolveProductCode:', {
      routeValue,
      productsLength: products.length,
      productsSample: products.slice(0, 3)
    });
    
    // Buscar por código
    const byCode = products.find(p => String(p.producto_codigo) === String(routeValue));
    if (byCode) {
      console.log('✅ Producto encontrado por código:', byCode);
      return byCode.producto_codigo;
    }

    // Buscar por nombre normalizado
    const normalizedRoute = normalizeText(routeValue);
    const byName = products.find(p => {
      const name = p.producto_nombre || p.nombre || '';
      return normalizeText(name) === normalizedRoute;
    });
    
    if (byName) {
      console.log('✅ Producto encontrado por nombre:', byName);
      return byName.producto_codigo;
    }
    
    console.warn('⚠️ Producto NO encontrado en BD:', routeValue, '- usaré el valor como texto personalizado');
    // Si no se encuentra, retornar el valor original (producto personalizado)
    return routeValue;
  };

  // Tabla de capacidades de vehículos (kg) - tomada de vehiculos_pricing
  const vehicleCapacities = {
    'CAMIONETA': 2000,
    'TURBO': 4000,
    'SENCILLO': 9000,
    'PATINETA2': 20000,
    'PATINETA3': 25000,
    'TRACTOMULA 2': 30000,
    'TRACTOMULA 3': 34000
  };

  // Función para recomendar vehículo basado en peso
  const recommendVehicle = (weight) => {
    if (!weight || weight <= 0) return 'SENCILLO';
    
    const weightNum = parseFloat(weight);
    
    // Ordenar por capacidad ascendente y seleccionar el primero que pueda llevar el peso
    const sortedVehicles = Object.entries(vehicleCapacities)
      .sort((a, b) => a[1] - b[1]);
    
    for (const [vehicle, capacity] of sortedVehicles) {
      if (weightNum <= capacity) {
        console.log(`🚚 Recomendando ${vehicle} para ${weightNum} kg (capacidad: ${capacity} kg)`);
        return vehicle;
      }
    }
    
    // Si excede todas las capacidades, retornar el más grande
    console.log(`⚠️ Peso ${weightNum} kg excede todas las capacidades, recomendando TRACTOMULA 3`);
    return 'TRACTOMULA 3';
  };

  const handleChange = (index, field, value) => {
    setLocalRoutes(prev =>
      prev.map((r, i) => {
        if (i !== index) return r;
        
        const updated = { ...r, [field]: value };
        
        // Si cambió el peso, auto-recomendar vehículo
        if (field === 'peso_mercancia' && value) {
          const recommendedVehicle = recommendVehicle(value);
          updated.vehiculo_requerido = recommendedVehicle;
          console.log(`🔄 Peso actualizado a ${value} kg, vehículo recomendado: ${recommendedVehicle}`);
        }
        
        return updated;
      })
    );
  };

  const handleSave = async () => {
    if (!groupId) {
      setError('Group ID is required to save routes.');
      return;
    }
    setSaving(true);
    setError(null);

    try {
      const resp = await fetch('/api/chat/quote/save-routes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          Accept: 'application/json',
        },
        body: JSON.stringify({
          group_id: groupId,
          routes: localRoutes,
        }),
      });
      const data = await resp.json();
      if (!resp.ok || !data.success) {
        throw new Error(data.error || 'Server error saving routes');
      }

      // Actualizar IDs en localRoutes
      if (data.data?.routes) {
        const updatedRoutes = localRoutes.map((r, i) => ({
          ...r,
          id: data.data.routes[i]?.id || r.id || null,
        }));
        
        setLocalRoutes(updatedRoutes);
        // Actualizar quoteData UNA SOLA VEZ al guardar
        setQuoteData(updatedRoutes);
        
        console.log('✅ Rutas guardadas y actualizadas:', updatedRoutes.length, 'ruta(s)');
      }
      
      onNext && onNext();
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  };

  // Options for react-select
  const citiesOptions = cities.map(c => ({
    value: c.ciudad_codigo,
    label: `${c.ciudad_nombre} - ${c.departamento_nombre}`,
  }));

  const packingOptions = packings.map(p => ({
    value: p.Codigo || p.codigo || p.empaque_id || p.id,
    label: p.Nombre || p.nombre || p.nome || p.empaque || p.Codigo,
  }));

  const productOptions = products.map(prod => ({
    value: prod.producto_codigo,
    label: prod.producto_nombre || prod.nombre || prod.producto_codigo,
  }));

  const addRoute = () => {
    setLocalRoutes(prev => {
      // Copiar datos de la última ruta si existe
      const lastRoute = prev.length > 0 ? prev[prev.length - 1] : null;
      
      const newRoute = lastRoute ? {
        id: null,
        ciudad_origen: '', // NO copiar ciudades
        ciudad_destino: '', // NO copiar ciudades
        peso_mercancia: lastRoute.peso_mercancia || '',
        cantidad: lastRoute.cantidad || '',
        tipo_embajale: lastRoute.tipo_embajale || '',
        tipo_producto: lastRoute.tipo_producto || '',
        producto_codigo: lastRoute.producto_codigo || '',
        vehiculo_requerido: lastRoute.vehiculo_requerido || '',
        valor_declarado: lastRoute.valor_declarado || '',
      } : {
        id: null,
        ciudad_origen: '',
        ciudad_destino: '',
        peso_mercancia: '',
        cantidad: '',
        tipo_embajale: '',
        tipo_producto: '',
        vehiculo_requerido: '',
        valor_declarado: '',
      };
      
      console.log('➕ Nueva ruta agregada con datos copiados:', newRoute);
      
      return [...prev, newRoute];
    });
  };

  const removeRoute = (index) => {
    setLocalRoutes(prev => prev.filter((_, i) => i !== index));
  };

  return (
    <Modal onClose={onClose} size="large">
      <div className="p-6">
        <h3 className="text-lg font-semibold mb-4">
          Edit Routes for {clientData?.clientName || 'Client'}
        </h3>

        {loading && <p className="text-sm text-gray-500">Loading routes...</p>}
        {error && <p className="text-sm text-red-500 mb-3">{error}</p>}

        {!loading && (
          <div className="space-y-4 max-h-[60vh] overflow-y-auto">
            {localRoutes.map((route, index) => {
              const selectedOriginCode = resolveCityCode(route.ciudad_origen);
              const selectedDestinationCode = resolveCityCode(route.ciudad_destino);
              
              // EMBALAJE: Usar NOMBRE directamente sin resolución a código
              const packingName = route.empaque || route.tipo_embajale || route.tipo_embalaje || '';
              
              console.log(`🚨 DEBUG RUTA ${index + 1}:`, {
                'route.empaque': route.empaque,
                'route.tipo_embajale': route.tipo_embajale,
                'packingName': packingName
              });
              
              // Crear opción directamente con el nombre, sin buscar código
              const selectedPackingOption = packingName 
                ? { value: packingName, label: packingName }
                : null;
              
              console.log(`🔍 selectedPackingOption:`, selectedPackingOption);
              
              // PRODUCTO: Lógica existente
              const selectedProductCode = resolveProductCode(route.tipo_producto);
              
              console.log(`📋 Ruta ${index + 1} - Valores resueltos:`, {
                origen: { valor: route.ciudad_origen, codigo: selectedOriginCode },
                destino: { valor: route.ciudad_destino, codigo: selectedDestinationCode },
                embalaje: { 
                  'route.empaque': route.empaque, 
                  'route.tipo_embajale': route.tipo_embajale,
                  'packingName': packingName,
                  'selectedPackingCode': selectedPackingCode,
                  'selectedPackingOption': selectedPackingOption
                },
                producto: { valor: route.tipo_producto, codigo: selectedProductCode },
                incluye_tara: route.incluye_tara
              });

              return (
                <div key={index} className="border p-4 rounded-lg bg-gray-50 relative">
                  <div className="flex justify-between items-center mb-3">
                    <h4 className="font-semibold">Route #{index + 1}</h4>
                    {localRoutes.length > 1 && (
                      <button
                        className="text-xs text-red-500 underline"
                        onClick={() => removeRoute(index)}
                      >
                        Remove
                      </button>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    {/* Origen */}
                    <div>
                      <label className="text-xs uppercase text-gray-500">Ciudad Origen</label>
                      <Select
                        classNamePrefix="rs"
                        placeholder="Seleccione origen"
                        options={citiesOptions}
                        value={citiesOptions.find(o => String(o.value) === String(selectedOriginCode)) || null}
                        onChange={(opt) => {
                          const city = cities.find(c => String(c.ciudad_codigo) === String(opt?.value));
                          handleChange(index, 'ciudad_origen', city ? city.ciudad_nombre : '');
                        }}
                        isClearable
                      />
                    </div>

                    {/* Destino */}
                    <div>
                      <label className="text-xs uppercase text-gray-500">Ciudad Destino</label>
                      <Select
                        classNamePrefix="rs"
                        placeholder="Seleccione destino"
                        options={citiesOptions}
                        value={citiesOptions.find(o => String(o.value) === String(selectedDestinationCode)) || null}
                        onChange={(opt) => {
                          const city = cities.find(c => String(c.ciudad_codigo) === String(opt?.value));
                          handleChange(index, 'ciudad_destino', city ? city.ciudad_nombre : '');
                        }}
                        isClearable
                      />
                    </div>

                    {/* Tipo Embalaje (Packing) */}
                    <div>
                      <label className="text-xs uppercase text-gray-500">Tipo Embalaje</label>
                      <Select
                        classNamePrefix="rs"
                        placeholder="Seleccione embalaje"
                        options={packingOptions}
                        value={selectedPackingOption}
                        onChange={(opt) => {
                          const packing = packings.find(p => String(p.Codigo ?? p.codigo ?? p.empaque_id ?? p.id) === String(opt?.value));
                          const packingCode = packing ? (packing.Codigo ?? packing.codigo ?? packing.empaque_id ?? packing.id) : '';
                          const packingName = packing
                            ? (packing.Nombre || packing.nombre || packing.nome || packing.empaque || '')
                            : (opt?.label || opt?.value || '');
                          handleChange(index, 'tipo_embajale', packingCode || packingName);
                          handleChange(index, 'empaque', packingName);
                          handleChange(index, 'empaque_id', packingCode || null);
                        }}
                        isClearable
                      />
                    </div>

                    {/* Tipo Producto (Product) */}
                    <div>
                      <label className="text-xs uppercase text-gray-500">Tipo Producto</label>
                      <Select
                        classNamePrefix="rs"
                        placeholder="Seleccione producto"
                        options={productOptions}
                        value={
                          selectedProductCode
                            ? productOptions.find(o => String(o.value) === String(selectedProductCode)) ||
                              // Si no se encuentra en las opciones, crear opción personalizada
                              { value: selectedProductCode, label: `${selectedProductCode} (personalizado)` }
                            : null
                        }
                        onChange={(opt) => {
                          const product = products.find(p => String(p.producto_codigo) === String(opt?.value));
                          // Si es un producto de la BD, guardar código; si es personalizado, guardar texto
                          handleChange(index, 'tipo_producto', product ? product.producto_codigo : (opt?.value || ''));
                        }}
                        isClearable
                      />
                    </div>

                    <div>
                      <label className="text-xs uppercase text-gray-500">Peso Mercancía</label>
                      <input
                        className="w-full border rounded px-2 py-1 text-sm"
                        type="number"
                        value={route.peso_mercancia || ''}
                        onChange={(e) => handleChange(index, 'peso_mercancia', e.target.value)}
                      />
                    </div>
                    <div>
                      <label className="text-xs uppercase text-gray-500">Cantidad</label>
                      <input
                        className="w-full border rounded px-2 py-1 text-sm"
                        type="number"
                        value={route.cantidad || ''}
                        onChange={(e) => handleChange(index, 'cantidad', e.target.value)}
                      />
                    </div>
                    <div>
                      <label className="text-xs uppercase text-gray-500">
                        Vehículo Requerido
                        {route.peso_mercancia && (
                          <span className="text-green-600 ml-2 text-xs normal-case">
                            (Recomendado: {recommendVehicle(route.peso_mercancia)})
                          </span>
                        )}
                      </label>
                      <Select
                        classNamePrefix="rs"
                        placeholder="Seleccione vehículo"
                        options={[
                          { value: 'CAMIONETA', label: 'CAMIONETA (hasta 2,000 kg)' },
                          { value: 'TURBO', label: 'TURBO (hasta 4,000 kg)' },
                          { value: 'SENCILLO', label: 'SENCILLO (hasta 9,000 kg)' },
                          { value: 'PATINETA2', label: 'PATINETA2 (hasta 20,000 kg)' },
                          { value: 'PATINETA3', label: 'PATINETA3 (hasta 25,000 kg)' },
                          { value: 'TRACTOMULA 2', label: 'TRACTOMULA 2 (hasta 30,000 kg)' },
                          { value: 'TRACTOMULA 3', label: 'TRACTOMULA 3 (hasta 34,000 kg)' },
                        ]}
                        value={
                          route.vehiculo_requerido
                            ? { value: route.vehiculo_requerido, label: route.vehiculo_requerido }
                            : null
                        }
                        onChange={(opt) => handleChange(index, 'vehiculo_requerido', opt?.value || '')}
                        isClearable
                      />
                    </div>
                    <div>
                      <label className="text-xs uppercase text-gray-500">Valor Declarado</label>
                      <input
                        className="w-full border rounded px-2 py-1 text-sm"
                        value={route.valor_declarado || ''}
                        onChange={(e) => handleChange(index, 'valor_declarado', e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        <div className="flex items-center justify-between mt-4">
          <button
            onClick={addRoute}
            className="px-4 py-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700 hover:bg-blue-100 transition-colors flex items-center space-x-2"
            title="Agrega una nueva ruta copiando los datos de la ruta anterior (excepto origen/destino)"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>+ Add Route {localRoutes.length > 0 && '(copiar datos anteriores)'}</span>
          </button>

          <div className="space-x-2">
            <button
              onClick={onClose}
              className="px-4 py-2 border rounded text-sm text-gray-600"
              disabled={saving}
            >
              Cancel
            </button>
            <button
              onClick={handleSave}
              className="px-4 py-2 bg-orange-500 text-white rounded text-sm"
              disabled={saving}
            >
              {saving ? 'Saving...' : 'Save & Continue'}
            </button>
          </div>
        </div>
      </div>
    </Modal>
  );
};

EditRoutesModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  onNext: PropTypes.func.isRequired,
  groupId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  quoteData: PropTypes.array.isRequired,
  setQuoteData: PropTypes.func.isRequired,
  clientData: PropTypes.object,
};

export default EditRoutesModal;