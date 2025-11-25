/*  resources/js/components/SolicitudWizard/Step6.jsx  */
import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import { chatBus } from './ChatBox';
import {
  FiUsers,         // título y label
  FiChevronLeft,   // botón atrás
  FiCheckCircle    // botón finalizar
} from 'react-icons/fi';

export default function Step6({ data = {}, formData = {}, onNext, onPrev, loading }) {
  /* ------------------------------------------------------------------
   *  Prefill (cuando la solicitud ya existe)
   * ----------------------------------------------------------------*/
  const ac = data.acompanamiento || {};
console.log(ac)
  const [form, setForm] = useState({
    vehiculo_acom :
        formData.vehiculo_acom || (ac.vehiculo_acom ?? ac.itesoltra_vehiculoacompanamiento ?? 1),
    tipo_vehiculo_acom :
        formData.tipo_vehiculo_acom || (ac.tipo_vehiculo_acom ?? ac.tipaco_codigo ?? ''),
    acompanamiento_cuenta_acom :
        formData.acompanamiento_cuenta_acom || (ac.acompanamiento_cuenta_acom ?? ac.itesoltra_acompanamientocuentade ?? ''),
    valor_acompanante_acom :
        formData.valor_acompanante_acom || (ac.valor_acompanante_acom ?? ac.itesoltra_acompanamientovalor ?? ''),
  });

  /* ------------------------------------------------------------------
   *  Chat → autocompletado
   * ----------------------------------------------------------------*/
  useEffect(() => {
    const fill = (k, v) => setForm(p => ({ ...p, [k]: v }));
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  /* ------------------------------------------------------------------
   *  Handlers
   * ----------------------------------------------------------------*/
  const handle = e =>
    setForm(p => ({ ...p, [e.target.name]: e.target.value }));

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
    </form>
  );
}

Step6.propTypes = {
  data   : PropTypes.object,
  onNext : PropTypes.func.isRequired,
  onPrev : PropTypes.func.isRequired,
  loading: PropTypes.bool
};