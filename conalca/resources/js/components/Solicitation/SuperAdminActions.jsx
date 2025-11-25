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
    <div className="border p-3 rounded mb-3">
      <h5 className="font-bold mb-2">Super Admin note</h5>
      <textarea value={note} onChange={e=>setNote(e.target.value)}
                className="border w-full p-1 mb-2" rows={3}/>
      <button onClick={send} className="bg-blue-700 text-white px-3">
        Add Note & Send
      </button>
    </div>
  );
}