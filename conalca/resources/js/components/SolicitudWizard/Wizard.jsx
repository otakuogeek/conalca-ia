// resources/js/components/SolicitudWizard/Wizard.jsx
import React, { useState, useEffect } from 'react';
import PropTypes   from 'prop-types';
import EventEmitter from 'eventemitter3';
import {
  saveStep,
  fetchSolicitud,
  fetchPrefill,      // ← NUEVO
  fetchGroupPrefill  // ← NUEVO PARA GRUPOS
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
const STEP1_FIELDS = [
  'tipo_viaje', 'moneda', 'fuente_solicitud', 'condicion_despacho',
  'condicion_facturacion', 'ciudad_facturacion', 'ciudad_facturacion_label',
  'vendedor', 'vendedor_label', 'cliente_codigo', 'cliente_nombre',
  'tipo_operacion', 'centro_costo_despacho', 'operation_flow'
];
const STEP2 = ['origen','origen_label','destino','destino_label',
  'cantidad_mercancia','peso','valor_mercancia',
  'producto','producto_label','empaque','empaque_label',
  'cantidad_vehiculos','clase_vehiculo','clase_vehiculo_label',
  'carroceria','carroceria_label',
  'minimo_modelo','tipo_flete','flete_conductor','flete_ministerio','tipo_tarifa',
  'tarifa_cliente','cargue_cuenta_de','descargue_cuenta_de','seguro_cuenta_de',
  'descripcion_mercancia','kit_seguridad','tipo_remesa_rndc',
  'lugar_recogida_contenedor','lugar_recogida_contenedor_label','tipo_carga','sub_cliente'];

const STEP3 = ['fecha_cargue','hora_cargue','remitente','remitente_label',
  'destinatario','destinario','destinario_label',
  'contacto','promesa_servicio','documento_transporte','observacion_cargue',
  'tiempo_cargue_pactado','tiempo_descargue_pactado',
  'fecha_cita_descargue','hora_cita_descargue'];

const STEP4 = ['contenedor'];
const STEP5 = ['modalidad_internacional'];
const STEP6 = [
  'vehiculo_acom',
  'tipo_vehiculo_acom', 
  'acompanamiento_cuenta_acom',
  'valor_acompanante_acom'
];

const fieldToStepKey = field => {
  if (STEP2.includes(field)) return 'step2';
  if (STEP3.includes(field)) return 'step3';
  if (STEP4.includes(field)) return 'step4';
  if (STEP5.includes(field)) return 'step5';
  if (STEP6.includes(field)) return 'step6';
  if (STEP1_FIELDS.includes(field)) return 'step1';
  return null; // ignore unknown fields
};

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

const mergeMissingSteps = (solicitud, fallbackPrefill) => {
  if (!fallbackPrefill) return solicitud;

  const merged = { ...solicitud };

  if (!merged.detalle || !Object.keys(merged.detalle).length) {
    merged.detalle = fallbackPrefill.detalle;
  }
  if (!merged.cargue || !Object.keys(merged.cargue).length) {
    merged.cargue = fallbackPrefill.cargue;
  }
  if (!merged.contenedor || !Object.keys(merged.contenedor).length) {
    merged.contenedor = fallbackPrefill.contenedor;
  }
  if (!merged.internacional || !Object.keys(merged.internacional).length) {
    merged.internacional = fallbackPrefill.internacional;
  }
  if (!merged.acompanamiento || !Object.keys(merged.acompanamiento).length) {
    merged.acompanamiento = fallbackPrefill.acompanamiento;
  }

  const fallbackIntl = fallbackPrefill.internacional || {};
  if (
    fallbackIntl.modalidad_internacional &&
    (!merged.internacional ||
     merged.internacional.modalidad_internacional === '' ||
     merged.internacional.modalidad_internacional === null ||
     merged.internacional.modalidad_internacional === 'null')
  ) {
    merged.internacional = {
      ...(merged.internacional || {}),
      modalidad_internacional: fallbackIntl.modalidad_internacional
    };
  }

  if (
    fallbackPrefill.modalidad_internacional &&
    (!merged.modalidad_internacional ||
     merged.modalidad_internacional === '' ||
     merged.modalidad_internacional === 'null')
  ) {
    merged.modalidad_internacional = fallbackPrefill.modalidad_internacional;
  }

  return merged;
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

export default function Wizard({ open, cotizacionId, groupId, onClose }) {
  const [current, setCurrent] = useState(1);
  const [localData, setLocal] = useState({});
  const [loading, setLoading] = useState(false);
  
  // Estado para persistir datos del formulario entre pasos
  const [formData, setFormData] = useState({
    step1: {},
    step2: {},
    step3: {},
    step4: {},
    step5: {},
    step6: {}
  });

  useEffect(() => {
    const handleChatFill = (field, value) => {
      const stepKey = fieldToStepKey(field);
      if (!stepKey) return;

      setFormData(prev => ({
        ...prev,
        [stepKey]: {
          ...prev[stepKey],
          [field]: value
        }
      }));
    };

    chatBus.on('fill-field', handleChatFill);
    return () => chatBus.off('fill-field', handleChatFill);
  }, []);

  // Emitir cambio de paso al ChatBox
  useEffect(() => {
    console.log('📍 Wizard: Emitiendo cambio de paso a', current);
    chatBus.emit('step-changed', current);
  }, [current]);

    useEffect(() => {
      if (!cotizacionId && !groupId) return;

      const load = async () => {
        setCurrent(1);

        // reset cached steps each time we load a new cotización/grupo
        setFormData({
          step1: {},
          step2: {},
          step3: {},
          step4: {},
          step5: {},
          step6: {}
        });

        try {
          /* 1) Always try to bring the existing solicitud (if any) */
          if (cotizacionId) {
            const { data: solicitud } = await fetchSolicitud(cotizacionId);

            if (solicitud) {
              let enriched = solicitud;

              const needsFallback =
                (!solicitud.detalle || !Object.keys(solicitud.detalle).length) ||
                (!solicitud.cargue || !Object.keys(solicitud.cargue).length) ||
                (!solicitud.contenedor || !Object.keys(solicitud.contenedor).length) ||
                (!solicitud.internacional || !Object.keys(solicitud.internacional).length) ||
                (!solicitud.acompanamiento || !Object.keys(solicitud.acompanamiento).length) ||
                // ← NEW: modalidad exists but is empty
                (!solicitud.internacional ||
                !solicitud.internacional.modalidad_internacional ||
                solicitud.internacional.modalidad_internacional === 'null');

              if (needsFallback) {
                const { data: prefill } = await fetchPrefill(cotizacionId);
                const fallback = reshapePrefill(prefill);
                enriched = mergeMissingSteps(solicitud, fallback);
              }

              setLocal(enriched);
              emitAll(enriched);
              return;
            }
          }

          /* 2) No solicitud yet → use group prefill if available */
          if (groupId) {
            console.log('🔄 Loading prefill from group:', groupId);

            const { data: prefillData } = await fetchGroupPrefill(groupId);
            console.log('✅ Prefill (group) loaded:', prefillData);

            const shaped = reshapePrefill(prefillData);
            setLocal(shaped);
            emitAll(shaped);

            return;
          }

          /* 3) Fallback to single-cotización prefill */
          if (cotizacionId) {
            const { data: prefill } = await fetchPrefill(cotizacionId);
            const reshaped = reshapePrefill(prefill);
            setLocal(reshaped);
            emitAll(prefill); // only if you still need the flat data in ChatBox
          }
        } catch (error) {
          console.error('❌ Error loading wizard data:', error);
        }
      };

      load();
    }, [cotizacionId, groupId]);

  /* ─── escuchar rellenos on-the-fly desde ChatBox ──────── */
  useEffect(() => {
    const fn = (f, v) => setLocal(p => ({ ...p, [f]: v }));
    chatBus.on('fill-field', fn);
    return () => chatBus.off('fill-field', fn);
  }, []);

  /* ─── guardar cada paso ───────────────────────────────── */

  const sendStep = async (stepKey, fields) => {
    setLoading(true);

    // Persist the snapshot of this step locally (formData) so it can be reloaded if user comes back
    const stepNumber = stepKey.replace('step_', '');
    setFormData(prev => ({
      ...prev,
      [`step${stepNumber}`]: fields
    }));

    try {
      const { data: resp } = await saveStep({
        step             : stepKey,
        CotizacionModelId: cotizacionId,
        ...fields
      });

      /* ─── ONLY on step 6 do we show the Silogtran result ─── */
      if (stepKey === 'step_6') {
        await showSilogtranMsg(resp);
      }

      // Normal wizard navigation
      if (current < TOTAL) {
        setCurrent(c => c + 1);
      } else {
        onClose();                    // close modal when all steps are done
        window.location.reload();     // remove if you prefer to refresh data manually
      }
    } catch (err) {
      console.error('Error saving step', err);
      alert('⚠️ Error saving the request. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const goToPrevStep = (currentStepFields = {}) => {
    // Guardar datos actuales del paso antes de ir hacia atrás
    const stepNumber = current;
    setFormData(prev => ({
      ...prev,
      [`step${stepNumber}`]: { ...prev[`step${stepNumber}`], ...currentStepFields }
    }));
    
    if (current > 1) {
      setCurrent(current - 1);
    }
  };

  // Función para navegar hacia adelante sin guardar
  const goToNextStep = (currentStepFields = {}) => {
    // Guardar datos actuales del paso antes de ir hacia adelante
    const stepNumber = current;
    setFormData(prev => ({
      ...prev,
      [`step${stepNumber}`]: { ...prev[`step${stepNumber}`], ...currentStepFields }
    }));
    
    if (current < TOTAL) {
      setCurrent(current + 1);
    }
  };

  const commonProps = { 
    loading, 
    data: localData, 
    formData: formData[`step${current}`] || {},  // Datos persistidos del paso actual
    goToPrevStep,
    goToNextStep
  };

  const render = () => {
    switch (current) {
      case 1: return <Step1 {...commonProps}
                            onNext={d => sendStep('step_1', d)} />;
      case 2: return <Step2 {...commonProps}
                            onPrev={(fields) => goToPrevStep(fields)}
                            onNext={d => sendStep('step_2', d)} />;
      case 3: return <Step3 {...commonProps}
                            onPrev={(fields) => goToPrevStep(fields)}
                            onNext={d => sendStep('step_3', d)} />;
      case 4: return <Step4 {...commonProps}
                            onPrev={(fields) => goToPrevStep(fields)}
                            onNext={d => sendStep('step_4', d)} />;
      case 5: return <Step5 {...commonProps}
                            onPrev={(fields) => goToPrevStep(fields)}
                            onNext={d => sendStep('step_5', d)} />;
      case 6: return <Step6 {...commonProps}
                            onPrev={(fields) => goToPrevStep(fields)}
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
      <div className="fixed inset-0 z-[99990] flex items-start justify-center pt-4 bg-black/50 overflow-y-auto backdrop-blur-sm">
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
                       w-8 h-8 rounded-full bg-white bg-opacity-20 backdrop-blur-sm
                       text-white hover:bg-opacity-30 transition-all duration-200
                       flex items-center justify-center text-xl font-bold">
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
  groupId     : PropTypes.number,  // ← NUEVO
  onClose     : PropTypes.func.isRequired,
};