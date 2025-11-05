import React, { useState, useEffect } from 'react';
import PropTypes                      from 'prop-types';
import { chatBus }                    from './ChatBox';
import AsyncSearchSelect              from '../ui/AsyncSelect';
import {
  searchClientes,
  searchCiudades,
  searchVendedores
} from '../../api/solicitud';
import {
  FiNavigation,
  FiDollarSign,
  FiPhoneCall,
  FiTruck,
  FiClipboard,
  FiFileText,
  FiMapPin,
  FiUser,
  FiLayers,
  FiUsers,
  FiArrowRight,
  FiLoader
} from 'react-icons/fi';

export default function Step1({ data = {}, formData = {}, onNext, loading }) {
  const [form, setForm] = useState({
    tipo_viaje            : formData.tipo_viaje            || data.tipo_viaje            || '',
    moneda                : formData.moneda                || data.moneda                || '',
    fuente_solicitud      : formData.fuente_solicitud      || data.fuente_solicitud      || '',
    condicion_despacho    : formData.condicion_despacho    || data.condicion_despacho    || '',
    condicion_facturacion : formData.condicion_facturacion || data.condicion_facturacion || '',
    /* combos dinámicos -------------------------------- */
    ciudad_facturacion        : formData.ciudad_facturacion        || data.ciudad_facturacion        || '',
    ciudad_facturacion_label  : formData.ciudad_facturacion_label  || data.ciudad_facturacion_label  || '',
    vendedor                  : formData.vendedor                  || data.vendedor                  || '',
    vendedor_label            : formData.vendedor_label            || data.vendedor_label            || '',
    cliente_codigo            : formData.cliente_codigo            || data.cliente_codigo            || '',
    cliente_nombre            : formData.cliente_nombre            || data.cliente_nombre            || '',
    /* -------------------------------------------------- */
    tipo_operacion         : formData.tipo_operacion        || data.tipo_operacion        || '',
    centro_costo_despacho  : formData.centro_costo_despacho || data.centro_costo_despacho || ''
  });

  // Estado para manejar la validación de tipo de operación
  const [operationType, setOperationType] = useState(formData.tipo_operacion || data.tipo_operacion || '');
  const [showOperationInfo, setShowOperationInfo] = useState(false);
  /* ------------------------------------------------------------------ */
  /*  VALIDACIÓN                                                        */
  /* ------------------------------------------------------------------ */
  const [submitted, setSubmitted] = useState(false);

  /* un value se considera “vacío” si es null, undefined o string vacío */
  const isEmpty  = v => v === null || v === undefined || v === '';

  /* listado de TODOS los campos obligatorios */
  const required = [
    'tipo_viaje',
    'moneda',
    'fuente_solicitud',
    'tipo_operacion',
    'condicion_despacho',
    'condicion_facturacion',
    'ciudad_facturacion',
    'vendedor',
    'centro_costo_despacho',
    'cliente_codigo'
  ];

  /* ¿el campo está en error?  (solo después de intentar enviar) */
  const hasError = name => submitted && isEmpty(form[name]);

  /* log de depuración */
  useEffect(() => console.log('[Step1 state]', form), [form]);

  /* Inicializar estado de tipo de operación */
  useEffect(() => {
    if (data.tipo_operacion) {
      setOperationType(data.tipo_operacion);
      setShowOperationInfo(data.tipo_operacion === 'IMPORTACION' || data.tipo_operacion === 'EXPORTACION');
    }
  }, [data.tipo_operacion]);

  /* chat → auto-fill */
  useEffect(() => {
    const fill = async (field, value) => {
      console.log('📝 Step1 recibió fill-field:', field, '=', value);
      
      // Si es vendedor y solo viene el código, buscar el label automáticamente
      if (field === 'vendedor' && value && !form.vendedor_label) {
        try {
          const label = await fetchSellerLabel(value);
          setForm(prev => ({ 
            ...prev, 
            [field]: value,
            vendedor_label: label 
          }));
        } catch (error) {
          console.error('Error buscando label para vendedor:', error);
          setForm(prev => ({ ...prev, [field]: value }));
        }
      }
      // Si es ciudad y solo viene el código, buscar el label automáticamente  
      else if (field === 'ciudad_facturacion' && value && !form.ciudad_facturacion_label) {
        try {
          const label = await fetchCityLabel(value);
          setForm(prev => ({ 
            ...prev, 
            [field]: value,
            ciudad_facturacion_label: label 
          }));
        } catch (error) {
          console.error('Error buscando label para ciudad:', error);
          setForm(prev => ({ ...prev, [field]: value }));
        }
      }
      // Si es cliente y solo viene el código, buscar el label automáticamente
      else if (field === 'cliente_codigo' && value && !form.cliente_nombre) {
        try {
          const label = await fetchClientLabel(value);
          setForm(prev => ({ 
            ...prev, 
            [field]: value,
            cliente_nombre: label 
          }));
        } catch (error) {
          console.error('Error buscando label para cliente:', error);
          setForm(prev => ({ ...prev, [field]: value }));
        }
      }
      // Para otros campos, actualizar directamente
      else {
        setForm(prev => ({ ...prev, [field]: value }));
      }
    };
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  const change = e => {
    const { name, value } = e.target;
    setForm({ ...form, [name]: value });
    
    // Manejar cambio de tipo de operación
    if (name === 'tipo_operacion') {
      setOperationType(value);
      setShowOperationInfo(value === 'IMPORTACION' || value === 'EXPORTACION');
    }
  };

  const submit = e => {
    e.preventDefault();
    setSubmitted(true);                     // ← activamos la validación

    /* ¿hay campos obligatorios vacíos? */
    const faltantes = required.filter(f => isEmpty(form[f]));
    if (faltantes.length) return;           // -> no enviamos nada

    /* payload solo con los códigos  ----------------------------- */
    const {
      ciudad_facturacion_label,
      vendedor_label,
      cliente_nombre,
      ...payload
    } = form;
    
    // Agregar información del tipo de operación para los siguientes pasos
    payload.operation_flow = {
      type: form.tipo_operacion,
      isImportExport: form.tipo_operacion === 'IMPORTACION' || form.tipo_operacion === 'EXPORTACION'
    };
    
    onNext(payload);
  };

  /* Busca ciudad por código DANE */
  const fetchCityLabel = async code => {
    if (!code) return '';
    try {
      const { data } = await searchCiudades(code);
      const found = data.find(c => String(c.ciudad_codigodane) === String(code));
      return found ? found.ciudad_nombre : '';
    } catch { return ''; }
  };

  /* Busca vendedor por documento/código */
  const fetchSellerLabel = async doc => {
    if (!doc) return '';
    try {
      const { data } = await searchVendedores(doc);
      const f = data.find(v => String(v.Documento) === String(doc));
      return f ? `${f.Nombre} (${f.Documento})` : '';
    } catch { return ''; }
  };

  /* Busca cliente por código */
  const fetchClientLabel = async code => {
    if (!code) return '';
    try {
      const { data } = await searchClientes(code);
      const f = data.find(c => String(c.codigo) === String(code));
      return f ? `${f.cliente} (${f.codigo})` : '';
    } catch { return ''; }
  };

  /* Si llega un código DANE sin label ⇒ buscamos el nombre */
  useEffect(() => {
    if (form.ciudad_facturacion && !form.ciudad_facturacion_label) {
      fetchCityLabel(form.ciudad_facturacion).then(name => {
        if (name)
          setForm(p => ({ ...p, ciudad_facturacion_label: name }));
      });
    }
  }, [form.ciudad_facturacion, form.ciudad_facturacion_label]);

  /* si hay vendedor sin label → buscamos el nombre */
  useEffect(() => {
    if (form.vendedor && !form.vendedor_label) {
      fetchSellerLabel(form.vendedor).then(name => {
        if (name) setForm(p => ({ ...p, vendedor_label: name }));
      });
    }
  }, [form.vendedor, form.vendedor_label]);

  /* si hay cliente sin label → buscamos el nombre */
  useEffect(() => {
    if (form.cliente_codigo && !form.cliente_nombre) {
      fetchClientLabel(form.cliente_codigo).then(name => {
        if (name) setForm(p => ({ ...p, cliente_nombre: name }));
      });
    }
  }, [form.cliente_codigo, form.cliente_nombre]);

  /* ────────── markup ────────── */
  return (
    <form
      onSubmit={submit}
      className="
        space-y-6 p-8 bg-white rounded-2xl shadow-xl
        border border-orange-100
      "
    >
      {/* ────────── TÍTULO ────────── */}
      <h2 className="text-xl font-semibold flex items-center gap-2 text-orange-600">
        <FiNavigation />
        Paso 1 – Datos básicos
      </h2>

      {/* grid con TODOS los campos */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* ────────── Tipo de viaje ────────── */}
        <div>
          <label
            htmlFor="tipo_viaje"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiNavigation className="text-orange-500" />
            Tipo de viaje
          </label>

          <div className="relative">
            <FiNavigation className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <select
              id="tipo_viaje"
              name="tipo_viaje"
              value={form.tipo_viaje}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('tipo_viaje') ? 'border-red-500' : 'border-gray-300'}
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              required
            >
              <option value="">— seleccionar —</option>
              <option>NACIONAL</option>
              <option>URBANO</option>
              <option>INTERNACIONAL</option>
            </select>
          </div>
        </div>

        {/* ────────── Moneda ────────── */}
        <div>
          <label
            htmlFor="moneda"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiDollarSign className="text-orange-500" />
            Moneda
          </label>

          <div className="relative">
            <FiDollarSign className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <select
              id="moneda"
              name="moneda"
              value={form.moneda}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('moneda') ? 'border-red-500' : 'border-gray-300'}
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              required
            >
              <option value="">— seleccionar —</option>
              <option>PESOS</option>
              <option>DOLARES</option>
            </select>
          </div>
        </div>

        {/* ────────── Fuente solicitud ────────── */}
        <div>
          <label
            htmlFor="fuente_solicitud"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiPhoneCall className="text-orange-500" />
            Fuente de la solicitud
          </label>

          <div className="relative">
            <FiPhoneCall className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <select
              id="fuente_solicitud"
              name="fuente_solicitud"
              value={form.fuente_solicitud}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('fuente_solicitud') ? 'border-red-500' : 'border-gray-300'}
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              required
            >
              <option value="">— seleccionar —</option>
              <option>TELEFONO DESPACHADOR</option>
              <option>TELEFONO ATENCION CLIENTE</option>
              <option>MAIL</option>
              <option>FAX</option>
              <option>SIA</option>
              <option>PAGINA WEB</option>
            </select>
          </div>
        </div>

        {/* ────────── Tipo operación ────────── */}
        <div>
          <label
            htmlFor="tipo_operacion"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiTruck className="text-orange-500" />
            Tipo de operación
          </label>

          <div className="relative">
            <FiTruck className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <select
              id="tipo_operacion"
              name="tipo_operacion"
              value={form.tipo_operacion}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('tipo_operacion') ? 'border-red-500' : 'border-gray-300'}
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              required
            >
              <option value="">— seleccionar —</option>
              <option>DISTRIBUCION</option>
              <option>IMPORTACION</option>
              <option>EXPORTACION</option>
            </select>
          </div>
        </div>

     
        {/* ────────── Condición despacho ────────── */}
        <div>
          <label
            htmlFor="condicion_despacho"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiClipboard className="text-orange-500" />
            Condición de despacho
          </label>

          <div className="relative">
            <FiClipboard className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <input
              id="condicion_despacho"
              name="condicion_despacho"
              value={form.condicion_despacho}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('condicion_despacho') ? 'border-red-500' : 'border-gray-300'}
                placeholder-gray-400
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              placeholder="Condición despacho"
              required
            />
          </div>
        </div>

        {/* ────────── Condición facturación ────────── */}
        <div>
          <label
            htmlFor="condicion_facturacion"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiFileText className="text-orange-500" />
            Condición de facturación
          </label>

          <div className="relative">
            <FiFileText className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <input
              id="condicion_facturacion"
              name="condicion_facturacion"
              value={form.condicion_facturacion}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('condicion_facturacion') ? 'border-red-500' : 'border-gray-300'}
                placeholder-gray-400
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              placeholder="Condición facturación"
              required
            />
          </div>
        </div>

        {/* ────────── Ciudad facturación ────────── */}
        <div className={`md:col-span-2 ${hasError('ciudad_facturacion') ? 'border border-red-500 rounded-md p-1' : ''}`}>
          <AsyncSearchSelect
            id="ciudad_facturacion"
            label={
              <span className="flex items-center gap-2 text-sm font-medium text-gray-700">
                <FiMapPin className="text-orange-500" />
                Ciudad de facturación
              </span>
            }
            load={searchCiudades}
            getOpt={c => ({
              value : c.ciudad_codigodane,
              label : c.ciudad_nombre
            })}
            value={
              form.ciudad_facturacion
                ? {
                    value: form.ciudad_facturacion,
                    label: form.ciudad_facturacion_label || form.ciudad_facturacion
                  }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                ciudad_facturacion       : opt?.value || '',
                ciudad_facturacion_label : opt?.label || ''
              }))
            }
          />
        </div>

        {/* ────────── Vendedor ────────── */}
        <div className={`md:col-span-2 ${hasError('vendedor') ? 'border border-red-500 rounded-md p-1' : ''}`}>
          <AsyncSearchSelect
            id="vendedor"
            label={
              <span className="flex items-center gap-2 text-sm font-medium text-gray-700">
                <FiUser className="text-orange-500" />
                Vendedor
              </span>
            }
            load={searchVendedores}
            getOpt={v => ({
              value : v.Documento,
              label : `${v.Nombre} (${v.Documento})`
            })}
            value={
              form.vendedor
                ? { value: form.vendedor, label: form.vendedor_label || form.vendedor }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                vendedor       : opt?.value || '',
                vendedor_label : opt?.label || ''
              }))
            }
          />
        </div>

        {/* ────────── Centro costo despacho ────────── */}
        <div>
          <label
            htmlFor="centro_costo_despacho"
            className="block text-sm font-medium mb-1 flex items-center gap-2 text-gray-700"
          >
            <FiLayers className="text-orange-500" />
            Centro de costo despacho
          </label>

          <div className="relative">
            <FiLayers className="absolute left-3 top-1/2 -translate-y-1/2 text-orange-400 pointer-events-none" />
            <input
              id="centro_costo_despacho"
              name="centro_costo_despacho"
              value={form.centro_costo_despacho}
              onChange={change}
              className={`
                w-full py-2 pl-11 pr-3 rounded-md
                border ${hasError('centro_costo_despacho') ? 'border-red-500' : 'border-gray-300'}
                placeholder-gray-400
                focus:ring-2 focus:ring-orange-500 focus:border-orange-500
              `}
              placeholder="Centro costo despacho"
              required
            />
          </div>
        </div>

        {/* ────────── Cliente ────────── */}
        <div className={`md:col-span-2 ${hasError('cliente_codigo') ? 'border border-red-500 rounded-md p-1' : ''}`}>
          <AsyncSearchSelect
            id="cliente_codigo"
            label={
              <span className="flex items-center gap-2 text-sm font-medium text-gray-700">
                <FiUsers className="text-orange-500" />
                Cliente
              </span>
            }
            load={searchClientes}
            getOpt={c => ({
              value : c.codigo,
              label : `${c.cliente} (${c.codigo})`
            })}
            value={
              form.cliente_codigo
                ? { value: form.cliente_codigo, label: form.cliente_nombre || form.cliente_codigo }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                cliente_codigo : opt?.value || '',
                cliente_nombre : opt?.label || ''
              }))
            }
          />
        </div>
      </div> {/* grid end */}

      {/* ────────── Botón ────────── */}
      <div className="text-right">
        <button
          type="submit"
          disabled={loading}
          className="
            inline-flex items-center gap-2 px-8 py-2 rounded-md text-white
            bg-orange-500 hover:bg-orange-600
            focus:ring-2 focus:ring-orange-600
            disabled:opacity-60
          "
        >
          {loading ? (
            <>
              <FiLoader className="animate-spin" />
              Guardando…
            </>
          ) : (
            <>
              Siguiente
              <FiArrowRight />
            </>
          )}
        </button>
      </div>
    </form>
  );
}

Step1.propTypes = {
  data   : PropTypes.object,
  onNext : PropTypes.func.isRequired,
  loading: PropTypes.bool
};