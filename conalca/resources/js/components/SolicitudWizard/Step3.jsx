import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import { chatBus } from './ChatBox';
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

export default function Step3({ data = {}, onNext, onPrev, loading }) {
  const c = data.cargue || {};

  const [form, setForm] = useState({
    fecha_cargue        : c.fecha_cargue         || '',
    hora_cargue         : c.hora_cargue          || '',
    remitente           : c.remitente            || '',
    destinatario        : c.destinatario         || '',
    contacto            : c.contacto             || '',
    promesa_servicio    : c.promesa_servicio     || '',
    documento_transporte: c.documento_transporte || '',
    observacion_cargue  : c.observacion_cargue   || ''
  });

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

        {/* Remitente */}
        <div>
          <label
            htmlFor="remitente"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiUser className="text-orange-500" /> Remitente
          </label>
          <input
            id="remitente"
            name="remitente"
            value={form.remitente}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Remitente"
            required
          />
        </div>

        {/* Destinatario */}
        <div>
          <label
            htmlFor="destinatario"
            className="block text-sm font-semibold mb-1 flex items-center gap-1 text-gray-800"
          >
            <FiUserCheck className="text-orange-500" /> Destinatario
          </label>
          <input
            id="destinatario"
            name="destinatario"
            value={form.destinatario}
            onChange={change}
            className="w-full border border-orange-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
            placeholder="Destinatario"
            required
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

Step3.propTypes = {
  data    : PropTypes.object,
  onNext  : PropTypes.func.isRequired,
  onPrev  : PropTypes.func.isRequired,
  loading : PropTypes.bool
};