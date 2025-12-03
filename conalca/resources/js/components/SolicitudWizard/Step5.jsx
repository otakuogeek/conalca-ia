import React,{useState,useEffect} from 'react';
import PropTypes from 'prop-types';
import {chatBus} from './ChatBox';
import {
  FiGlobe,        // título y label modalidad internacional
  FiChevronLeft,  // botón atrás
  FiChevronRight  // botón siguiente
} from 'react-icons/fi';

const buildStep5State = (formData = {}, data = {}) => ({
  modalidad_internacional:
    formData.modalidad_internacional ||
    data.modalidad_internacional ||
    (data.internacional && data.internacional.modalidad_internacional) ||
    ''
});

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

export default function Step5({data={},formData={},onNext,onPrev,loading}){

  const i=data.internacional||{};
  // const [form,setForm]=useState({
  //    modalidad_internacional: formData.modalidad_internacional || i.modalidad_internacional || ''
  // });

  const [showDebug, setShowDebug] = useState(false);
  const [form, setForm] = useState(buildStep5State(formData, data));

  useEffect(() => {
    setForm(buildStep5State(formData, data));
  }, [formData, data]);

  useEffect(() => {
    const fill = (field, value) => {
      setForm(prev => ({ ...prev, [field]: value }));
    };
    chatBus.on('fill-field', fill);
    return () => chatBus.off('fill-field', fill);
  }, []);

  const submit=e=>{e.preventDefault(); onNext(form);};

  return (
    <form onSubmit={submit} className="space-y-10 text-center">
      {/* Título */}
      <h2 className="text-2xl font-semibold flex items-center justify-center gap-2 text-gray-800">
        <FiGlobe className="text-orange-500" /> Paso&nbsp;5 – Modalidad internacional
      </h2>

      {/* Selector modalidad */}
      <div>
        <label
          htmlFor="modalidad_internacional"
          className="block text-sm font-semibold mb-2 text-gray-800"
        >
          <FiGlobe className="inline text-orange-500 mr-1" />
          Modalidad internacional
        </label>

        <select
          id="modalidad_internacional"
          name="modalidad_internacional"
          value={form.modalidad_internacional}
          onChange={e =>
            setForm(p => ({
              ...p,
              modalidad_internacional: e.target.value
            }))
          }
          className="border border-orange-300 rounded px-4 py-2 w-56 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
          required
        >
          <option value="">Seleccione…</option>
          <option>OTM</option>
          <option>DTA</option>
          <option>DTAI</option>
          <option>NACIONALIZADA</option>
        </select>
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
      {/* ────────── Debug tools ────────── */}
      {/* <div className="pt-4 border-t border-dashed border-gray-200">
        <button
          type="button"
          onClick={() => setShowDebug(v => !v)}
          className="text-xs uppercase tracking-wide text-red-500 border border-red-300 px-3 py-1 rounded-md hover:bg-red-50"
        >
          {showDebug ? 'Hide debug snapshot' : 'Show debug snapshot'}
        </button>

        {showDebug && (
          <div className="mt-4 text-left text-xs bg-gray-900 text-green-200 rounded-lg p-3 space-y-2">
            <p className="font-semibold text-red-300">🪲 Step5 debug</p>
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
                {JSON.stringify({
                  flat_modalidad: data.modalidad_internacional,
                  internacional: data.internacional
                }, null, 2)}
              </pre>
            </div>
          </div>
        )}
      </div> */}
    </form>
  );
}

Step5.propTypes={
  data:PropTypes.object,onNext:PropTypes.func.isRequired,
  onPrev:PropTypes.func.isRequired,loading:PropTypes.bool
};
