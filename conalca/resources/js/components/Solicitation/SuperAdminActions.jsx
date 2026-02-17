import { useState } from 'react';
import { addSuperNote } from '../../services/solicitations';

export default function SuperAdminActions({ solicitation, onRefresh, onListRefresh }) {
  const [note, setNote] = useState('');

  const send = async () => {
    if (!note.trim()) return;
    await addSuperNote(solicitation.id, note);
    setNote('');
    onRefresh();
    onListRefresh?.();
  };

  return (
    <div className="bg-white rounded-lg p-5 border border-gray-200 shadow-sm mb-3" style={{ fontFamily: 'Product Sans, sans-serif' }}>
      <h5 className="font-semibold mb-3 text-gray-700" style={{ fontFamily: 'Product Sans, sans-serif' }}>Nota de Super Admin</h5>
      <textarea value={note} onChange={e=>setNote(e.target.value)}
                className="border border-gray-300 rounded-lg w-full px-4 py-3 mb-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition-all" style={{ fontFamily: 'Product Sans, sans-serif' }} rows={3} placeholder="Escribe una nota..."/>
      <button onClick={send} className="bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg px-4 py-2 text-sm transition-all duration-200 shadow-sm hover:shadow-md">
        Enviar Nota
      </button>
    </div>
  );
}