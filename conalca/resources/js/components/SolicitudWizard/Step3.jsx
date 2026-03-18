import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import { chatBus } from './ChatBox';
import AsyncSearchSelect    from '../ui/AsyncSelect';
import { searchTerceros }    from '../../api/solicitud';
import {
  FiPackage,        // título
  FiCalendar,       // fechas
  FiClock,          // hora de cargue
  FiUser,           // remitente
  FiUserCheck,      // destinatario
  FiPhone,          // contacto
  FiFileText,       // documento de transporte
  FiEdit,           // observación
  FiChevronLeft,    // botón atrás
  FiChevronRight    // botón siguiente
} from 'react-icons/fi';

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

const buildStep3State = (formData = {}, cargue = {}, data = {}) => ({
  fecha_cargue              : formData.fecha_cargue              || cargue.fecha_cargue              || '',
  hora_cargue               : formData.hora_cargue               || cargue.hora_cargue               || '',
  tiempo_cargue_pactado     : formData.tiempo_cargue_pactado     || cargue.tiempo_cargue_pactado     || '',
  fecha_cita_descargue      : formData.fecha_cita_descargue      || cargue.fecha_cita_descargue      || '',
  hora_cita_descargue       : formData.hora_cita_descargue       || cargue.hora_cita_descargue       || '',
  tiempo_descargue_pactado  : formData.tiempo_descargue_pactado  || cargue.tiempo_descargue_pactado  || '',
  remitente                 : formData.remitente                 || cargue.remitente                 || '',
  remitente_label           : formData.remitente_label           || cargue.remitente_label           || '',
  destinario                : formData.destinario ?? formData.destinatario ?? cargue.destinario ?? cargue.destinatario ?? '',
  destinario_label          : formData.destinario_label          || cargue.destinario_label          || '',
  contacto                  : formData.contacto                  || cargue.contacto                  || '',
  promesa_servicio          : formData.promesa_servicio          || cargue.promesa_servicio          || '',
  documento_transporte      : formData.documento_transporte      || cargue.documento_transporte      || '',
  observacion_cargue        : formData.observacion_cargue        || cargue.observacion_cargue        || data.condicion_despacho || ''
});

