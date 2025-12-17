/*  ──────────────────────────────────────────────────────────
    SolicitationDetail.jsx  –  modal con edición de IMPORTANCIA
    ────────────────────────────────────────────────────────── */
import React, { useEffect, useState } from 'react';
import { IoClose } from 'react-icons/io5';
import ChatBox            from './ChatBox';
import PricingActions     from './PricingActions';
import SuperAdminActions  from './SuperAdminActions';

import {
  getSolicitation,
  updateSolicitation          //  ← nueva importación
} from '../../services/solicitations';
import { getCurrentUser }  from '../../services/userService';
import { normalizeRole }   from '../../services/roles';

export default function SolicitationDetail({ id, onClose, onUpdated }) {

  /* ───────── estados ───────── */
  const [user,        setUser]        = useState(null);
  const [data,        setData]        = useState(null);
  const [importance,  setImportance]  = useState('LOW');
  const [loading,     setLoading]     = useState(true);

  /* ───────── helpers ───────── */
  const fetch = async () => {
    setLoading(true);
    const res = await getSolicitation(id);
    setData(res.data);
    setImportance(res.data.importance);     // sincroniza <select>
    setLoading(false);
  };

  /* ───────── efectos ───────── */
  useEffect(() => { getCurrentUser().then(r => setUser(r.data)); }, []);
  useEffect(() => { fetch(); }, [id]);

  if (loading || !data || !user) return null;

  const hasRole = r =>
    user.roles?.some(role => normalizeRole(role.name) === normalizeRole(r));

  /*  Permisos para editar importancia
      – creador + status PENDING
      – o rol PRICING
      – o rol SUPER ADMIN
  */
  const canEditImportance =
    (user.id === data.created_by && data.status === 'PENDING') ||
    hasRole('PRICING') || hasRole('SUPER ADMIN');

  const changeImportance = async (newLevel) => {
    if (newLevel === data.importance) return;
    await updateSolicitation(id, { importance: newLevel });
    await fetch();          //  refresca modal
    onUpdated?.();          //  refresca listado
  };

  /* ───────── badge de estado ───────── */
  const statusBadge = (st) => {
    const c = 'px-3 py-1 text-xs rounded-full font-medium border';
    const baseStyle = { fontFamily: 'Product Sans, sans-serif' };
    return {
      PENDING   : <span className={`bg-gray-50  text-gray-600  border-gray-200 ${c}`} style={baseStyle}>Pendiente</span>,
      IN_PROCESS: <span className={`bg-blue-50 text-blue-700 border-blue-200 ${c}`} style={baseStyle}>En proceso</span>,
      ANSWERED  : <span className={`bg-green-50  text-green-700  border-green-200 ${c}`} style={baseStyle}>Contestada</span>,
      FINALIZED : <span className={`bg-green-100 text-green-800 border-green-300 ${c}`} style={baseStyle}>Finalizada</span>,
      SENT      : <span className={`bg-indigo-50 text-indigo-700 border-indigo-200 ${c}`} style={baseStyle}>Enviada</span>,
      REJECTED  : <span className={`bg-red-50   text-red-700   border-red-200 ${c}`} style={baseStyle}>Rechazada</span>,
    }[st] ?? <span className={`bg-gray-100 text-gray-700 border-gray-200 ${c}`} style={baseStyle}>{st}</span>;
  };

  /* ───────── UI ───────── */
  return (
    <div className="fixed inset-0 z-[99990] flex items-start justify-center pt-8 bg-black/50 px-4 overflow-y-auto backdrop-blur-sm" style={{ fontFamily: 'Product Sans, sans-serif' }}>
      <div className="relative w-full max-w-5xl bg-white rounded-xl shadow-2xl p-6 flex flex-col gap-6 max-h-[92vh] overflow-y-auto border border-gray-200">

        {/* botón cerrar */}
        <button
          aria-label="Cerrar"
          className="absolute right-4 top-4 text-2xl text-gray-400 hover:text-gray-600 transition-colors duration-200 bg-gray-50 hover:bg-gray-100 rounded-full p-2"
          onClick={onClose}
        >
          <IoClose />
        </button>

        <div className="pr-12">
          <h3 className="text-3xl font-semibold text-gray-800 mb-2" style={{ fontFamily: 'Product Sans, sans-serif' }}>
            Solicitud #{data.id}
          </h3>
          <div className="w-16 h-1 bg-blue-500 rounded-full"></div>
        </div>

        {/* Información básica */}
        <div className="bg-gray-50 rounded-lg p-6 border border-gray-200">
          <h4 className="text-lg font-semibold text-gray-800 mb-4" style={{ fontFamily: 'Product Sans, sans-serif' }}>Información General</h4>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

            <div className="flex flex-col gap-2">
              <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Origen</span>
              <span className="text-gray-800 font-medium" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.origin}</span>
            </div>

            <div className="flex flex-col gap-2">
              <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Destino</span>
              <span className="text-gray-800 font-medium" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.destination}</span>
            </div>

            <div className="flex flex-col gap-2">
              <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Estado</span>
              <div>{statusBadge(data.status)}</div>
            </div>

            <div className="flex flex-col gap-2">
              <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Importancia</span>
              {canEditImportance ? (
                <select
                  value={importance}
                  onChange={e => {
                    setImportance(e.target.value);
                    changeImportance(e.target.value);
                  }}
                  className="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-400 focus:border-blue-500 outline-none w-fit"
                  style={{ fontFamily: 'Product Sans, sans-serif' }}
                >
                  <option value="LOW">Baja</option>
                  <option value="MEDIUM">Media</option>
                  <option value="HIGH">Alta</option>
                </select>
              ) : (
                <span className="text-gray-800 font-medium" style={{ fontFamily: 'Product Sans, sans-serif' }}>
                  {importance === 'HIGH'   ? 'Alta'  :
                   importance === 'MEDIUM' ? 'Media' : 'Baja'}
                </span>
              )}
            </div>

            {data.description && (
              <div className="col-span-1 md:col-span-2 flex flex-col gap-2">
                <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Descripción</span>
                <span className="text-gray-700 leading-relaxed" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.description}</span>
              </div>
            )}

          </div>
        </div>

        {/* Información adicional */}
        {(data.price || data.pricing_note || data.superadmin_note) && (
          <div className="bg-white rounded-lg p-6 border border-gray-200">
            <h4 className="text-lg font-semibold text-gray-800 mb-4" style={{ fontFamily: 'Product Sans, sans-serif' }}>Información Adicional</h4>
            <div className="grid grid-cols-1 gap-4 text-sm">
              
              {data.price && (
                <div className="flex flex-col gap-2">
                  <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Precio</span>
                  <span className="text-gray-800 font-medium text-lg" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.price}</span>
                </div>
              )}

              {data.pricing_note && (
                <div className="flex flex-col gap-2">
                  <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Nota de Pricing</span>
                  <span className="text-gray-700 leading-relaxed" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.pricing_note}</span>
                </div>
              )}

              {data.superadmin_note && (
                <div className="flex flex-col gap-2">
                  <span className="text-blue-600 font-medium text-xs uppercase tracking-wide" style={{ fontFamily: 'Product Sans, sans-serif' }}>Nota Super Admin</span>
                  <span className="text-gray-700 leading-relaxed" style={{ fontFamily: 'Product Sans, sans-serif' }}>{data.superadmin_note}</span>
                </div>
              )}

            </div>
          </div>
        )}

        {/* Acciones específicas por rol */}
        <div className="space-y-4">
          {hasRole('PRICING') && (
            <div className="bg-blue-50 rounded-lg p-6 border border-blue-200">
              <h4 className="text-lg font-semibold text-blue-800 mb-4" style={{ fontFamily: 'Product Sans, sans-serif' }}>Acciones de Pricing</h4>
              <PricingActions
                solicitation={data}
                onRefresh={fetch}
                onListRefresh={onUpdated}
              />
            </div>
          )}

          {hasRole('SUPER ADMIN') && (
            <div className="bg-gray-50 rounded-lg p-6 border border-gray-200">
              <h4 className="text-lg font-semibold text-gray-800 mb-4" style={{ fontFamily: 'Product Sans, sans-serif' }}>Acciones de Super Admin</h4>
              <SuperAdminActions
                solicitation={data}
                onRefresh={fetch}
                onListRefresh={onUpdated}
              />
            </div>
          )}
        </div>

        {/* Chats */}
        <div className="space-y-4">
          {hasRole('PRICING') && (
            <>
              <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <ChatBox solicitationId={id} channel="PRICING_COM" title="Chat con Asesor Comercial" />
              </div>
              <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                <ChatBox solicitationId={id} channel="PRICING_SA"  title="Chat con Super-Admin" />
              </div>
            </>
          )}

          {hasRole('SUPER ADMIN') && (
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              <ChatBox solicitationId={id} channel="PRICING_SA" title="Chat con Pricing" />
            </div>
          )}

          {hasRole('ASISTENTE COMERCIAL') && (
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
              <ChatBox solicitationId={id} channel="PRICING_COM" title="Chat con Pricing" />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}