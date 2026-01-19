import React, { useState, useEffect } from 'react';
import PropTypes            from 'prop-types';
import { chatBus }          from './ChatBox';
import AsyncSearchSelect    from '../ui/AsyncSelect';
import {
  searchCiudades,
  searchProductos,
  searchEmpaques,
  searchClases,
  searchCarrocerias
} from '../../api/solicitud';
import {
  FiMapPin,
  FiBox,
  FiPackage,
  FiLayers,
  FiTruck,
  FiCalendar,
  FiTag,
  FiDollarSign,
  FiType,
  FiShield,
  FiEdit,
  FiChevronLeft,
  FiChevronRight
} from 'react-icons/fi';
import { FaWeight } from 'react-icons/fa';

const normalizeCityCode = value =>
  (typeof value === 'string' && /^\d+$/.test(value.trim()))
    ? value.trim()
    : '';

const buildStep2State = (formData = {}, detalle = {}, data = {}) => {
  const flow = formData.operation_flow || data.operation_flow || {};

  return {
    origen        : normalizeCityCode(formData.origen ?? detalle.origen ?? data.origen),
    origen_label           : formData.origen_label           || detalle.origen_label           || data.origen_label || '',
    destino       : normalizeCityCode(formData.destino ?? detalle.destino ?? data.destino),
    destino_label          : formData.destino_label          || detalle.destino_label          || data.destino_label || '',
    lugar_recogida_contenedor: normalizeCityCode(
      formData.lugar_recogida_contenedor ?? detalle.lugar_recogida_contenedor ?? data.lugar_recogida_contenedor
    ),
    lugar_recogida_contenedor_label : formData.lugar_recogida_contenedor_label || detalle.lugar_recogida_contenedor_label || data.lugar_recogida_contenedor_label || '',
    tipo_carga             : formData.tipo_carga             || detalle.tipo_carga             || data.tipo_carga || '',
    cantidad_mercancia     : formData.cantidad_mercancia     || detalle.cantidad_mercancia     || data.cantidad_mercancia || '',
    peso                   : formData.peso                   || detalle.peso                   || data.peso || '',
    valor_mercancia        : formData.valor_mercancia        || detalle.valor_mercancia        || data.valor_mercancia || '',
    producto               : formData.producto               || detalle.producto               || data.producto || '',
    producto_label         : formData.producto_label         || detalle.producto_label         || data.producto_label || '',
    empaque                : formData.empaque                || detalle.empaque                || data.empaque || '',
    empaque_label          : formData.empaque_label          || detalle.empaque_label          || data.empaque_label || '',
    cantidad_vehiculos     : formData.cantidad_vehiculos     || detalle.cantidad_vehiculos     || data.cantidad_vehiculos || '',
    clase_vehiculo         : formData.clase_vehiculo         || detalle.clase_vehiculo         || data.clase_vehiculo || '',
    clase_vehiculo_label   : formData.clase_vehiculo_label   || detalle.clase_vehiculo_label   || data.clase_vehiculo_label || '',
    carroceria             : formData.carroceria             || detalle.carroceria             || data.carroceria || '',
    carroceria_label       : formData.carroceria_label       || detalle.carroceria_label       || data.carroceria_label || '',
    minimo_modelo          : formData.minimo_modelo          || detalle.minimo_modelo          || data.minimo_modelo || '',
    tipo_flete             : formData.tipo_flete             || detalle.tipo_flete             || data.tipo_flete || '',
    flete_conductor        : formData.flete_conductor        || detalle.flete_conductor        || data.flete_conductor || '',
    flete_ministerio       : formData.flete_ministerio       || detalle.flete_ministerio       || data.flete_ministerio || '',
    tipo_tarifa            : formData.tipo_tarifa            || detalle.tipo_tarifa            || data.tipo_tarifa || '',
    tarifa_cliente         : formData.tarifa_cliente         || detalle.tarifa_cliente         || data.tarifa_cliente || '',
    cargue_cuenta_de       : formData.cargue_cuenta_de       || detalle.cargue_cuenta_de       || data.cargue_cuenta_de || '',
    descargue_cuenta_de    : formData.descargue_cuenta_de    || detalle.descargue_cuenta_de    || data.descargue_cuenta_de || '',
    seguro_cuenta_de       : formData.seguro_cuenta_de       || detalle.seguro_cuenta_de       || data.seguro_cuenta_de || '',
    descripcion_mercancia  : formData.descripcion_mercancia  || detalle.descripcion_mercancia  || data.descripcion_mercancia || '',
    kit_seguridad          : formData.kit_seguridad          || detalle.kit_seguridad          || data.kit_seguridad || '',
    sub_cliente            : formData.sub_cliente            || detalle.sub_cliente            || data.sub_cliente || '',
    tipo_remesa_rndc       : formData.tipo_remesa_rndc       || detalle.tipo_remesa_rndc       || data.tipo_remesa_rndc || '',
    operation_flow         : flow
  };
};

