import React, { useState } from 'react';
import { createSolicitation } from '../../services/solicitations';

export default function SolicitationForm({ onCreated }) {
  const [form, setForm] = useState({
    origin: '',
    destination: '',
    importance: 'LOW',
    description: ''
  });

  const handle = e => setForm({ ...form, [e.target.name]: e.target.value });

  const submit = async e => {
    e.preventDefault();
    await createSolicitation(form);
    setForm({ origin:'', destination:'', importance:'', description:'' });
    onCreated();
  };

  return (
    <div className="mb-6 bg-white rounded-xl p-6 shadow-sm border border-orange-100" style={{ fontFamily: 'Product Sans, sans-serif' }}>
      <h3 className="text-lg font-medium text-orange-700 mb-4" style={{ fontFamily: 'Product Sans, sans-serif' }}>Nueva Solicitud</h3>
      <form onSubmit={submit}>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <input
            name="origin"
            value={form.origin}
            onChange={handle}
            placeholder="Origen"
            className="border border-orange-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all bg-white"
            style={{ fontFamily: 'Product Sans, sans-serif' }}
            required
            maxLength={255}
          />
          <input
            name="destination"
            value={form.destination}
            onChange={handle}
            placeholder="Destino"
            className="border border-orange-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all bg-white"
            style={{ fontFamily: 'Product Sans, sans-serif' }}
            required
            maxLength={255}
          />
        </div>
        <div className="flex gap-4 mb-4">
          <select
            name="importance"
            value={form.importance}
            onChange={handle}
            className="border border-orange-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all flex-1 bg-white"
            style={{ fontFamily: 'Product Sans, sans-serif' }}
          >
            <option value="LOW">Baja</option>
            <option value="MEDIUM">Media</option>
            <option value="HIGH">Alta</option>
          </select>
          <button
            className="bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg px-6 py-2.5 text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
            disabled={!form.origin || !form.destination}
            style={{ fontFamily: 'Product Sans, sans-serif' }}
          >
            Crear Solicitud
          </button>
        </div>
        <textarea
          name="description"
          value={form.description}
          onChange={handle}
          placeholder="Descripción (opcional)"
          className="border border-orange-200 rounded-lg px-4 py-2.5 text-sm w-full resize-none focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all bg-white"
          style={{ fontFamily: 'Product Sans, sans-serif' }}
          rows={3}
          maxLength={500}
        />
      </form>
    </div>
  );
}