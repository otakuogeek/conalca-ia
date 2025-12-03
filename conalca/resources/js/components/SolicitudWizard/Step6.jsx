/*  resources/js/components/SolicitudWizard/Step6.jsx  */
import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import { chatBus } from './ChatBox';
import {
  FiUsers,         // título y label
  FiChevronLeft,   // botón atrás
  FiCheckCircle    // botón finalizar
} from 'react-icons/fi';

// const buildStep6State = (formData = {}, acompanamiento = {}) => ({
//   vehiculo_acom:
//     formData.vehiculo_acom ??
//     acompanamiento.vehiculo_acom ??
//     acompanamiento.itesoltra_vehiculoacompanamiento ??
//     1,

//   tipo_vehiculo_acom:
//     formData.tipo_vehiculo_acom ??
//     acompanamiento.tipo_vehiculo_acom ??
//     acompanamiento.tipaco_codigo ??
//     '',

//   acompanamiento_cuenta_acom:
//     formData.acompanamiento_cuenta_acom ??
//     acompanamiento.acompanamiento_cuenta_acom ??
//     acompanamiento.itesoltra_acompanamientocuentade ??
//     '',

//   valor_acompanante_acom:
//     formData.valor_acompanante_acom ??
//     acompanamiento.valor_acompanante_acom ??
//     acompanamiento.itesoltra_acompanamientovalor ??
//     ''
// });

const buildStep6State = (
  formData = {},
  acompanamiento = {},
  flatData = {}              // NEW
) => ({
  vehiculo_acom:
    formData.vehiculo_acom ??
    acompanamiento.vehiculo_acom ??
    acompanamiento.itesoltra_vehiculoacompanamiento ??
    flatData.vehiculo_acom ??               // NEW
    1,

  tipo_vehiculo_acom:
    formData.tipo_vehiculo_acom ??
    acompanamiento.tipo_vehiculo_acom ??
    acompanamiento.tipaco_codigo ??
    flatData.tipo_vehiculo_acom ??          // NEW
    '',

  acompanamiento_cuenta_acom:
    formData.acompanamiento_cuenta_acom ??
    acompanamiento.acompanamiento_cuenta_acom ??
    acompanamiento.itesoltra_acompanamientocuentade ??
    flatData.acompanamiento_cuenta_acom ??  // NEW
    '',

  valor_acompanante_acom:
    formData.valor_acompanante_acom ??
    acompanamiento.valor_acompanante_acom ??
    acompanamiento.itesoltra_acompanamientovalor ??
    flatData.valor_acompanante_acom ??      // NEW
    ''
});

