import React, { useEffect, useState } from 'react';
import {
  getNotes,
  addNote,
  eventsList
} from '../../services/cotizationsService';

import useCurrentUser from '../../hooks/useCurrentUser';
import { normalizeRole } from '../../services/roles';

import {
  FiX,
  FiAlertCircle,
  FiClipboard,
  FiPlus
} from 'react-icons/fi';

export default function NotesPanel({ model, onClose }) {
  const [notes, setNotes]     = useState([]);
  const [noteType, setNoteType] = useState('alerta');
  const [body, setBody]       = useState('');
  const [events, setEvents]   = useState([]);

  /* ---- usuario / permisos ---- */
  const { user }  = useCurrentUser();
  const allowed   = ['SUPER_ADMIN', 'SAC', 'GERENTE_DE_CUENTA'];
  const canWrite  = user?.roles?.some(r =>
    allowed.includes(normalizeRole(r.name))
  );

  /* ---- carga inicial ---- */
  useEffect(() => {
    if (model) {
      loadNotes();
      eventsList().then(r => setEvents(r.data));
    }
  }, [model]);

  const loadNotes = () =>
    model && getNotes(model.id).then(r => setNotes(r.data));

  const handleSave = async () => {
    if (!body.trim()) return;
    await addNote(model.id, { type: noteType, body });
    setBody('');
    loadNotes();
  };

  if (!model) return null;

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-[10001]">
      {/* ---------- Panel ---------- */}
      <div className="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-lg">
        {/* ---------- Cerrar ---------- */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
        >
          <FiX size={22} />
        </button>

        {/* ---------- Título ---------- */}
        <h3 className="text-2xl font-semibold text-gray-800 mb-6">
          Notas · Cotización #{model.id}
        </h3>

        {/* ---------- Listado ---------- */}
        <ul className="space-y-3 mb-8">
          {notes.map(n => (
            <li
              key={n.id}
              className="flex items-start gap-3 bg-gray-50 rounded-md p-3"
            >
              <span className="mt-1">
                {n.type === 'alerta' ? (
                  <FiAlertCircle className="text-red-500" />
                ) : (
                  <FiClipboard className="text-indigo-500" />
                )}
              </span>

              <div className="text-sm leading-snug">
                <span className="font-medium capitalize">{n.type}</span>:{' '}
                {n.body}
                <span className="block text-xs text-gray-500 mt-1">
                  — {n.author.name} ·{' '}
                  {new Date(n.created_at).toLocaleString()}
                </span>
              </div>
            </li>
          ))}
        </ul>

        {/* ---------- Formulario ---------- */}
        {canWrite && (
          <div className="space-y-4">
            {/* tipo */}
            <select
              value={noteType}
              onChange={e => setNoteType(e.target.value)}
              className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            >
              <option value="alerta">Procedimiento de alerta</option>
              <option value="novedad">Novedad de tránsito</option>
            </select>

            {/* evento predeterminado */}
            {noteType === 'novedad' && (
              <select
                onChange={e => setBody(e.target.value)}
                className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              >
                <option value="">
                  -- Seleccione novedad predeterminada --
                </option>
                {events.map(ev => (
                  <option key={ev.id} value={ev.name}>
                    {ev.name}
                  </option>
                ))}
                <option value="">Otra…</option>
              </select>
            )}

            {/* texto libre */}
            <textarea
              rows="3"
              placeholder="Escribe la nota…"
              value={body}
              onChange={e => setBody(e.target.value)}
              className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            />

            {/* botón guardar */}
            <button
              onClick={handleSave}
              className="w-full flex items-center justify-center gap-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium tracking-tight py-2 transition active:scale-95"
            >
              <FiPlus /> Guardar nota
            </button>
          </div>
        )}
      </div>
    </div>
  );
}