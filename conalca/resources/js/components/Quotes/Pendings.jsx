import React, { useState, useEffect } from 'react';
import { usePendings } from '../../services/usePendings';
import { FaCheck, FaTrash } from 'react-icons/fa';

export default function Pendings({ groupCotizationId, userId }) {
  const [form, setForm] = useState({ title: '', description: '' });
  const [adding, setAdding] = useState(false);

  const {
    pendings, loading,
    fetchPendings, addPending, editPending, removePending
  } = usePendings(groupCotizationId);

  useEffect(() => { fetchPendings(); }, [fetchPendings]);

  const handleAdd = async (e) => {
    e.preventDefault();
    await addPending({
      title: form.title,
      description: form.description,
      group_cotization_id: groupCotizationId,
    });
    setForm({ title: '', description: '' });
    setAdding(false);
  };

  const handleDone = async (pending) => {
    await editPending(pending.id, { done: !pending.done });
  };

  const handleDelete = async (id) => {
    if (window.confirm("¿Eliminar pendiente?")) {
      await removePending(id);
    }
  };

  return (
    <div>
      {loading && <div className="text-xs text-gray-400 py-2">Cargando pendientes...</div>}

      <ul className="mb-2">
        {pendings.length === 0 && !adding && (
          <li className="text-xs text-gray-400 pl-2 py-2">Sin pendientes.</li>
        )}
        {pendings.map(p => (
          <li key={p.id} className="mb-2 flex items-start gap-2 bg-gray-50 rounded p-2">
            <span
              onClick={() => handleDone(p)}
              className={`cursor-pointer rounded-full p-1 ${p.done ? "bg-green-200 text-green-700" : "bg-yellow-100 text-yellow-700"}`}
              title={p.done ? "Marcar como pendiente" : "Marcar como hecho"}
            >
              <FaCheck />
            </span>
            <div className="flex-1">
              <div className="font-semibold line-clamp-1">{p.title}</div>
              <div className="text-xs text-gray-500 line-clamp-2">{p.description}</div>
              {/* No es necesario mostrar el usuario creador */}
            </div>
            {/* Solo yo veo mis pendientes, así que siempre puedo borrar */}
            <button
              onClick={() => handleDelete(p.id)}
              className="text-red-600 hover:text-red-900 ml-2"
              title="Eliminar pendiente"
            >
              <FaTrash />
            </button>
          </li>
        ))}
      </ul>

      {adding ? (
        <form onSubmit={handleAdd} className="mt-2 flex flex-col gap-1">
          <input
            className="border rounded p-1"
            value={form.title}
            onChange={e => setForm(f => ({ ...f, title: e.target.value }))}
            required
            maxLength={255}
            placeholder="Título del pendiente"
          />
          <textarea
            className="border rounded p-1"
            rows={2}
            value={form.description}
            onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
            placeholder="Descripción (opcional)"
            maxLength={500}
          />
          <div className="flex gap-2 mt-1">
            <button
              type="submit"
              className="bg-[#FF7C32] text-white rounded px-2 py-1 font-bold text-xs"
            >
              Agregar
            </button>
            <button
              type="button"
              className="border rounded px-2 py-1 text-xs"
              onClick={() => setAdding(false)}
            >
              Cancelar
            </button>
          </div>
        </form>
      ) : (
        <button
          className="bg-[#FF7C32]/10 hover:bg-[#FF7C32]/20 text-[#FF7C32] rounded px-4 py-1 text-sm mt-2 font-bold"
          onClick={() => setAdding(true)}
        >
          + Agregar pendiente
        </button>
      )}
    </div>
  );
}