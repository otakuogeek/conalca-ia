import React,{useState,useEffect} from 'react';
import PropTypes from 'prop-types';
import {chatBus} from './ChatBox';
import {
  FiGlobe,        // título y label modalidad internacional
  FiChevronLeft,  // botón atrás
  FiChevronRight  // botón siguiente
} from 'react-icons/fi';

export default function Step5({data={},onNext,onPrev,loading}){

  const i=data.internacional||{};
  const [form,setForm]=useState({
     modalidad_internacional: i.modalidad_internacional || ''
  });

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

Step5.propTypes={
  data:PropTypes.object,onNext:PropTypes.func.isRequired,
  onPrev:PropTypes.func.isRequired,loading:PropTypes.bool
};
