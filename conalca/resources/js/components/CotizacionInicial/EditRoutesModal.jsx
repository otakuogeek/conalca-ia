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
          const routes = data.data.routes;
          console.log(`📦 ${routes.length} ruta(s) encontrada(s)`);
          
          // Debug: verificar tipo_embajale
          routes.forEach((r, i) => {
            console.log(`Ruta ${i + 1} - tipo_embajale:`, r.tipo_embajale);
          });
          
          setLocalRoutes(routes);
          setQuoteData(routes);
        } else {
          console.warn('⚠️ No se encontraron rutas guardadas');
          // Si no hay rutas en BD, usar quoteData pasado como prop
          if (normalizedRoutes.length > 0) {
            console.log('🔄 Usando rutas desde props (recién creadas)');
            setLocalRoutes(normalizedRoutes);
          } else {
            setError('No se encontraron rutas para este grupo');
          }
        }
      } catch (err) {
        console.error('❌ Error cargando rutas:', err);
        setError('Error de red al cargar rutas: ' + err.message);
        // Fallback: usar normalizedRoutes si hay error
        if (normalizedRoutes.length > 0) {
          setLocalRoutes(normalizedRoutes);
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

  // Helpers to preselect if route has code or name
  const resolveCityCode = (routeValue) => {
    if (!routeValue) return '';
    
    // Buscar por código exacto
    const byCode = cities.find(c => String(c.ciudad_codigo) === String(routeValue));
    if (byCode) return byCode.ciudad_codigo;

    // Buscar por nombre normalizado (sin tildes, sin case-sensitive)
    const normalizedRoute = normalizeText(routeValue);
    const byName = cities.find(c => {
      const normalizedCity = normalizeText(c.ciudad_nombre || '');
      return normalizedCity === normalizedRoute;
    });
    
    if (byName) {
      console.log(`✅ Ciudad encontrada: "${routeValue}" → código ${byName.ciudad_codigo} (${byName.ciudad_nombre})`);
      return byName.ciudad_codigo;
    }
    
    console.warn(`⚠️ Ciudad NO encontrada: "${routeValue}" (normalizado: "${normalizedRoute}")`);
    return '';
  };

  const resolvePackingCode = (routeValue) => {
    if (!routeValue) {
      console.log('📦 resolvePackingCode: valor vacío');
      return '';
    }
    
    console.log('🔍 resolvePackingCode:', {
      routeValue,
      packingsLength: packings.length,
      packingsSample: packings.slice(0, 3)
    });
    
    // Buscar por código
    const byCode = packings.find(p => String(p.Codigo) === String(routeValue));
    if (byCode) {
      console.log('✅ Embalaje encontrado por código:', byCode);
      return byCode.Codigo;
    }

    // Buscar por nombre normalizado
    const normalizedRoute = normalizeText(routeValue);
    const byName = packings.find(p => {
      const name = p.Nombre || p.nombre || p.nome || '';
      return normalizeText(name) === normalizedRoute;
    });
    
    if (byName) {
      console.log('✅ Embalaje encontrado por nombre:', byName);
      return byName.Codigo;
    }
    
    console.warn('⚠️ Embalaje NO encontrado para:', routeValue, 'en', packings.length, 'opciones');
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
        setLocalRoutes(prev =>
          prev.map((r, i) => ({
            ...r,
            id: data.data.routes[i]?.id || r.id || null,
          }))
        );
        
        // IMPORTANTE: Actualizar quoteData completo para que PricingModal tenga los datos correctos
        setQuoteData(localRoutes.map((r, i) => ({
          ...r,
          id: data.data.routes[i]?.id || r.id || null,
        })));
        
        console.log('✅ quoteData actualizado con rutas editadas:', localRoutes);
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
    value: p.Codigo,
    label: p.Nombre || p.nombre || p.nome || p.Codigo,
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
              const selectedPackingCode = resolvePackingCode(route.tipo_embajale);
              const selectedProductCode = resolveProductCode(route.tipo_producto);
              
              console.log(`📋 Ruta ${index + 1} - Valores resueltos:`, {
                origen: { valor: route.ciudad_origen, codigo: selectedOriginCode },
                destino: { valor: route.ciudad_destino, codigo: selectedDestinationCode },
                embalaje: { valor: route.tipo_embajale, codigo: selectedPackingCode },
                producto: { valor: route.tipo_producto, codigo: selectedProductCode }
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
                        value={packingOptions.find(o => String(o.value) === String(selectedPackingCode)) || null}
                        onChange={(opt) => {
                          const packing = packings.find(p => String(p.Codigo) === String(opt?.value));
                          handleChange(index, 'tipo_embajale', packing ? packing.Codigo : '');
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