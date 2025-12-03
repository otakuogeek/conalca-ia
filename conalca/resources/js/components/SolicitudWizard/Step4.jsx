import React,{useState,useEffect} from 'react';
import PropTypes from 'prop-types';
import {chatBus} from './ChatBox';
import {
  FiBox,          // título “contenedor”
  FiChevronLeft,  // botón atrás
  FiChevronRight  // botón siguiente
} from 'react-icons/fi';

const buildStep4State = (formData = {}, contenedor = {}) => {
  const valueFromRelation =
    typeof contenedor === 'string'
      ? contenedor
      : contenedor?.contenedor;

  return {
    contenedor: formData.contenedor || valueFromRelation || 'NO'
  };
};

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

export default function Step4({data={},formData={},onNext,onPrev,loading}){

  const co=data.contenedor||{};
  const [showDebug, setShowDebug] = useState(false);
  const [form, setForm] = useState(buildStep4State(formData, co));

  useEffect(() => {
    setForm(buildStep4State(formData, data.contenedor || {}));
  }, [formData, data]);

  useEffect(()=>{
    const f=(k,v)=>setForm(p=>({...p,[k]:v}));
    chatBus.on('fill-field',f);
    return()=>chatBus.off('fill-field',f);
  },[]);

  const submit=e=>{e.preventDefault(); onNext(form);};

  return (
    <form onSubmit={submit} className="space-y-10 text-center">
      {/* Título */}
      <h2 className="text-2xl font-semibold flex items-center justify-center gap-2 text-gray-800">
        <FiBox className="text-orange-500" /> Paso&nbsp;4 – ¿Usa contenedor?
      </h2>

      {/* Selector SI / NO */}
      <div>
        <label
          htmlFor="contenedor"
          className="block text-sm font-semibold mb-2 text-gray-800"
        >
          ¿El flete utiliza contenedor?
        </label>

        <select
          id="contenedor"
          name="contenedor"
          value={form.contenedor}
          onChange={e =>
            setForm(p => ({
              ...p,
              contenedor: e.target.value
            }))
          }
          className="border border-orange-300 rounded px-4 py-2 w-40 mx-auto focus:outline-none focus:ring-2 focus:ring-orange-500"
          required
        >
          <option value="SI">Sí</option>
          <option value="NO">No</option>
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
    </form>
  );
}

Step4.propTypes={
  data:PropTypes.object,onNext:PropTypes.func.isRequired,
  onPrev:PropTypes.func.isRequired,loading:PropTypes.bool
};