import React, { useState, useEffect } from 'react';
import {
  fetchGroups,
  fetchTransitEvents,
  addNote,
  getNotes,
  updateGroupStatus
} from '../../services/cotizationsService';

import {
  FiSearch,
  FiCheckCircle,
  FiClipboard,
  FiPlus,
  FiAlertCircle
} from 'react-icons/fi';
import { TbHandClick } from "react-icons/tb";

export default function SacCotizationView() {
  const [groups, setGroups]   = useState([]);
  const [events, setEvents]   = useState([]);
  const [filters, setFilters] = useState({
    group_id: '',
    cotization_id: '',
    status: '',
    from: '',
    to: ''
  });

  const [selGroup, setSelGroup] = useState(null);
  const [selModel, setSelModel] = useState(null);
  const [notes, setNotes]       = useState([]);
  const [noteBody, setNoteBody] = useState('');
  const [noteType, setNoteType] = useState('alerta');

  /* ------------ carga inicial ------------ */
  useEffect(() => {
    loadGroups();
    fetchTransitEvents().then(r => setEvents(r.data));
  }, []);

  const loadGroups = () =>
    fetchGroups(filters).then(r => {
      setGroups(r.data);
      setSelGroup(null);
      setSelModel(null);
      setNotes([]);
    });

  /* ------------ notas ------------ */
  const loadNotes = id => getNotes(id).then(r => setNotes(r.data));

  const handleAddNote = async () => {
    if (!selModel) return;
    await addNote(selModel.id, { type: noteType, body: noteBody });
    setNoteBody('');
    loadNotes(selModel.id);
  };

  /* ------------ render ------------ */
  return (
    <div className="max-w-screen-xl mx-auto px-4 py-8">
      {/* ---------- Título ---------- */}
      <h2 className="mb-8 text-3xl font-semibold text-gray-800 tracking-tight">
        Cotizaciones <span className="text-indigo-600">SAC</span>
      </h2>

      {/* ---------- Filtros ---------- */}
      <div className="bg-white shadow-sm rounded-lg p-6 mb-8">
        <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
          <input
            type="text"
            placeholder="ID grupo"
            className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            value={filters.group_id}
            onChange={e =>
              setFilters({ ...filters, group_id: e.target.value })
            }
          />
          <input
            type="text"
            placeholder="ID cotización"
            className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            value={filters.cotization_id}
            onChange={e =>
              setFilters({ ...filters, cotization_id: e.target.value })
            }
          />
          <select
            className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            value={filters.status}
            onChange={e =>
              setFilters({ ...filters, status: e.target.value })
            }
          >
            <option value="">Estado</option>
            <option value="en tránsito">En tránsito</option>
            <option value="en facturación">En facturación</option>
            <option value="facturado">Facturado</option>
          </select>
          <input
            type="date"
            className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            onChange={e => setFilters({ ...filters, from: e.target.value })}
          />
          <input
            type="date"
            className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            onChange={e => setFilters({ ...filters, to: e.target.value })}
          />
          <button
            className="rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium tracking-tight transition focus:outline-none active:scale-95 flex items-center justify-center gap-2"
            onClick={loadGroups}
          >
            <FiSearch /> Buscar
          </button>
        </div>
      </div>

      {/* ---------- Tabla de grupos ---------- */}
      <div className="rounded-lg overflow-hidden shadow-sm border border-gray-200 mb-8">
        <table className="w-full text-sm text-left">
          <thead className="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
            <tr>
              <th className="px-4 py-3">ID</th>
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3">Estado</th>
              <th className="px-4 py-3"># Cot.</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {groups.map(g => (
              <tr
                key={g.id}
                className={`cursor-pointer transition-colors ${
                  selGroup?.id === g.id ? 'bg-indigo-50' : 'hover:bg-gray-50'
                }`}
              >
                <td
                  className="px-4 py-3 font-medium text-indigo-600"
                  onClick={() => {
                    setSelGroup(g);
                    setSelModel(null);
                  }}
                >
                  {g.id}
                  <TbHandClick className="w-6 h-6" />
                </td>
                <td className="px-4 py-3">{g.client?.name}</td>
                <td className="px-4 py-3">
                  <select
                    className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    defaultValue={g.status}
                    onChange={e =>
                      updateGroupStatus(g.id, e.target.value).then(loadGroups)
                    }
                  >
                    <option value="en tránsito">En tránsito</option>
                    <option value="en facturación">En facturación</option>
                    <option value="facturado">Facturado</option>
                  </select>
                </td>
                <td className="px-4 py-3 text-center">
                  {g.cotizaciones.length}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* ---------- Cotizaciones del grupo ---------- */}
      {selGroup && (
        <div className="bg-white shadow-sm rounded-lg p-6 mb-8">
          <h5 className="text-lg font-medium text-gray-700 mb-4">
            Grupo #{selGroup.id} · Cotizaciones
          </h5>

          <div className="rounded-lg overflow-hidden border border-gray-200">
            <table className="w-full text-sm text-left">
              <thead className="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                  <th className="px-4 py-3">ID</th>
                  <th className="px-4 py-3">Cliente</th>
                  <th className="px-4 py-3">Tipo</th>
                  <th className="px-4 py-3">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {selGroup.cotizaciones.map(c => (
                  <tr
                    key={c.id}
                    className={`transition-colors ${
                      selModel?.id === c.id
                        ? 'bg-emerald-50'
                        : 'hover:bg-gray-50'
                    }`}
                  >
                    <td className="px-4 py-3 font-medium text-emerald-600">
                      {c.id}
                    </td>
                    <td className="px-4 py-3">{c.client?.name}</td>
                    <td className="px-4 py-3">{c.tipo}</td>
                    <td className="px-4 py-3">
                      <button
                        className="rounded-md border border-emerald-600 text-emerald-600 hover:bg-emerald-50 text-sm font-medium tracking-tight transition focus:outline-none active:scale-95 flex items-center gap-1 px-3 py-1.5"
                        onClick={() => {
                          setSelModel(c);
                          loadNotes(c.id);
                        }}
                      >
                        <FiCheckCircle className="mr-1" />
                        Seleccionar
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ---------- Notas de la cotización ---------- */}
      {selModel && (
        <div className="bg-white shadow-sm rounded-lg p-6">
          <h5 className="text-lg font-medium text-gray-700 mb-4">
            Cotización #{selModel.id} · Notas
          </h5>

          {/* Lista de notas */}
          <ul className="mb-6 space-y-2">
            {notes.map(n => (
              <li
                key={n.id}
                className="flex items-start gap-2 bg-gray-50 rounded-md p-3"
              >
                <div className="shrink-0 pt-0.5">
                  {n.type === 'alerta' ? (
                    <FiAlertCircle className="text-red-500" />
                  ) : (
                    <FiClipboard className="text-indigo-500" />
                  )}
                </div>
                <div className="text-sm leading-snug">
                  <span className="font-medium capitalize">{n.type}</span>:{' '}
                  {n.body}
                  <span className="block text-xs text-gray-500 mt-1">
                    — {n.author.name}
                  </span>
                </div>
              </li>
            ))}
          </ul>

          {/* Formulario alta de nota */}
          <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
            <select
              className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition md:col-span-3"
              value={noteType}
              onChange={e => setNoteType(e.target.value)}
            >
              <option value="alerta">Procedimiento de alerta</option>
              <option value="novedad">Novedad de tránsito</option>
            </select>

            {noteType === 'novedad' && (
              <select
                className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition md:col-span-4"
                onChange={e => setNoteBody(e.target.value)}
              >
                <option value="">-- Seleccione novedad --</option>
                {events.map(ev => (
                  <option key={ev.id} value={ev.name}>
                    {ev.name}
                  </option>
                ))}
                <option value="">Otra…</option>
              </select>
            )}

            <textarea
              rows="3"
              placeholder="Escribe aquí la nota…"
              className="w-full rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition md:col-span-12"
              value={noteBody}
              onChange={e => setNoteBody(e.target.value)}
            />

            <button
              className="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium tracking-tight transition focus:outline-none active:scale-95 flex items-center justify-center gap-2 md:col-span-3 py-2"
              onClick={handleAddNote}
            >
              <FiPlus /> Guardar nota
            </button>
          </div>
        </div>
      )}
    </div>
  );
}