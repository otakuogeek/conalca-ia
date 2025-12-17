import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import Pendings from './Pendings';
import NotesPanel from '../Cotizations/NotesPanel';
import CallPanel from '../Calls/CallPanel';
import { FaTruckMoving, FaTruck } from 'react-icons/fa';

export default function TransitGroupModal({ open, onClose, group }) {
  const [selectedModel, setSelectedModel] = useState(null);

  if (!open) return null;

  /*  ────────────────────────────────────────────────────────── */
  /*  Datos derivados                                           */
  /*  ────────────────────────────────────────────────────────── */
  const acceptedQuotes = (group?.cotizaciones || []).filter(
    q => q.decision_cliente === 'aceptada'
  );

  /*  ────────────────────────────────────────────────────────── */
  /*  Render                                                    */
  /*  ────────────────────────────────────────────────────────── */
  const portalTarget = document.getElementById('modal-root') || document.body;
  return ReactDOM.createPortal(
    <div
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
      className="fixed inset-0 z-[99990] flex items-start sm:items-start justify-center pt-4 overflow-y-auto backdrop-blur-sm"
    >
      {/* fondo oscurecido */}
      <div
        className="fixed inset-0 bg-gray-900/70"
        onClick={onClose}
      />

      {/* contenedor modal */}
      <article
        className="relative z-[99991] w-full max-w-[95vw] sm:max-w-3xl lg:max-w-6xl 2xl:max-w-7xl my-4
                   max-h-[92vh] rounded-2xl bg-white shadow-2xl border border-gray-100 flex flex-col"
      >
        {/* header fijo con botón cerrar */}
        <header className="flex-shrink-0 relative p-4 border-b border-gray-100">
          <button
            onClick={onClose}
            className="absolute top-3 right-3 p-2 rounded-full bg-gray-100 hover:bg-red-50 transition z-10"
          >
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              className="text-gray-600 hover:text-red-500 transition"
            >
              <path
                fill="currentColor"
                d="M13.414 12 17.707 7.707a1 1 0 0 0-1.414-1.414L12 10.586 7.707 6.293A1 1 0 0 0 6.293 7.707L10.586 12l-4.293 4.293a1 1 0 1 0 1.414 1.414L12 13.414l4.293 4.293a1 1 0 0 0 1.414-1.414L13.414 12Z"
              />
            </svg>
          </button>
        </header>

        {/* contenido con scroll */}
        <section className="flex-1 overflow-y-auto p-4 sm:p-6">
          {/* GRID PRINCIPAL RESPONSIVE */}
          <div
            className="grid gap-6
                       grid-cols-1
                       lg:grid-cols-2
                       2xl:grid-cols-3"
          >
            {/* ────────────────────── COL-1 ────────────────────── */}
            <div className="flex flex-col gap-6">
              {/* Resumen cotización --------------------------------------------------- */}
              <div className="w-full rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
                <header className="mb-6">
                  <div className="flex items-center gap-3 mb-4">
                    <span className="w-3 h-3 rounded-full bg-blue-500" />
                    <p className="flex flex-wrap text-xs text-gray-500 uppercase gap-6 font-semibold tracking-wide">
                      <span>N° Cotización</span>
                      <span className="hidden sm:inline">Nombre de la empresa</span>
                    </p>
                  </div>

                  <h2 className="text-base md:text-lg font-bold text-gray-800 flex flex-wrap items-center gap-3">
                    <span className="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200 shadow-sm">
                      ID {group?.id}
                    </span>
                    <span
                      className="truncate uppercase text-gray-700 text-sm"
                      title={group?.client?.name}
                    >
                      {group?.client?.name}
                    </span>
                  </h2>
                </header>

                {/* Cotizaciones del grupo (estado Silogtran) */}
                <div className="space-y-3">
                  {(group?.cotizaciones || [])
                    .filter(cot => {
                      const status = cot.solicitud?.silogtran_status || cot.silogtran_status || 'Sin estado';
                      return status !== 'Sin estado';
                    })
                    .map(cot => {
                    const status =
                      cot.solicitud?.silogtran_status ||
                      cot.silogtran_status ||
                      'Sin estado';
                    const route =
                      cot.ruta ||
                      (cot.ciudad_origen && cot.ciudad_destino
                        ? `${cot.ciudad_origen}-${cot.ciudad_destino}`
                        : 'Ruta N/D');

                    /* clases de color dinámicas */
                    const byStatus = {
                      'En proceso':
                        'bg-blue-50 text-blue-700 border-blue-200',
                      Completado:
                        'bg-green-50 text-green-700 border-green-200',
                      Pendiente:
                        'bg-yellow-50 text-yellow-700 border-yellow-200',
                      'Sin estado':
                        'bg-gray-50 text-gray-600 border-gray-200'
                    };
                    const color = byStatus[status] || byStatus['Sin estado'];

                    return (
                      <div
                        key={cot.id}
                        className="flex flex-col gap-3 p-4 rounded-xl border border-gray-200 hover:bg-gray-50 hover:shadow-sm transition-all duration-200 bg-white"
                      >
                        {/* Primera fila: COT y Ruta */}
                        <div className="flex items-center justify-between gap-3">
                          <span className="inline-flex items-center px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 shadow-sm">
                            COT #{cot.id}
                          </span>
                          
                        </div>
                        
                        {/* Segunda fila: Estado */}
                        <div className="flex justify-end">
                          <span
                            className={`inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border shadow-sm ${color}`}
                          >
                            {status}
                          </span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

             {/* Datos empresa -------------------------------------------------------- */}
              <div className="w-full rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
                <h3 className="flex items-center gap-3 mb-6 text-sm font-bold text-gray-600 uppercase tracking-wide">
                  <span className="w-3 h-3 rounded-full bg-blue-500" />
                  Datos empresa
                </h3>

                {/* Razón social o contacto */}
                <div className="mb-5 p-3 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200">
                  <p className="text-base font-bold text-gray-800 uppercase truncate">
                    {group?.client?.cliente || group?.client?.contacto || '-'}
                  </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <Field label="NIT" value={group?.client?.documento} />

                  <Field label="Teléfonos" value={group?.client?.telefono} />

                  <Field label="Dirección" value={group?.client?.direccion} />

                  <Field label="Ciudad" value={group?.client?.ciudad} />

                  <Field
                    label="Vigencia C. Comercio"
                    value={
                      group?.client?.vigenciacamara
                        ? new Date(group.client.vigenciacamara).toLocaleDateString()
                        : '-'
                    }
                  />

                  <Field
                    label="F. creación doc."
                    value={
                      group?.client?.fecha
                        ? new Date(group.client.fecha).toLocaleDateString()
                        : '-'
                    }
                  />
                </div>
              </div>

              {/* Rutas creadas -------------------------------------------------------- */}
              <div className="w-full rounded-2xl border border-orange-200 p-6 bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 shadow-lg">
                <header className="flex items-center gap-4 mb-6">
                  <div className="flex items-center gap-3">
                    <span className="w-4 h-4 rounded-full bg-orange-500 animate-pulse shadow-sm" />
                    <h3 className="text-lg font-bold uppercase text-gray-800 tracking-wide">
                      Rutas creadas
                    </h3>
                  </div>
                  <span className="ml-auto inline-flex items-center px-3 py-2 rounded-full bg-orange-200 text-orange-800 text-sm font-bold shadow-sm border border-orange-300">
                    {acceptedQuotes.length} rutas
                  </span>
                </header>

                {acceptedQuotes.length === 0 ? (
                  <div className="text-center py-12">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mb-4">
                      <FaTruck className="text-orange-400 text-2xl" />
                    </div>
                    <p className="text-gray-500 text-lg font-medium mb-2">No hay rutas creadas</p>
                    <p className="text-gray-400 text-sm">Las rutas aparecerán aquí cuando sean aceptadas</p>
                  </div>
                ) : (
                  <div className="space-y-5">
                    {acceptedQuotes.map((quote, idx) => (
                      <button
                        key={quote.id}
                        onClick={() => setSelectedModel(quote)}
                        className="group relative w-full flex flex-col gap-4 p-5 rounded-2xl bg-white/95 backdrop-blur-sm border-2 border-orange-200/60
                                   hover:bg-white hover:border-orange-300 hover:shadow-lg hover:scale-[1.01] 
                                   transition-all duration-300 ease-out shadow-sm"
                      >
                        {/* Header de la ruta */}
                        <div className="flex items-center gap-4 w-full">
                          <div className="flex-shrink-0 p-3 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 group-hover:from-orange-200 group-hover:to-orange-300 transition-all duration-300">
                            <FaTruck className="text-orange-600 text-lg" />
                          </div>
                          
                          <div className="flex-1 flex items-center justify-center gap-3 min-w-0 px-2">
                            <div className="flex flex-col items-center gap-1 min-w-0">
                              <span className="font-semibold text-gray-800 text-sm bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 shadow-sm max-w-full truncate">
                                {quote.ciudad_origen}
                              </span>
                              <span className="text-xs text-gray-500 font-medium uppercase tracking-wide">Origen</span>
                            </div>
                            
                            <div className="flex flex-col items-center gap-1 flex-shrink-0">
                              <span className="font-black text-orange-500 text-xl animate-pulse">→</span>
                              <span className="text-xs text-gray-500 font-semibold uppercase tracking-wide bg-orange-100 px-2 py-0.5 rounded-full">RUTA</span>
                            </div>
                            
                            <div className="flex flex-col items-center gap-1 min-w-0">
                              <span className="font-semibold text-gray-800 text-sm bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 shadow-sm max-w-full truncate">
                                {quote.ciudad_destino}
                              </span>
                              <span className="text-xs text-gray-500 font-medium uppercase tracking-wide">Destino</span>
                            </div>
                          </div>
                        </div>

                        {/* Información adicional */}
                        <div className="flex items-start justify-between w-full gap-3 pt-3 border-t border-gray-100">
                          <div className="flex flex-col gap-2 min-w-0 flex-1">
                            <span className="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-semibold text-sm shadow-md max-w-full">
                              <span className="text-sm">📅</span>
                              <span className="truncate">
                                {new Date(quote.created_at).toLocaleDateString('es-CO', {
                                  day: '2-digit',
                                  month: '2-digit',
                                  year: 'numeric'
                                })}
                              </span>
                            </span>
                            {quote.valor && (
                              <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 text-green-700 border border-green-300 font-semibold text-xs shadow-sm max-w-full">
                                <span className="text-sm">💰</span>
                                <span className="truncate">${parseFloat(quote.valor).toLocaleString('es-CO')}</span>
                              </span>
                            )}
                          </div>
                          
                          <div className="flex flex-col items-end gap-2 min-w-0 flex-shrink-0">
                            <span className="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 border border-orange-300 font-bold text-sm shadow-sm">
                              RUTA {idx + 1}
                            </span>
                            {quote.tipo_mercancia && (
                              <span className="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-blue-100 text-blue-700 border border-blue-300 font-semibold text-xs shadow-sm max-w-full">
                                <span className="text-sm">📦</span>
                                <span className="truncate">{quote.tipo_mercancia}</span>
                              </span>
                            )}
                          </div>
                        </div>

                        {/* Indicador de hover */}
                        <div className="absolute inset-0 rounded-2xl border-2 border-transparent group-hover:border-orange-400 transition-all duration-300 pointer-events-none" />
                        
                        {/* Badge de estado */}
                        <div className="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-green-500 border-2 border-white shadow-lg flex items-center justify-center">
                          <span className="text-white text-xs font-bold">✓</span>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* ────────────────────── COL-2 ────────────────────── */}
            <div className="flex flex-col gap-6">
              {/* Carga --------------------------------------------------------------- */}
              <div className="w-full rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
                <header className="flex items-center gap-3 mb-6">
                  <span className="w-3 h-3 rounded-full bg-green-500" />
                  <h3 className="text-lg font-bold uppercase text-gray-800">
                    Carga
                  </h3>
                  <span className="ml-auto inline-flex items-center px-3 py-2 rounded-full bg-green-100 text-green-700 text-sm font-bold border border-green-300 shadow-sm">
                    {acceptedQuotes.length} cargas
                  </span>
                </header>

                <div className="space-y-5">
                  {acceptedQuotes.map((cot, idx) => (
                    <div
                      key={cot.id}
                      className="border border-gray-200 rounded-2xl p-5 bg-gradient-to-br from-gray-50 to-white shadow-sm hover:shadow-md transition-all duration-200"
                    >
                      <header className="flex items-center gap-4 mb-4">
                        <span className="p-3 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 shadow-sm">
                          <FaTruckMoving className="text-orange-600 text-lg" />
                        </span>
                        <h4 className="font-bold text-gray-800 truncate flex-1 text-base">
                          {cot.ciudad_origen} → {cot.ciudad_destino}
                        </h4>
                        <span className="text-sm inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full font-bold border border-blue-300 shadow-sm">
                          Carga {idx + 1}
                        </span>
                      </header>

                      {/* datos carga */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <Field small label="Mercancía" value={cot.tipo_mercancia} />
                        <Field small label="Producto" value={cot.producto_label || cot.tipo_producto} />
                        <Field
                          small
                          label="Peso"
                          value={
                            cot.peso_mercancia
                              ? `${cot.peso_mercancia} kg`
                              : '--'
                          }
                        />
                        <Field
                          small
                          label="Dimensiones"
                          value={cot.dimensiones_exactas}
                        />
                        <Field small label="Unidades" value={cot.cantidad} />
                        <Field
                          small
                          label="Vehículos"
                          value={cot.cantidad_vh}
                        />
                        <Field
                          small
                          label="Embalaje"
                          value={cot.tipo_embajale}
                        />
                        <Field
                          small
                          label="Carrocería"
                          value={cot.tipo_carroceria}
                        />
                        <Field
                          small
                          label="Vehículo req."
                          value={
                            cot.vehiculo_requerido ||
                            cot.pricing?.vehicle_type
                          }
                        />
                        <Field
                          small
                          label="Valor decl."
                          value={
                            cot.valor_declarado
                              ? `$${parseFloat(
                                  cot.valor_declarado
                                ).toLocaleString('es-CO')}`
                              : '--'
                          }
                          highlight="green"
                        />
                        <Field
                          small
                          label="Cotización"
                          value={
                            cot.valor
                              ? `$${parseFloat(
                                  cot.valor
                                ).toLocaleString('es-CO')}`
                              : '--'
                          }
                          highlight="blue"
                        />
                        <Field
                          small
                          label="Seguro"
                          value={cot.seguro === '1' ? 'Sí' : 'No'}
                        />
                        <Field
                          small
                          label="Temperatura"
                          value={
                            cot.temperatura_mercancia || 'No aplica'
                          }
                        />
                        <Field
                          small
                          label="Reg. fotog."
                          value={cot.registro_fotografico}
                        />
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Conductores / llamadas -------------------------------------------- */}
              <div className="w-full rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
                <header className="flex items-center gap-3 mb-6">
                  <span className="w-3 h-3 rounded-full bg-purple-500" />
                  <h3 className="text-lg font-bold uppercase text-gray-800">
                    Conductores
                  </h3>
                </header>

                <div className="space-y-4">
                  {acceptedQuotes.map(cot => (
                    <div
                      key={cot.id}
                      className="p-4 rounded-xl bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 shadow-sm hover:shadow-md transition-all duration-200"
                    >
                      <CallPanel cotizacion={cot} onModalClose={onClose} />
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* ────────────────────── COL-3 ────────────────────── */}
            <div className="flex flex-col gap-6">
              {/* Pendientes ----------------------------------------------------------- */}
              <div className="w-full rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
                <header className="flex items-center gap-3 mb-6">
                  <span className="w-3 h-3 rounded-full bg-yellow-500 animate-pulse" />
                  <h3 className="text-lg font-bold uppercase text-gray-800">
                    Pendientes
                  </h3>
                </header>

                <div className="rounded-xl bg-gradient-to-br from-yellow-50 to-orange-50 p-4 border border-yellow-200 shadow-sm">
                  <Pendings groupCotizationId={group.id} />
                </div>
              </div>
            </div>
          </div>
        </section>
      </article>

      {/* Notas -------------------------------------------------- */}
      {selectedModel && (
        <NotesPanel
          model={selectedModel}
          onClose={() => setSelectedModel(null)}
        />
      )}
  </div>,
  portalTarget
  );
}

/* ───────────────── helper pequeño para no repetir markup ───────────────── */
function Field({ label, value, small, highlight }) {
  const classes = [
    'px-3 py-2 rounded-lg border shadow-sm',
    highlight === 'green' && 'bg-green-50 text-green-700 font-bold border-green-300',
    highlight === 'blue' && 'bg-blue-50 text-blue-700 font-bold border-blue-300',
    !highlight && 'bg-gray-50 text-gray-800 border-gray-200'
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className={`flex flex-col gap-2 ${small ? 'text-sm' : 'text-sm'}`}>
      <span className="font-bold text-gray-600 text-xs uppercase tracking-wide">{label}:</span>
      <span className={`flex-1 truncate ${classes} min-h-[2.2rem] flex items-center transition-colors hover:shadow-md`} title={value}>
        {value || '--'}
      </span>
    </div>
  );
}