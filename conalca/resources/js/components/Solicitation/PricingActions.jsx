import { useState } from 'react';
import PricingDetailForm from './PricingDetailForm';
import { assignPrice, escalateRequest } from '../../services/solicitations';
import { FiEdit2, FiChevronUp, FiSave, FiFileText } from 'react-icons/fi';

export default function PricingActions({ solicitation, onRefresh, onListRefresh }) {
  const [price, setPrice] = useState(solicitation.price || '');
  const [note, setNote] = useState(solicitation.pricing_note || '');
  const [open, setOpen] = useState(false);

  const save = async () => {
    await assignPrice(solicitation.id, { price, pricing_note: note });
    onRefresh();
    onListRefresh?.(); 
  };

  const escalate = async () => {
    await escalateRequest(solicitation.id);
    onRefresh();
    onListRefresh?.(); 
  };

  return (
    <div className="bg-white border border-gray-100 rounded-lg shadow-sm p-5 mb-3 max-w-xl">
      <h5 className="font-bold mb-4 text-[#FF7C32] text-lg flex items-center gap-2">
        <FiFileText /> Herramientas de pricing
      </h5>
      <div className="flex flex-col md:flex-row gap-2 mb-3">
        <input
          type="number"
          min="0"
          step="0.01"
          value={price}
          onChange={e => setPrice(e.target.value)}
          placeholder="Precio"
          className="border border-gray-300 rounded-md px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-[#FF7C32] text-sm transition"
        />
        <input
          value={note}
          onChange={e => setNote(e.target.value)}
          placeholder="Nota interna"
          className="border border-gray-300 rounded-md px-3 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-[#FF7C32] text-sm transition"
          maxLength={255}
        />
      </div>
      <div className="flex flex-col md:flex-row gap-2">
        <button
          onClick={save}
          className="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white font-medium rounded px-4 py-2 text-sm transition"
        >
          <FiSave /> Guardar
        </button>
        { solicitation.price ? null :
          <button
            onClick={escalate}
            className="flex items-center gap-1 bg-gray-500 hover:bg-amber-600 text-white font-medium rounded px-4 py-2 text-sm transition"
          >
            <FiChevronUp /> Escalar a SA
          </button>
        }
        { solicitation.pricing_id ? null :
          <button
            onClick={() => setOpen(true)}
            className="flex items-center gap-1 bg-gray-500 hover:bg-indigo-600 text-white font-medium rounded px-4 py-2 text-sm transition"
          >
            <FiEdit2 />
            
            Agregar registro a Pricings
          </button>
        }
      </div>

      {open && (
        <div className="mt-4">
          <PricingDetailForm
            solicitation={solicitation}
            onSaved={onRefresh}
            onListRefresh={onListRefresh}
            onClose={() => setOpen(false)}
          />
        </div>
      )}
    </div>
  );
}