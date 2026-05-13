/*  resources/js/components/SolicitudWizard/Step6.jsx  – Costos */
import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import { chatBus } from './ChatBox';
import AsyncSearchSelect from '../ui/AsyncSelect';
import {
  searchCostos,
  searchProveedores,
} from '../../api/solicitud';
import {
  FiDollarSign,
  FiChevronLeft,
  FiCheckCircle,
  FiPlus,
  FiTrash2,
} from 'react-icons/fi';

/* ── Estado inicial de un ítem de costo ── */
const emptyCosto = () => ({
  tipvalrem_codigo : '',
  tipvalrem_nombre : '',
  valor_unitario   : '',
  valor_costo_unitario : '',
  facturable       : '',
  observacion_costo: '',
  aplica_flete     : 'NO',
  proveedor_codigo : '',
  proveedor_nombre : '',
});

export default function Step6({ data = {}, formData = {}, onNext, onPrev, loading }) {

  /* ── Lista de costos ya guardados (prefill) ── */
  const prefillCostos = formData.costos || data.costos || [];

  const [costos, setCostos] = useState(
    prefillCostos.length > 0 ? prefillCostos : []
  );

  /* ── Formulario del ítem nuevo ── */
  const [current, setCurrent] = useState(emptyCosto());

  /* ── Sync when formData/data changes ── */
  useEffect(() => {
    const c = formData.costos || data.costos || [];
    if (c.length > 0) setCostos(c);
  }, [formData, data]);

  /* ── Chat autofill ── */
  useEffect(() => {
    const fill = async (field, value) => {
      /* When the AI sends a name/text for costo or proveedor, look up the real code */
      if (field === 'tipvalrem_codigo' && value && isNaN(Number(value))) {
        try {
          const res = await searchCostos(value);
          const items = res?.data ?? res ?? [];
          if (items.length > 0) {
            const match = items[0];
            setCurrent(prev => ({
              ...prev,
              tipvalrem_codigo: match.tipvalrem_codigo,
              tipvalrem_nombre: match.tipvalrem_nombre,
            }));
            return;
          }
        } catch { /* ignore */ }
        /* Fallback: store as-is so the user sees something */
        setCurrent(prev => ({ ...prev, tipvalrem_nombre: value }));
        return;
      }
      if (field === 'proveedor_codigo' && value && isNaN(Number(value))) {
        try {
          const res = await searchProveedores(value);
          const items = res?.data ?? res ?? [];
          if (items.length > 0) {
            const match = items[0];
            setCurrent(prev => ({
              ...prev,
              proveedor_codigo: match.tercero_codigo,
              proveedor_nombre: `${match.nombre} (${match.tercero_documento})`,
            }));
            return;
          }
        } catch { /* ignore */ }
        setCurrent(prev => ({ ...prev, proveedor_nombre: value }));
        return;
      }
      setCurrent(prev => ({ ...prev, [field]: value }));
    };
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  /* ── Handlers ── */
  const handleCurrent = e =>
    setCurrent(prev => ({ ...prev, [e.target.name]: e.target.value }));

  const addCosto = () => {
    if (!current.tipvalrem_codigo) return;
    setCostos(prev => [...prev, { ...current }]);
    setCurrent(emptyCosto());
  };

  const removeCosto = idx =>
    setCostos(prev => prev.filter((_, i) => i !== idx));

  const submit = e => {
    e.preventDefault();
    onNext({ costos });
  };

  /* ── UI ── */
  return (
    <form onSubmit={submit} className="space-y-8 text-center">
      {/* Título */}
      <h2 className="text-2xl font-semibold flex items-center justify-center gap-2 text-gray-800">
        <FiDollarSign className="text-orange-500" /> Paso&nbsp;6 – Costos
      </h2>

      {/* ── Formulario nuevo costo ── */}
      <div className="border border-gray-200 rounded-xl p-5 bg-gray-50 space-y-4">

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">

          {/* Costo (autocompletado) */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Costo</label>
            <AsyncSearchSelect
              id="tipvalrem_codigo"
              load={searchCostos}
              getOpt={c => ({
                value : c.tipvalrem_codigo,
                label : `${c.tipvalrem_codigo} – ${c.tipvalrem_nombre}`,
              })}
              value={
                current.tipvalrem_codigo
                  ? { value: current.tipvalrem_codigo, label: current.tipvalrem_nombre || String(current.tipvalrem_codigo) }
                  : null
              }
              onChange={opt =>
                setCurrent(prev => ({
                  ...prev,
                  tipvalrem_codigo : opt?.value || '',
                  tipvalrem_nombre : opt ? opt.label.split(' – ').slice(1).join(' – ') : '',
                }))
              }
              label=""
            />
          </div>

          {/* Valor Unitario */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Valor Unitario</label>
            <input
              name="valor_unitario"
              type="number"
              min="0"
              step="0.01"
              value={current.valor_unitario}
              onChange={handleCurrent}
              className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          {/* Valor Costo Unitario */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Valor Costo Unitario</label>
            <input
              name="valor_costo_unitario"
              type="number"
              min="0"
              step="0.01"
              value={current.valor_costo_unitario}
              onChange={handleCurrent}
              className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          {/* Facturable */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Facturable</label>
            <select
              name="facturable"
              value={current.facturable}
              onChange={handleCurrent}
              className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
              <option value="">SELECCIONE UNO</option>
              <option value="SI">SI</option>
              <option value="NO">NO</option>
            </select>
          </div>

          {/* Observación Costo */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Observación Costo</label>
            <textarea
              name="observacion_costo"
              rows="2"
              value={current.observacion_costo}
              onChange={handleCurrent}
              className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          {/* Aplica Flete */}
          <div>
            <label className="block text-sm font-semibold mb-1 text-gray-700">Aplica Flete</label>
            <select
              name="aplica_flete"
              value={current.aplica_flete}
              onChange={handleCurrent}
              className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
              <option value="NO">NO</option>
              <option value="SI">SI</option>
            </select>
          </div>

          {/* Proveedor (autocompletado) */}
          <div className="md:col-span-2">
            <label className="block text-sm font-semibold mb-1 text-gray-700">Proveedor</label>
            <AsyncSearchSelect
              id="proveedor_codigo"
              load={searchProveedores}
              getOpt={p => ({
                value : p.tercero_codigo,
                label : `${p.tercero_codigo} – ${p.nombre} (${p.tercero_documento})`,
              })}
              value={
                current.proveedor_codigo
                  ? { value: current.proveedor_codigo, label: current.proveedor_nombre || String(current.proveedor_codigo) }
                  : null
              }
              onChange={opt =>
                setCurrent(prev => ({
                  ...prev,
                  proveedor_codigo : opt?.value || '',
                  proveedor_nombre : opt ? opt.label.split(' – ').slice(1).join(' – ') : '',
                }))
              }
              label=""
            />
          </div>
        </div>

        {/* Botón Adicionar Costo */}
        <div className="text-left">
          <button
            type="button"
            onClick={addCosto}
            disabled={!current.tipvalrem_codigo}
            className="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded disabled:opacity-40"
          >
            <FiPlus /> Adicionar Costo
          </button>
        </div>
      </div>

      {/* ── Tabla de costos adicionados ── */}
      {costos.length > 0 && (
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden">
            <thead className="bg-gray-100 text-gray-600 font-semibold">
              <tr>
                <th className="px-3 py-2">Costo</th>
                <th className="px-3 py-2">Vlr Unitario</th>
                <th className="px-3 py-2">Vlr Costo Unit.</th>
                <th className="px-3 py-2">Facturable</th>
                <th className="px-3 py-2">Aplica Flete</th>
                <th className="px-3 py-2">Proveedor</th>
                <th className="px-3 py-2">Observación</th>
                <th className="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {costos.map((c, i) => (
                <tr key={i} className="border-t border-gray-100 hover:bg-orange-50">
                  <td className="px-3 py-2">{c.tipvalrem_nombre || c.tipvalrem_codigo}</td>
                  <td className="px-3 py-2">{c.valor_unitario}</td>
                  <td className="px-3 py-2">{c.valor_costo_unitario}</td>
                  <td className="px-3 py-2">{c.facturable}</td>
                  <td className="px-3 py-2">{c.aplica_flete}</td>
                  <td className="px-3 py-2">{c.proveedor_nombre || '–'}</td>
                  <td className="px-3 py-2 max-w-[150px] truncate">{c.observacion_costo}</td>
                  <td className="px-3 py-2">
                    <button
                      type="button"
                      onClick={() => removeCosto(i)}
                      className="text-red-500 hover:text-red-700"
                      title="Eliminar"
                    >
                      <FiTrash2 />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* ── Navegación ── */}
      <div className="flex justify-between">
        <button
          type="button"
          onClick={() => onPrev({ costos })}
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
  loading: PropTypes.bool,
};