const DebugInspector = ({ form, formData, data, show }) => {
  if (!show) return null;
  return (
    <div className="mt-6 rounded-xl border border-red-300 bg-gray-900 text-green-200 text-xs p-4 space-y-3">
      <h3 className="text-red-300 font-semibold text-sm">🪲 Debug: Step6 snapshot</h3>
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

export default function Step6({ data = {}, formData = {}, onNext, onPrev, loading }) {
  /* ------------------------------------------------------------------
   *  Prefill (cuando la solicitud ya existe)
   * ----------------------------------------------------------------*/
  const ac = data.acompanamiento || {};

  const [showDebug, setShowDebug] = useState(false);
   const [debugLog, setDebugLog] = useState([]);  
   const [form, setForm] = useState(buildStep6State(formData, ac, data)); 
  

  useEffect(() => {
    setForm(buildStep6State(formData, data.acompanamiento || {}, data));   // NEW
  }, [formData, data]);

  /* ------------------------------------------------------------------
   *  Chat → autocompletado
   * ----------------------------------------------------------------*/
  useEffect(() => {
    const fill = (field, value) => {
      setDebugLog(prev => [...prev, `${field} = ${value}`]);
      setForm(prev => ({ ...prev, [field]: value }));
    };
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  /* ------------------------------------------------------------------
   *  Handlers
   * ----------------------------------------------------------------*/
   const handle = e => setForm(prev => ({ ...prev, [e.target.name]: e.target.value }));
  const submit = e => { e.preventDefault(); onNext(form); };

  /* ------------------------------------------------------------------
   *  UI
   * ----------------------------------------------------------------*/
  return (
    <form onSubmit={submit} className="space-y-10 text-center">
      {/* Título */}
      <h2 className="text-2xl font-semibold flex items-center justify-center gap-2 text-gray-800">
        <FiUsers className="text-orange-500" /> Paso&nbsp;6 – Acompañamiento
      </h2>

      {/* Nº de vehículos de acompañamiento */}
      <div>
        <label
          htmlFor="vehiculo_acom"
          className="block text-sm font-semibold mb-2 flex items-center justify-center gap-1 text-gray-800"
        >
          <FiUsers className="text-orange-500" />
          Nº de vehículos
        </label>

        <input
          id="vehiculo_acom"
          name="vehiculo_acom"
          type="number"
          min="0"
          value={form.vehiculo_acom}
          onChange={handle}
          placeholder="Cantidad"
          className="border border-orange-300 rounded px-4 py-2 w-64 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
          required
        />
      </div>

      {/* Tipo ACC */}
      <div>
        <label
          htmlFor="tipo_vehiculo_acom"
          className="block text-sm font-semibold mb-2 text-gray-800"
        >
          Tipo ACC
        </label>

        <select
          id="tipo_vehiculo_acom"
          name="tipo_vehiculo_acom"
          value={form.tipo_vehiculo_acom}
          onChange={handle}
          className="border border-orange-300 rounded px-4 py-2 w-64 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
        >
          <option value="">Seleccionar</option>
          <option value="MOTORIZADO">MOTORIZADO</option>
          <option value="VEHICULAR">VEHICULAR</option>
          <option value="CABINA">CABINA</option>
        </select>
      </div>

      {/* ¿Quién lo asume? */}
      <div>
        <label
          htmlFor="acompanamiento_cuenta_acom"
          className="block text-sm font-semibold mb-2 text-gray-800"
        >
          ¿Quién lo asume?
        </label>

        <select
          id="acompanamiento_cuenta_acom"
          name="acompanamiento_cuenta_acom"
          value={form.acompanamiento_cuenta_acom}
          onChange={handle}
          className="border border-orange-300 rounded px-4 py-2 w-64 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
        >
          <option value="">Seleccionar</option>
          <option value="CLIENTE">CLIENTE</option>
          <option value="EMPRESA">EMPRESA</option>
        </select>
      </div>

      {/* Valor COP */}
      <div>
        <label
          htmlFor="valor_acompanante_acom"
          className="block text-sm font-semibold mb-2 text-gray-800"
        >
          Valor&nbsp;COP
        </label>

        <input
          id="valor_acompanante_acom"
          name="valor_acompanante_acom"
          type="number"
          min="1"
          step="0.01"
          value={form.valor_acompanante_acom}
          onChange={handle}
          placeholder="0"
          className="border border-orange-300 rounded px-4 py-2 w-64 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
          required
        />
      </div>

      {/* Navegación */}
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
          className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-2 rounded disabled:opacity-50"
        >
          {loading ? 'Guardando…' : <>Finalizar <FiCheckCircle /></>}
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
      {/* ────────── Debug tools ────────── */}
      {/* <div className="pt-4 border-t border-dashed border-gray-200">
        <button
          type="button"
          onClick={() => setShowDebug(v => !v)}
          className="text-xs uppercase tracking-wide text-red-500 border border-red-300 px-3 py-1 rounded-md hover:bg-red-50"
        >
          {showDebug ? 'Hide debug snapshot' : 'Show debug snapshot'}
        </button>

        {showDebug && (
          <div className="mt-4 text-left text-xs bg-gray-900 text-green-200 rounded-lg p-3 space-y-2">
            <p className="font-semibold text-red-300">🪲 Step5 debug</p>
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
                {JSON.stringify({
                  flat_modalidad: data.modalidad_internacional,
                  internacional: data.internacional
                }, null, 2)}
              </pre>
            </div>
          </div>
        )}
      </div> */}
    </form>
  );
}

Step6.propTypes = {
  data   : PropTypes.object,
  onNext : PropTypes.func.isRequired,
  onPrev : PropTypes.func.isRequired,
  loading: PropTypes.bool
};