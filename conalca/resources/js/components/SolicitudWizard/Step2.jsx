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



export default function Step2({ data = {}, onNext, onPrev, loading }) {

  const d = data.detalle || {};

  const [form, setForm] = useState({
    /* ciudades */
    origen                 : d.origen  || '',
    origen_label           : d.origen_label  || '',
    destino                : d.destino || '',
    destino_label          : d.destino_label || '',

    cantidad_mercancia     : d.cantidad_mercancia     || '',
    peso                   : d.peso                   || '',
    valor_mercancia        : d.valor_mercancia        || '',

    /* catálogo producto / empaque */
    producto               : d.producto        || '',
    producto_label         : d.producto_label  || '',
    empaque                : d.empaque         || '',
    empaque_label          : d.empaque_label   || '',

    cantidad_vehiculos     : d.cantidad_vehiculos     || '',

    /* clase y carrocería de vehículo */
    clase_vehiculo         : d.clase_vehiculo        || '',
    clase_vehiculo_label   : d.clase_vehiculo_label  || '',
    carroceria             : d.carroceria            || '',
    carroceria_label       : d.carroceria_label      || '',

    /* demás campos sin cambios */
    minimo_modelo          : d.minimo_modelo          || '',
    tipo_flete             : d.tipo_flete             || '',
    flete_conductor        : d.flete_conductor        || '',
    flete_ministerio       : d.flete_ministerio       || '',
    tipo_tarifa            : d.tipo_tarifa            || '',
    tarifa_cliente         : d.tarifa_cliente         || '',
    cargue_cuenta_de       : d.cargue_cuenta_de       || '',
    descargue_cuenta_de    : d.descargue_cuenta_de    || '',
    seguro_cuenta_de       : d.seguro_cuenta_de       || '',
    descripcion_mercancia  : d.descripcion_mercancia  || '',
    kit_seguridad          : d.kit_seguridad          || '',
    sub_cliente            : d.sub_cliente            || '',
    tipo_remesa_rndc       : d.tipo_remesa_rndc       || ''
  });
  /* ------------------------------------------------------------------ */
  /*  VALIDACIÓN                                                        */
  /* ------------------------------------------------------------------ */
  const [submitted, setSubmitted] = useState(false);
  const isEmpty  = v => v === '' || v === null || v === undefined;

  /* todos los campos que son obligatorios en este paso */
  const required = [
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

  /* ¿el campo tiene error? (solo después de enviar) */
  const hasError = name => submitted && isEmpty(form[name]);

  /* chat → auto-fill */
  useEffect(()=>{
    const fill = (f,v)=>{
      console.log('📝 Step2 recibió fill-field:', f, '=', v);
      setForm(p=>({...p,[f]:v}));
    };
    chatBus.on('fill-field',fill);
    return()=>chatBus.off('fill-field',fill);
  },[]);

  /* helpers asincrónicos  ─────────────────────────── */
  const getCityName   = async code => {
    if (!code) return '';
    const { data } = await searchCiudades(code);
    const f = data.find(c => String(c.ciudad_codigodane) === String(code));
    return f ? f.ciudad_nombre : '';
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

    /* armamos el payload sin los “label” ---------------------------- */
    const {
      origen_label,
      destino_label,
      producto_label,
      empaque_label,
      clase_vehiculo_label,
      carroceria_label,
      ...payload
    } = form;

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
        <div className={hasError('origen') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="origen" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiMapPin className="text-orange-500" /> Ciudad de origen
          </label>
          <AsyncSearchSelect
            id="origen"
            load={searchCiudades}
            getOpt={c => ({ value: c.ciudad_codigodane, label: c.ciudad_nombre })}
            value={
              form.origen
                ? { value: form.origen, label: form.origen_label || form.origen }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                origen: opt?.value || '',
                origen_label: opt?.label || ''
              }))
            }
          />
        </div>

        {/* ───── Ciudad de destino ───── */}
        <div className={hasError('destino') ? 'border border-red-500 rounded p-1' : ''}>
          <label htmlFor="destino" className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiMapPin className="text-orange-500" /> Ciudad de destino
          </label>
          <AsyncSearchSelect
            id="destino"
            load={searchCiudades}
            getOpt={c => ({ value: c.ciudad_codigodane, label: c.ciudad_nombre })}
            value={
              form.destino
                ? { value: form.destino, label: form.destino_label || form.destino }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                destino: opt?.value || '',
                destino_label: opt?.label || ''
              }))
            }
          />
        </div>

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
          onClick={onPrev}
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
    </form>
  );
}

Step2.propTypes = {
  data    : PropTypes.object,
  onNext  : PropTypes.func.isRequired,
  onPrev  : PropTypes.func.isRequired,
  loading : PropTypes.bool
};