export default function Step3({ data = {}, formData = {}, onNext, onPrev, loading }) {
  const c = data.cargue || {};
  
  const [showDebug, setShowDebug] = useState(false);
  const [form, setForm] = useState(buildStep3State(formData, c, data));

  useEffect(() => {
    setForm(buildStep3State(formData, data.cargue || {}, data));
  }, [formData, data]);
  

  /* chat → auto-fill */
  useEffect(() => {
    const fill = (k, v) => setForm(p => ({ ...p, [k]: v }));
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  const change = e => setForm({ ...form, [e.target.name]: e.target.value });
  const submit = e => { e.preventDefault(); onNext(form); };

  return (
    <form onSubmit={submit} className="space-y-10">
      {/* Título */}
      <h2 className="text-2xl font-semibold flex items-center gap-2 text-gray-800">
        <FiPackage className="text-orange-500" /> Paso 3 – Cargue
      </h2>

      {/* GRID PRINCIPAL */}
      <div className="grid md:grid-cols-2 gap-8">

        {/* Fecha de cargue */}
        <div>
          <label
            htmlFor="fecha_cargue"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiCalendar className="text-orange-500" /> Fecha de cargue
          </label>
          <input
            id="fecha_cargue"
            name="fecha_cargue"
            type="date"
            value={form.fecha_cargue}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            required
          />
        </div>

        {/* Hora de cargue */}
        <div>
          <label
            htmlFor="hora_cargue"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiClock className="text-orange-500" /> Hora de cargue
          </label>
          <input
            id="hora_cargue"
            name="hora_cargue"
            type="time"
            value={form.hora_cargue}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            required
          />
        </div>

        {/* Tiempo cargue pactado */}
        <div>
          <label
            htmlFor="tiempo_cargue_pactado"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiClock className="text-orange-500" /> Tiempo cargue pactado (horas)
          </label>
          <input
            id="tiempo_cargue_pactado"
            name="tiempo_cargue_pactado"
            type="number"
            min="0"
            step="1"
            value={form.tiempo_cargue_pactado}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Ej: 4"
          />
        </div>

        {/* Fecha cita descargue */}
        <div>
          <label
            htmlFor="fecha_cita_descargue"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiCalendar className="text-orange-500" /> Fecha cita descargue
          </label>
          <input
            id="fecha_cita_descargue"
            name="fecha_cita_descargue"
            type="date"
            value={form.fecha_cita_descargue}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
        </div>

        {/* Hora cita descargue */}
        <div>
          <label
            htmlFor="hora_cita_descargue"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiClock className="text-orange-500" /> Hora cita descargue
          </label>
          <input
            id="hora_cita_descargue"
            name="hora_cita_descargue"
            type="time"
            value={form.hora_cita_descargue}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
        </div>

        {/* Tiempo descargue pactado */}
        <div>
          <label
            htmlFor="tiempo_descargue_pactado"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiClock className="text-orange-500" /> Tiempo descargue pactado (horas)
          </label>
          <input
            id="tiempo_descargue_pactado"
            name="tiempo_descargue_pactado"
            type="number"
            min="0"
            step="1"
            value={form.tiempo_descargue_pactado}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Ej: 4"
          />
        </div>

        {/* Remitente */}
        <div>
          <label className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiUser className="text-orange-500" /> Remitente
          </label>
          <AsyncSearchSelect
            id="remitente"
            load={searchTerceros}
            getOpt={t => ({
              value: String(t.id),
              label: `${t.codigo || t.documento} – ${t.cliente}`
            })}
            value={
              form.remitente
                ? { value: form.remitente, label: form.remitente_label || form.remitente }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                remitente       : opt?.value || '',
                remitente_label : opt?.label || ''
              }))
            }
            placeholder="Buscar remitente…"
          />
        </div>

        {/* Destinatario */}
        <div>
          <label className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800">
            <FiUserCheck className="text-orange-500" /> Destinatario
          </label>
          <AsyncSearchSelect
            id="destinatario"
            load={searchTerceros}
            getOpt={t => ({
              value: String(t.id),
              label: `${t.codigo || t.documento} – ${t.cliente}`
            })}
            value={
              form.destinario
                ? { value: form.destinario, label: form.destinario_label || form.destinario }
                : null
            }
            onChange={opt =>
              setForm(p => ({
                ...p,
                destinario       : opt?.value || '',
                destinario_label : opt?.label || ''
              }))
            }
            placeholder="Buscar destinatario…"
          />
        </div>

        {/* Contacto */}
        <div>
          <label
            htmlFor="contacto"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiPhone className="text-orange-500" /> Contacto
          </label>
          <input
            id="contacto"
            name="contacto"
            value={form.contacto}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Contacto"
            required
          />
        </div>

        {/* Promesa de servicio */}
        <div>
          <label
            htmlFor="promesa_servicio"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiCalendar className="text-orange-500" /> Promesa de servicio
          </label>
          <input
            id="promesa_servicio"
            name="promesa_servicio"
            type="date"
            value={form.promesa_servicio}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            required
          />
        </div>

        {/* Documento transporte */}
        <div>
          <label
            htmlFor="documento_transporte"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiFileText className="text-orange-500" /> Documento de transporte
          </label>
          <input
            id="documento_transporte"
            name="documento_transporte"
            value={form.documento_transporte}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Doc. transporte"
            required
          />
        </div>
      </div>

      {/* Observación cargue */}
      <div>
        <label
          htmlFor="observacion_cargue"
          className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
        >
          <FiEdit className="text-orange-500" /> Observación de cargue
        </label>
        <textarea
          id="observacion_cargue"
          name="observacion_cargue"
          value={form.observacion_cargue}
          onChange={change}
          className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
          rows={3}
          placeholder="Observación"
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

Step3.propTypes = {
  data    : PropTypes.object,
  onNext  : PropTypes.func.isRequired,
  onPrev  : PropTypes.func.isRequired,
  loading : PropTypes.bool
};