const DebugInspector = ({ form, formData, data, show }) => {
  if (!show) return null;

  return (
    <div className="mt-6 rounded-xl border border-red-300 bg-gray-900 text-green-200 text-xs p-4 space-y-3">
      <h3 className="text-red-300 font-semibold text-sm">🪲 Debug: Step1 snapshot</h3>

      <div>
        <p className="text-red-200 font-medium">form (local state)</p>
        <pre className="whitespace-pre-wrap break-words">
          {JSON.stringify(form, null, 2)}
        </pre>
      </div>

      <div>
        <p className="text-red-200 font-medium">formData (wizard cache)</p>
        <pre className="whitespace-pre-wrap break-words">
          {JSON.stringify(formData, null, 2)}
        </pre>
      </div>

      <div>
        <p className="text-red-200 font-medium">data (prefill/localData)</p>
        <pre className="whitespace-pre-wrap break-words">
          {JSON.stringify(data, null, 2)}
        </pre>
      </div>
    </div>
  );
};

export default function Step2({ data = {}, formData = {}, onNext, onPrev, loading }) {

  const d = data.detalle || {};
  
  // Detectar tipo de operación
  // const operationType = data.operation_flow?.type || data.tipo_operacion || '';
  const operationType =
    formData.operation_flow?.type ??
    formData.tipo_operacion ??
    data.operation_flow?.type ??
    data.tipo_operacion ??
    '';
  const isImportExport = operationType === 'IMPORTACION' || operationType === 'EXPORTACION';

  // const [form, setForm] = useState({
  //   /* ciudades */
  //   origen                 : formData.origen                 || d.origen  || '',
  //   origen_label           : formData.origen_label           || d.origen_label  || '',
  //   destino                : formData.destino                || d.destino || '',
  //   destino_label          : formData.destino_label          || d.destino_label || '',
    
  //   // NUEVO: Campo específico para exportaciones
  //   lugar_recogida_contenedor: formData.lugar_recogida_contenedor || d.lugar_recogida_contenedor || '',
  //   lugar_recogida_contenedor_label: formData.lugar_recogida_contenedor_label || d.lugar_recogida_contenedor_label || '',
    
  //   // NUEVO: Tipo de carga para import/export
  //   tipo_carga             : formData.tipo_carga             || d.tipo_carga || '',
    
  //   // Campos existentes para distribución
  //   cantidad_mercancia     : formData.cantidad_mercancia     || d.cantidad_mercancia     || '',
  //   peso                   : formData.peso                   || d.peso                   || '',
  //   valor_mercancia        : formData.valor_mercancia        || d.valor_mercancia        || '',

  //   /* catálogo producto / empaque */
  //   producto               : formData.producto               || d.producto        || '',
  //   producto_label         : formData.producto_label         || d.producto_label  || '',
  //   empaque                : formData.empaque                || d.empaque         || '',
  //   empaque_label          : formData.empaque_label          || d.empaque_label   || '',

  //   cantidad_vehiculos     : formData.cantidad_vehiculos     || d.cantidad_vehiculos     || '',

  //   /* clase y carrocería de vehículo */
  //   clase_vehiculo         : formData.clase_vehiculo         || d.clase_vehiculo        || '',
  //   clase_vehiculo_label   : formData.clase_vehiculo_label   || d.clase_vehiculo_label  || '',
  //   carroceria             : formData.carroceria             || d.carroceria            || '',
  //   carroceria_label       : formData.carroceria_label       || d.carroceria_label      || '',

  //   /* demás campos sin cambios */
  //   minimo_modelo          : formData.minimo_modelo          || d.minimo_modelo          || '',
  //   tipo_flete             : formData.tipo_flete             || d.tipo_flete             || '',
  //   flete_conductor        : formData.flete_conductor        || d.flete_conductor        || '',
  //   flete_ministerio       : formData.flete_ministerio       || d.flete_ministerio       || '',
  //   tipo_tarifa            : formData.tipo_tarifa            || d.tipo_tarifa            || '',
  //   tarifa_cliente         : formData.tarifa_cliente         || d.tarifa_cliente         || '',
  //   cargue_cuenta_de       : formData.cargue_cuenta_de       || d.cargue_cuenta_de       || '',
  //   descargue_cuenta_de    : formData.descargue_cuenta_de    || d.descargue_cuenta_de    || '',
  //   seguro_cuenta_de       : formData.seguro_cuenta_de       || d.seguro_cuenta_de       || '',
  //   descripcion_mercancia  : formData.descripcion_mercancia  || d.descripcion_mercancia  || '',
  //   kit_seguridad          : formData.kit_seguridad          || d.kit_seguridad          || '',
  //   sub_cliente            : formData.sub_cliente            || d.sub_cliente            || '',
  //   tipo_remesa_rndc       : formData.tipo_remesa_rndc       || d.tipo_remesa_rndc       || ''
  // });
  const [showDebug, setShowDebug] = useState(false);
  const [form, setForm] = useState(buildStep2State(formData, d, data));
  useEffect(() => {
    setForm(buildStep2State(formData, data.detalle || {}, data));
  }, [formData, data]);
  
  /* ------------------------------------------------------------------ */
  /*  VALIDACIÓN                                                        */
  /* ------------------------------------------------------------------ */
  const [submitted, setSubmitted] = useState(false);
  const isEmpty  = v => v === '' || v === null || v === undefined;
  const [debugLog, setDebugLog] = useState([]);
  
  /* campos obligatorios según tipo de operación */
  const getRequiredFields = () => {
    if (isImportExport) {
      // Para importaciones y exportaciones
      const baseFields = ['origen', 'destino', 'tipo_carga'];
      
      // Si es exportación, agregar lugar de recogida del contenedor
      if (operationType === 'EXPORTACION') {
        baseFields.push('lugar_recogida_contenedor');
      }
      
      return baseFields;
    } else {
      // Para distribución (campos originales)
      return [
        'origen',
        'destino',
        'cantidad_mercancia',
        'peso',
        'valor_mercancia',
        'producto',
        'empaque',
        'cantidad_vehiculos',
        'clase_vehiculo',
        'carroceria',
        'minimo_modelo',
        'tipo_flete',
        'flete_conductor',
        'flete_ministerio',
        'tipo_tarifa',
        'tarifa_cliente',
        'cargue_cuenta_de',
        'descargue_cuenta_de',
        'seguro_cuenta_de',
        'kit_seguridad',
        'tipo_remesa_rndc',
        'descripcion_mercancia'
      ];
    }
  };

  const required = getRequiredFields();

  /* ¿el campo tiene error? (solo después de enviar) */
  const hasError = name => submitted && isEmpty(form[name]);

  // useEffect(() => {
  //   const labelReset = {
  //     origen: 'origen_label',
  //     destino: 'destino_label',
  //     lugar_recogida_contenedor: 'lugar_recogida_contenedor_label',
  //     producto: 'producto_label',
  //     empaque: 'empaque_label',
  //     clase_vehiculo: 'clase_vehiculo_label',
  //     carroceria: 'carroceria_label'
  //   };

  //   const fill = (field, value) => {
  //     setForm(prev => {
  //       // Ignore null/undefined/""
  //       if (value === null || value === undefined || value === '' || value === 'null') {
  //         return prev;
  //       }

  //       const next = { ...prev };

  //       if (['origen', 'destino', 'lugar_recogida_contenedor'].includes(field)) {
  //         const normalized = normalizeCityCode(String(value));
  //         if (!normalized) return prev;         // don’t wipe the city if the AI sends garbage

  //         next[field] = normalized;
  //         next[`${field}_label`] = '';          // force AsyncSelect to refresh label
  //       } else {
  //         next[field] = value;
  //         if (labelReset[field]) next[labelReset[field]] = '';
  //       }

  //       return next;
  //     });
  //   };

  //   chatBus.on('fill-field', fill);
  //   return () => chatBus.off('fill-field', fill);
  // }, []);

  useEffect(() => {
    const labelReset = {
      origen: 'origen_label',
      destino: 'destino_label',
      lugar_recogida_contenedor: 'lugar_recogida_contenedor_label',
      producto: 'producto_label',
      empaque: 'empaque_label',
      clase_vehiculo: 'clase_vehiculo_label',
      carroceria: 'carroceria_label'
    };

    const resolveCity = async (raw) => {
      const val = String(raw || '').trim();
      if (!val) return null;

      // If already numeric, return as code
      if (/^\d+$/.test(val)) {
        return { code: val, label: '' };
      }

      // Otherwise search by name
      try {
        const { data } = await searchCiudades(val);
        if (data && data.length) {
          const hit = data[0];
          const code = String(hit.ciudad_codigodane);
          const label = `${hit.ciudad_codigodane} – ${hit.ciudad_nombre}${
            hit.municipio_nombre ? ` – ${hit.municipio_nombre}` : ''
          }`;
          return { code, label };
        }
      } catch (err) {
        console.error('City resolution error:', err);
      }
      return null;
    };

    const handleFill = (field, value) => {
      (async () => {
        // Skip empty/null
        if (value === null || value === undefined || value === '' || value === 'null') return;

        // City fields: try to resolve name -> code
        if (['origen', 'destino', 'lugar_recogida_contenedor'].includes(field)) {
          const resolved = await resolveCity(value);
          if (!resolved) return;
          setForm((prev) => ({
            ...prev,
            [field]: resolved.code,
            [`${field}_label`]: resolved.label
          }));
          return;
        }

        // Default behavior for other fields
        setForm((prev) => {
          const next = { ...prev, [field]: value };
          if (labelReset[field]) next[labelReset[field]] = '';
          return next;
        });
      })();
    };

    chatBus.on('fill-field', handleFill);
    return () => chatBus.off('fill-field', handleFill);
  }, []);

  /* helpers asincrónicos  ─────────────────────────── */
  const getCityName = async code => {
    if (!code) return '';
    const { data } = await searchCiudades(code);
    const f = data.find(c => String(c.ciudad_codigodane) === String(code));
    return f
      ? `${f.ciudad_codigodane} – ${f.ciudad_nombre}${
          f.municipio_nombre ? ` – ${f.municipio_nombre}` : ''
        }`
      : '';
  };

  const getProductName = async code => {
    if (!code) return '';
    const { data } = await searchProductos(code);
    const f = data.find(p => String(p.producto_codigo) === String(code));
    return f ? f.producto_nombre : '';
  };

  const getPackingName = async code => {
    if (!code) return '';
    const { data } = await searchEmpaques(code);
    // 🔧 FIX: Buscar por Codigo (que ahora es el ID)
    const f = data.find(e => String(e.Codigo) === String(code));
    return f ? f.Nombre : '';
  };

  const getClassName = async code => {
    if (!code) return '';
    const { data } = await searchClases(code);
    const f = data.find(v => String(v.Codigo) === String(code));
    return f ? f.Nombre : '';
  };

  const getBodyworkName = async code => {
    if (!code) return '';
    const { data } = await searchCarrocerias(code);
    const f = data.find(c => String(c.Codigo) === String(code));
    return f ? f.Nombre : '';
  };

  /* origen / destino */
  useEffect(() => {
    if (form.origen && !form.origen_label)
      getCityName(form.origen).then(n => n && setForm(p => ({ ...p, origen_label: n })));

    if (form.destino && !form.destino_label)
      getCityName(form.destino).then(n => n && setForm(p => ({ ...p, destino_label: n })));
  }, [form.origen, form.origen_label, form.destino, form.destino_label]);

  /* lugar recogida contenedor (para exportaciones) */
  useEffect(() => {
    if (form.lugar_recogida_contenedor && !form.lugar_recogida_contenedor_label)
      getCityName(form.lugar_recogida_contenedor).then(n => n && setForm(p => ({ ...p, lugar_recogida_contenedor_label: n })));
  }, [form.lugar_recogida_contenedor, form.lugar_recogida_contenedor_label]);

  /* producto */
  useEffect(() => {
    if (form.producto && !form.producto_label)
      getProductName(form.producto).then(n => n && setForm(p => ({ ...p, producto_label: n })));
  }, [form.producto, form.producto_label]);

  /* empaque */
  useEffect(() => {
    if (form.empaque && !form.empaque_label)
      getPackingName(form.empaque).then(n => n && setForm(p => ({ ...p, empaque_label: n })));
  }, [form.empaque, form.empaque_label]);

  /* clase vehículo */
  useEffect(() => {
    if (form.clase_vehiculo && !form.clase_vehiculo_label)
      getClassName(form.clase_vehiculo).then(n => n && setForm(p => ({ ...p, clase_vehiculo_label: n })));
  }, [form.clase_vehiculo, form.clase_vehiculo_label]);

  /* carrocería */
  useEffect(() => {
    if (form.carroceria && !form.carroceria_label)
      getBodyworkName(form.carroceria).then(n => n && setForm(p => ({ ...p, carroceria_label: n })));
  }, [form.carroceria, form.carroceria_label]);

  const change = e => setForm({ ...form, [e.target.name]: e.target.value });

  const submit = e => {
    e.preventDefault();
    setSubmitted(true);

    const faltantes = required.filter(f => isEmpty(form[f]));
    if (faltantes.length) return;        // hay campos vacíos ⇒ no avanzamos

    /* armamos el payload sin los "label" ---------------------------- */
    const {
      origen_label,
      destino_label,
      lugar_recogida_contenedor_label,
      producto_label,
      empaque_label,
      clase_vehiculo_label,
      carroceria_label,
      ...payload
    } = form;

    // Mantener información del flujo de operación
    payload.operation_flow = data.operation_flow || {
      type: operationType,
      isImportExport: isImportExport
    };

    onNext(payload);
  };

  return (
    <form onSubmit={submit} className="space-y-10">
      {/* ───── Título ───── */}
      <h2 className="text-2xl font-semibold flex items-center gap-2 text-gray-800">
        <FiPackage className="text-orange-500" />
        Paso 2 – Detalle del servicio
      </h2>

      {/* ───── GRID PRINCIPAL ───── */}
      <div className="grid md:grid-cols-2 gap-8">

        {/* ───── Ciudad de origen ───── */}
    
        <AsyncSearchSelect
          id="origen"
          load={searchCiudades}
          getOpt={c => ({
            value : c.ciudad_codigodane,
            label : `${c.ciudad_codigodane} – ${c.ciudad_nombre}${
              c.municipio_nombre ? ` – ${c.municipio_nombre}` : ''
            }`
          })}
          value={
            form.origen
              ? { value: form.origen, label: form.origen_label || form.origen }
              : null
          }
          onChange={opt =>
            setForm(p => ({
              ...p,
              origen       : opt?.value || '',
              origen_label : opt?.label || ''
            }))
          }
        />

        {/* ───── Ciudad de destino ───── */}
       
        <AsyncSearchSelect
          id="destino"
          load={searchCiudades}
          getOpt={c => ({
            value : c.ciudad_codigodane,
            label : `${c.ciudad_codigodane} – ${c.ciudad_nombre}${
              c.municipio_nombre ? ` – ${c.municipio_nombre}` : ''
            }`
          })}
          value={
            form.destino
              ? { value: form.destino, label: form.destino_label || form.destino }
              : null
          }
          onChange={opt =>
            setForm(p => ({
              ...p,
              destino       : opt?.value || '',
              destino_label : opt?.label || ''
            }))
          }
        />

        {/* ───── Campo específico para EXPORTACIONES: Lugar de recogida del contenedor ───── */}
        {operationType === 'EXPORTACION' && (
          <div className={`md:col-span-2 ${hasError('lugar_recogida_contenedor') ? 'border border-red-500 rounded p-1' : ''}`}>
            <label htmlFor="lugar_recogida_contenedor" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
              <FiMapPin className="text-green-500" /> Donde recoge el contenedor vacío
            </label>
            <AsyncSearchSelect
              id="lugar_recogida_contenedor"
              load={searchCiudades}
              getOpt={c => ({
                value : c.ciudad_codigodane,
                label : `${c.ciudad_codigodane} – ${c.ciudad_nombre}${
                  c.municipio_nombre ? ` – ${c.municipio_nombre}` : ''
                }`
              })}
              value={
                form.lugar_recogida_contenedor
                  ? {
                      value: form.lugar_recogida_contenedor,
                      label: form.lugar_recogida_contenedor_label || form.lugar_recogida_contenedor
                    }
                  : null
              }
              onChange={opt =>
                setForm(p => ({
                  ...p,
                  lugar_recogida_contenedor       : opt?.value || '',
                  lugar_recogida_contenedor_label : opt?.label || ''
                }))
              }
            />
          </div>
        )}

        {/* ───── Campo específico para IMPORTACIONES/EXPORTACIONES: Tipo de carga ───── */}
        {isImportExport && (
          <div className={`md:col-span-2 ${hasError('tipo_carga') ? 'border border-red-500 rounded p-1' : ''}`}>
            <label htmlFor="tipo_carga" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
              <FiBox className={operationType === 'IMPORTACION' ? 'text-blue-500' : 'text-green-500'} /> 
              Tipo de carga
            </label>
            <div className="relative">
              <FiBox className={`absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none ${operationType === 'IMPORTACION' ? 'text-blue-400' : 'text-green-400'}`} />
              <select
                id="tipo_carga"
                name="tipo_carga"
                value={form.tipo_carga}
                onChange={change}
                className={`
                  w-full py-2 pl-11 pr-3 rounded-md
                  border ${hasError('tipo_carga') ? 'border-red-500' : 'border-gray-300'}
                  focus:ring-2 focus:ring-orange-500 focus:border-orange-500
                `}
                required
              >
                <option value="">— Seleccionar tipo de carga —</option>
                <option value="CARGA_SUELTA">Carga suelta</option>
                <option value="CARGA_CONTENEDORIZADA">Carga contenedorizada</option>
              </select>
            </div>
            {form.tipo_carga && (
              <div className={`mt-2 p-3 rounded-lg text-xs ${
                form.tipo_carga === 'CARGA_SUELTA' 
                  ? 'bg-yellow-50 text-yellow-700 border border-yellow-200'
                  : 'bg-blue-50 text-blue-700 border border-blue-200'
              }`}>
                {form.tipo_carga === 'CARGA_SUELTA' 
                  ? '⚠️ Carga suelta seleccionada: No se generarán valores de devolución automáticamente.'
                  : '📦 Carga contenedorizada seleccionada: Se aplicarán valores de devolución según parametrización del módulo de Pricing.'
                }
              </div>
            )}
          </div>
        )}

     

        {/* ═══════════════════════════════════════════════════════════════════════ */}
        {/* CAMPOS ESPECÍFICOS PARA DISTRIBUCIÓN (OCULTOS EN IMPORT/EXPORT)        */}
        {/* ═══════════════════════════════════════════════════════════════════════ */}
        {!isImportExport && (
          <>
            {/* ───── Cantidad mercancía ───── */}
            <div>
              <label htmlFor="cantidad_mercancia" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
                <FiBox className="text-orange-500" /> Cantidad de mercancía
              </label>
              <input
                id="cantidad_mercancia"
                name="cantidad_mercancia"
                type="number"
                value={form.cantidad_mercancia}
                onChange={change}
                className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
                  border ${hasError('cantidad_mercancia') ? 'border-red-500' : 'border-orange-300'}`}
              />
            </div>

        {/* ───── Peso ───── */}
        <div>
          <label htmlFor="peso" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FaWeight className="text-orange-500" /> Peso (kg)
          </label>
          <input
            id="peso"
            name="peso"
            type="number"
            value={form.peso.replace(/[^\d.-]/g, '')}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('peso') ? 'border-red-500' : 'border-orange-300'}`}
          />
        </div>

        {/* ───── Valor mercancía ───── */}
        <div>
          <label htmlFor="valor_mercancia" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiDollarSign className="text-orange-500" /> Valor de la mercancía
          </label>
          <input
            id="valor_mercancia"
            name="valor_mercancia"
            type="number"
            value={form.valor_mercancia}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('valor_mercancia') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>

        {/* ───── Producto ───── */}
        <div className={hasError('producto') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="producto" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiPackage className="text-orange-500" /> Producto
          </label>
          <AsyncSearchSelect
            id="producto"
            load={searchProductos}
            getOpt={p => ({ value: p.producto_codigo, label: p.producto_nombre })}
            value={
              form.producto
                ? { value: form.producto, label: form.producto_label || form.producto }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                producto: opt?.value || '',
                producto_label: opt?.label || ''
              }))
            }
          />
        </div>

        {/* ───── Empaque ───── */}
        <div className={hasError('empaque') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="empaque" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiLayers className="text-orange-500" /> Empaque
          </label>
          <AsyncSearchSelect
            id="empaque"
            load={searchEmpaques}
            getOpt={e => ({ value: e.Codigo, label: e.Nombre })}
            value={
              form.empaque
                ? { value: form.empaque, label: form.empaque_label || form.empaque }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                empaque: opt?.value || '',
                empaque_label: opt?.label || ''
              }))
            }
          />
        </div>

        {/* ───── Cantidad vehículos ───── */}
        <div>
          <label htmlFor="cantidad_vehiculos" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiTruck className="text-orange-500" /> Cantidad de vehículos
          </label>
          <input
            id="cantidad_vehiculos"
            name="cantidad_vehiculos"
            type="number"
            value={form.cantidad_vehiculos}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('cantidad_vehiculos') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>

        {/* ───── Clase de vehículo ───── */}
        <div className={hasError('clase_vehiculo') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="clase_vehiculo" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiTruck className="text-orange-500" /> Clase de vehículo
          </label>
          <AsyncSearchSelect
            id="clase_vehiculo"
            load={searchClases}
            getOpt={v => ({ value: v.Codigo, label: v.Nombre })}
            value={
              form.clase_vehiculo
                ? { value: form.clase_vehiculo, label: form.clase_vehiculo_label || form.clase_vehiculo }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                clase_vehiculo: opt?.value || '',
                clase_vehiculo_label: opt?.label || ''
              }))
            }
          />
        </div>

        {/* ───── Carrocería ───── */}
        <div className={hasError('carroceria') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="carroceria" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiTruck className="text-orange-500" /> Carrocería
          </label>
          <AsyncSearchSelect
            id="carroceria"
            load={searchCarrocerias}
            getOpt={c => ({ value: c.Codigo, label: c.Nombre })}
            value={
              form.carroceria
                ? { value: form.carroceria, label: form.carroceria_label || form.carroceria }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                carroceria: opt?.value || '',
                carroceria_label: opt?.label || ''
              }))
            }
          />
        </div>

        {/* ───── Mínimo modelo ───── */}
        <div>
          <label htmlFor="minimo_modelo" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiCalendar className="text-orange-500" /> Mínimo modelo
          </label>
          <input
            id="minimo_modelo"
            name="minimo_modelo"
            type="number"
            value={form.minimo_modelo}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('minimo_modelo') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>

        {/* ───── Tipo de flete ───── */}
        <div>
          <label htmlFor="tipo_flete" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiTag className="text-orange-500" /> Tipo de flete
          </label>
          <select
            id="tipo_flete"
            name="tipo_flete"
            value={form.tipo_flete}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('tipo_flete') ? 'border-red-500' : 'border-orange-300'}`}
          >
            <option value="">— seleccionar —</option>
            <option>CARGA SUELTA</option>
            <option>CUPO</option>
            <option>CONSOLIDADO</option>
            <option>EXPRESO</option>
            <option>GALON</option>
            <option>VAN</option>
            <option>CONTENEDOR</option>
          </select>
        </div>

        {/* ───── Flete conductor ───── */}
        <div>
          <label htmlFor="flete_conductor" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiDollarSign className="text-orange-500" /> Flete conductor
          </label>
          <input
            id="flete_conductor"
            name="flete_conductor"
            type="number"
            value={form.flete_conductor}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('flete_conductor') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>

        {/* ───── Flete ministerio ───── */}
        <div>
          <label htmlFor="flete_ministerio" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiDollarSign className="text-orange-500" /> Flete ministerio
          </label>
          <input
            id="flete_ministerio"
            name="flete_ministerio"
            type="number"
            value={form.flete_ministerio}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('flete_ministerio') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>

        {/* ───── Tipo tarifa ───── */}
        <div>
          <label htmlFor="tipo_tarifa" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiTag className="text-orange-500" /> Tipo de tarifa
          </label>
          <select
            id="tipo_tarifa"
            name="tipo_tarifa"
            value={form.tipo_tarifa}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('tipo_tarifa') ? 'border-red-500' : 'border-orange-300'}`}
          >
            <option value="">— seleccionar —</option>
            <option>GENERAL</option>
            <option>PESO</option>
            <option>GALON</option>
          </select>
        </div>

        {/* ───── Cargue cuenta de ───── */}
        <div>
          <label htmlFor="cargue_cuenta_de" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiType className="text-orange-500" /> Cargue por cuenta de
          </label>
          <select
            id="cargue_cuenta_de"
            name="cargue_cuenta_de"
            value={form.cargue_cuenta_de}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('cargue_cuenta_de') ? 'border-red-500' : 'border-orange-300'}`}
          >
            <option value="">— seleccionar —</option>
            <option>CLIENTE</option>
            <option>EMPRESA</option>
            <option>DESTINATARIO</option>
          </select>
        </div>

        {/* ───── Descargue cuenta de ───── */}
        <div>
          <label htmlFor="descargue_cuenta_de" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiType className="text-orange-500" /> Descargue por cuenta de
          </label>
          <select
            id="descargue_cuenta_de"
            name="descargue_cuenta_de"
            value={form.descargue_cuenta_de}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('descargue_cuenta_de') ? 'border-red-500' : 'border-orange-300'}`}
          >
            <option value="">— seleccionar —</option>
            <option>CLIENTE</option>
            <option>EMPRESA</option>
            <option>DESTINATARIO</option>
          </select>
        </div>

        {/* ───── Seguro cuenta de ───── */}
        <div>
          <label htmlFor="seguro_cuenta_de" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiShield className="text-orange-500" /> Seguro por cuenta de
          </label>
          <select
            id="seguro_cuenta_de"
            name="seguro_cuenta_de"
            value={form.seguro_cuenta_de}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('seguro_cuenta_de') ? 'border-red-500' : 'border-orange-300'}`}
            required
          >
            <option value="">— seleccionar —</option>
            <option>CLIENTE</option>
            <option>EMPRESA</option>
          </select>
        </div>

        {/* ───── Kit seguridad ───── */}
        <div>
          <label htmlFor="kit_seguridad" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiShield className="text-orange-500" /> Kit de seguridad
          </label>
          <select
            id="kit_seguridad"
            name="kit_seguridad"
            value={form.kit_seguridad}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('kit_seguridad') ? 'border-red-500' : 'border-orange-300'}`}
            required
          >
            <option value="">— seleccionar —</option>
            <option>SI</option>
            <option>NO</option>
          </select>
        </div>

        {/* ───── Tipo remesa RNDC ───── */}
        <div>
          <label htmlFor="tipo_remesa_rndc" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiPackage className="text-orange-500" /> Tipo de remesa RNDC
          </label>
          <select
            id="tipo_remesa_rndc"
            name="tipo_remesa_rndc"
            value={form.tipo_remesa_rndc}
            onChange={change}
            className={`w-full rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('tipo_remesa_rndc') ? 'border-red-500' : 'border-orange-300'}`}
            required
          >
            <option value="">— seleccionar —</option>
            <option>REMESA GENERAL</option>
            <option>CONTENEDOR CARGADO</option>
            <option>CONTENEDOR VACIO</option>
          </select>
        </div>

        {/* ───── Tarifa cliente ───── */}
        <div>
          <label htmlFor="tarifa_cliente" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiDollarSign className="text-orange-500" /> Tarifa cliente
          </label>
          <input
            id="tarifa_cliente"
            name="tarifa_cliente"
            type="number"
            value={form.tarifa_cliente}
            onChange={change}
            className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
              border ${hasError('tarifa_cliente') ? 'border-red-500' : 'border-orange-300'}`}
            required
          />
        </div>
          </>
        )}

      </div>{/* grid end */}

      {/* ───── Descripción mercancía ───── */}
      <div>
        <label htmlFor="descripcion_mercancia" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
          <FiEdit className="text-orange-500" /> Descripción de la mercancía
        </label>
        <textarea
          id="descripcion_mercancia"
          name="descripcion_mercancia"
          value={form.descripcion_mercancia}
          onChange={change}
          rows={3}
          className={`w-full rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500
            border ${hasError('descripcion_mercancia') ? 'border-red-500' : 'border-orange-300'}`}
        />
      </div>

      {/* ───── Botones navegación ───── */}
      <div className="flex justify-between">
        <button
          type="button"
          onClick={() => onPrev(form)}
          className="flex items-center gap-2 px-5 py-2 border border-orange-500 text-orange-600 rounded hover:bg-orange-50"
        >
          <FiChevronLeft /> Atrás
        </button>

        <button
          type="submit"
          disabled={loading}
          className="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-8 py-2 rounded disabled:opacity-50"
        >
          {loading ? 'Guardando…' : <>Siguiente <FiChevronRight /></>}
        </button>
      </div>
      {/* ────────── Debug tools ────────── */}
      {/* <div className="pt-4 border-t border-dashed border-gray-200">
        <button
          type="button"
          onClick={() => setShowDebug(v => !v)}
          className="text-xs uppercase tracking-wide text-red-500 border border-red-300 px-3 py-1 rounded-md hover:bg-red-50"
        >
          {showDebug ? 'Hide debug snapshot' : 'Show debug snapshot'}
        </button>

        <DebugInspector
          show={showDebug}
          form={form}
          formData={formData}
          data={data}
        />
      </div> */}
    </form>
  );
}

Step2.propTypes = {
  data    : PropTypes.object,
  onNext  : PropTypes.func.isRequired,
  onPrev  : PropTypes.func.isRequired,
  loading : PropTypes.bool
};