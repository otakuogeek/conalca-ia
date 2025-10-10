// resources/js/components/SolicitudWizard/Wizard.jsx
import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import EventEmitter from 'eventemitter3';
import {
  saveStep,
  fetchSolicitud,
  fetchPrefill      // ← NUEVO
} from '../../api/solicitud';

import Step1 from './Step1';
import Step2 from './Step2';
import Step3 from './Step3';
import Step4 from './Step4';
import Step5 from './Step5';
import Step6 from './Step6';

import ChatBox, { chatBus } from './ChatBox';
import { createPortal } from 'react-dom';

export const bus = new EventEmitter();        // wizard-bus

const TOTAL = 6;

/* ───── helpers ─────────────────────────────────────────── */

// campos de cada paso para re-estructurar el prefill plano
const STEP2 = ['origen','destino','cantidad_mercancia','peso','valor_mercancia',
  'producto','empaque','cantidad_vehiculos','clase_vehiculo','carroceria',
  'minimo_modelo','tipo_flete','flete_conductor','flete_ministerio','tipo_tarifa',
  'tarifa_cliente','cargue_cuenta_de','descargue_cuenta_de','seguro_cuenta_de',
  'descripcion_mercancia','kit_seguridad','tipo_remesa_rndc'];

const STEP3 = ['fecha_cargue','hora_cargue','remitente','destinatario','contacto',
  'promesa_servicio','documento_transporte','observacion_cargue'];

const STEP4 = ['contenedor'];
const STEP5 = ['modalidad_internacional'];
const STEP6 = [
  'vehiculo_acom',                         // ← antiguo (por si existe)
  'itesoltra_vehiculoacompanamiento',
  'tipaco_codigo',
  'itesoltra_acompanamientocuentade',
  'itesoltra_acompanamientovalor'
];

/* Convierte el objeto plano devuelto por /prefill
   al formato que usan los componentes (detalle, cargue, etc.) */
const reshapePrefill = (pref) => {
  const out = {
    detalle        : {},
    cargue         : {},
    contenedor     : {},
    internacional  : {},
    acompanamiento : {}
  };

  for (const [k, v] of Object.entries(pref)) {
    if (STEP2.includes(k))           out.detalle[k]        = v;
    else if (STEP3.includes(k))      out.cargue[k]         = v;
    else if (STEP4.includes(k))      out.contenedor[k]     = v;
    else if (STEP5.includes(k))      out.internacional[k]  = v;
    else if (STEP6.includes(k))      out.acompanamiento[k] = v;
    else                             out[k]                = v;  // paso 1
  }
  return out;
};

/* Emite TODOS los campos (también los anidados) al chatBus
   para que los Step*.jsx y el ChatBox los conozcan.         */
const emitAll = (obj) => {
  const walk = (o) => {
    if (o && typeof o === 'object')
      Object.entries(o).forEach(([k,v]) =>
        (v && typeof v === 'object') ? walk(v)
                                     : chatBus.emit('fill-field', k, String(v)));
  };
  walk(obj);
};

/* ───── componente ───────────────────────────────────────── */

export default function Wizard({ open, cotizacionId, onClose }) {
  const [current, setCurrent] = useState(1);
  const [localData, setLocal] = useState({});
  const [loading, setLoading] = useState(false);

  /* ─── al cambiar de cotización ────────────────────────── */
  useEffect(() => {
    if (!cotizacionId) return;

    const load = async () => {
      setCurrent(1);
      /* 1) ¿ya existe una Solicitud? */
      const { data: solicitud } = await fetchSolicitud(cotizacionId);

      if (solicitud) {
        setLocal(solicitud);
        // COMENTADO: No emitir automáticamente, solo cuando el usuario lo pida
        // emitAll(solicitud);
      } else {
        /* 2) si no, traemos el pre-fill plano y lo re-armamos */
        const { data: pre } = await fetchPrefill(cotizacionId);
        const reshaped = reshapePrefill(pre);
        setLocal(reshaped);
        // COMENTADO: No emitir automáticamente, solo cuando el usuario lo pida
        // emitAll(pre);            // se emite el plano (el bot sólo necesita pares k/v)
      }
    };

    load();
  }, [cotizacionId]);

  /* ─── escuchar rellenos on-the-fly desde ChatBox ──────── */
  useEffect(() => {
    const fn = (f, v) => setLocal(p => ({ ...p, [f]: v }));
    chatBus.on('fill-field', fn);
    return () => chatBus.off('fill-field', fn);
  }, []);

  /* ─── guardar cada paso ───────────────────────────────── */
  // const sendStep = (stepKey, fields) => {
  //   setLoading(true);
  //   saveStep({ step: stepKey, CotizacionModelId: cotizacionId, ...fields })
  //     .then(() => {
  //       if (current < TOTAL) {
  //         setCurrent(c => c + 1);          // avanza al siguiente paso
  //       } else {
  //         onClose();                       // cierra el modal
  //         window.location.reload();        // ← recarga la vista
  //       }
  //     })
  //     .finally(() => setLoading(false));
  // };

  const sendStep = async (stepKey, fields) => {
    setLoading(true);

    try {
      const { data: resp } = await saveStep({
        step             : stepKey,
        CotizacionModelId: cotizacionId,
        ...fields
      });

      /* ─── SOLO EN EL PASO 6 mostramos la alerta ─── */
      if (stepKey === 'step_6') {
        await showSilogtranMsg(resp);
      }

      /* navegación normal del wizard */
      if (current < TOTAL) {
        setCurrent(c => c + 1);
      } else {
        onClose();                    // cierra modal al terminar
        window.location.reload();     // si todavía lo necesitas
      }
    } catch (err) {
      console.error('Error guardando paso', err);
      alert('⚠️  Error guardando la solicitud. Intenta de nuevo.');
    } finally {
      setLoading(false);
    }
  };

  const commonProps = { loading, data: localData };

  const render = () => {
    switch (current) {
      case 1: return <Step1 {...commonProps}
                            onNext={d => sendStep('step_1', d)} />;
      case 2: return <Step2 {...commonProps}
                            onPrev={() => setCurrent(1)}
                            onNext={d => sendStep('step_2', d)} />;
      case 3: return <Step3 {...commonProps}
                            onPrev={() => setCurrent(2)}
                            onNext={d => sendStep('step_3', d)} />;
      case 4: return <Step4 {...commonProps}
                            onPrev={() => setCurrent(3)}
                            onNext={d => sendStep('step_4', d)} />;
      case 5: return <Step5 {...commonProps}
                            onPrev={() => setCurrent(4)}
                            onNext={d => sendStep('step_5', d)} />;
      case 6: return <Step6 {...commonProps}
                            onPrev={() => setCurrent(5)}
                            onNext={d => sendStep('step_6', d)} />;
      default: return null;
    }
  };

  /* ─── modal ───────────────────────────────────────────── */
  if (!open) return null;

  /* -------------------------------------------------------------- */
  /*  Muestra al usuario la respuesta de Silogtran (éxito o error)  */
  /* -------------------------------------------------------------- */
  const showSilogtranMsg = async (apiResp) => {
    if (!apiResp) return;

    /* 1) error devuelto por el WS → viene en advertencia           */
    if (apiResp.advertencia) {
      await new Promise(r => {
        alert(`⚠️  ${apiResp.advertencia}`);   // ⇒ modal nativo; cámbialo por SweetAlert si lo usas
        r();
      });
      return;
    }

    /* 2) éxito: mensaje en apiResp.silogtran o apiResp.message     */
    const okMsg = apiResp.silogtran || apiResp.message;
    if (okMsg) {
      await new Promise(r => {
        alert(`✅  ${okMsg}`);
        r();
      });
    }
  };

  const portalTarget = document.getElementById('modal-root') || document.body;

  return createPortal(
    (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div className="relative bg-white w-full max-w-6xl rounded-2xl
                        flex flex-col md:flex-row max-h-[90vh] my-8 mx-2">

          <div className="w-full md:w-1/2 overflow-y-auto p-4 md:p-6
                          max-h-[60vh] md:max-h-[90vh]">
            {render()}
          </div>

          <div className="w-full md:w-1/2 border-t md:border-t-0 md:border-l">
            <ChatBox />
          </div>

          <button
            onClick={onClose}
            className="absolute top-2 right-2 md:top-4 md:right-4
                       text-3xl font-bold text-gray-600 hover:text-red-500">
            ✕
          </button>
        </div>
      </div>
  ),
  portalTarget
  );
}

Wizard.propTypes = {
  open        : PropTypes.bool.isRequired,
  cotizacionId: PropTypes.number,
  onClose     : PropTypes.func.isRequired,
};