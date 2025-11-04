/* eslint-disable react/prop-types */
import React, { useEffect, useState } from "react";
import {
  getUserColumns,
  createUserColumn,
  updateUserColumn,
  deleteUserColumn,
  reorderUserColumns
} from '../../services/columnsService';
import { fetchGroupQuotations, updateGroupStatus } from '../../services/channelService';
import { DragDropContext, Droppable, Draggable } from "react-beautiful-dnd";
import ChannelColumnContent from './ChannelColumnContent';
import CreateColumnModal from './CreateColumnModal';
import { Toaster, toast } from 'react-hot-toast';
import { FiTrash } from 'react-icons/fi';
import { RiInsertColumnLeft } from "react-icons/ri";
import TransitGroupModal from './TransitGroupModal';
import { quoteBus } from '../CotizacionInicial/QuoteIndex';

/* ------------- 🔥 1. COLUMNAS FIJAS ------------------------------ *
 * Quitamos las dos que ya no se usan y añadimos "Completada".
 * ----------------------------------------------------------------- */
const fixedColumns = [
  { key: 'Pre-Solicitud', name: 'Pre-Solicitud',   color: '#E9F1F2', sys: true },
  { key: 'En tránsito',    name: 'En tránsito',    color: '#FAEFDB', sys: true },
  { key: 'En facturación', name: 'En facturación', color: '#FAF5E8', sys: true },
  { key: 'Completada',     name: 'Completada',     color: '#D7FAD5', sys: true },
];

/* ------------- 2. ESTADOS NO PERMITIDOS EN CUSTOM --------------- */
const NOT_ALLOWED_CUSTOM = [
  "enviada", "aceptada", "pendiente",
  "en transito", "en tránsito",
  "en facturacion", "en facturación",
  "completada"
];

/* =================================================================
 *  COMPONENTE PRINCIPAL
 * ================================================================= */
function ChannelsWithCustomColumns() {
  const [customColumns, setCustomColumns] = useState([]);
  const [groups, setGroups] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [transitoModalOpen, setTransitoModalOpen] = useState(false);
  const [selectedTransitoGroup, setSelectedTransitoGroup] = useState(null);

  /* -------------------- GET USER COLUMNS ------------------------ */
  useEffect(() => {
    getUserColumns().then(res => {
      if (Array.isArray(res.data))
        setCustomColumns(res.data.map(col => ({ ...col, sys: false })));
    });
  }, []);

  /* --------------------- GET GROUPS ----------------------------- */
  const loadGroups = () => {
    fetchGroupQuotations().then(res => {
      console.log('ChannelsWithCustomColumns - Datos de API:', res.data);
      console.log('ChannelsWithCustomColumns - Grupos encontrados:', res.data.data);
      setGroups(res.data.data || []);
    });
  };

  useEffect(() => {
    loadGroups();
  }, []);

  /* -------------- ESCUCHAR EVENTOS DE REFRESH ------------------ */
  useEffect(() => {
    const handleRefresh = () => {
      console.log('ChannelsWithCustomColumns: Refreshing quotations...');
      loadGroups();
    };

    quoteBus.on('refreshQuotations', handleRefresh);
    
    return () => {
      quoteBus.off('refreshQuotations', handleRefresh);
    };
  }, []);

  // Ya no abrimos modal; redirigimos a una página dedicada.

  /* -------------------- HELPERS -------------------------------- */
  const getColumnsOrder = () => [...customColumns, ...fixedColumns];

  const getGroupsByStatus = () => {
    const byStatus = {};
    getColumnsOrder().forEach(col => (byStatus[col.name] = []));
    groups.forEach(group => {
      const col = getColumnsOrder()
        .find(c => c.name === group.status) || fixedColumns[0];
      byStatus[col.name].push(group);
    });
    return byStatus;
  };

  /* ------------------- MODALS ---------------------------------- */
  const openTransitoModal  = (g) => { setSelectedTransitoGroup(g); setTransitoModalOpen(true); };
  const closeTransitoModal = ()  => { setSelectedTransitoGroup(null); setTransitoModalOpen(false); };

  // /* =========== 🔥 2.  VALIDACIONES DE ARRASTRE ================== */
  /* ================================================================
  *  canMove(group, destColumnName)
  *  ---------------------------------------------------------------
  *  True  →  se permite soltar el grupo en la columna destino.
  *  False →  se cancela el movimiento.
  * ================================================================ */
  function canMove(group, destColumnName) {
    const current = (group.status || '').toLowerCase();   // estado actual del grupo
    const dest    = (destColumnName || '').toLowerCase(); // nombre de la columna destino

    /* ──────────── 1. “En tránsito” solo recibe grupos ACEPTADOS ──────────── */
    if (dest === 'en tránsito' || dest === 'en transito') {
      return current === 'aceptada';
    }

    /* ──────────── 2. “En facturación” solo recibe grupos EN TRÁNSITO ─────── */
    if (dest === 'en facturación' || dest === 'en facturacion') {
      return current === 'en tránsito' || current === 'en transito';
    }

    /* ──────────── 3. “Completada” solo recibe grupos EN FACTURACIÓN ──────── */
    if (dest === 'completada') {
      return current === 'en facturación' || current === 'en facturacion';
    }

    /* ──────────── 4. Un grupo ya “Completada” no se mueve a ningún lado ──── */
    if (current === 'completada') {
      return false;
    }

    /* ──────────── 5. Para el resto, no hay restricciones adicionales ─────── */
    return true;
  }

  /* ------------------ 🔥 3. DRAG END ---------------------------- */
  function handleDragEnd(result) {
    const { source, destination, type, draggableId } = result;
    if (!destination) return;

    /* --- 3.1  REORDENAR COLUMNAS CUSTOM ------------------------ */
    if (type === "COLUMN") {
      if (source.index === destination.index) return;
      const newCols = Array.from(customColumns);
      const [removed] = newCols.splice(source.index, 1);
      newCols.splice(destination.index, 0, removed);
      setCustomColumns(newCols);
      reorderUserColumns(newCols.map(c => c.id));
      return;
    }

    /* --- 3.2  MOVER GRUPOS ------------------------------------- */
    if (type === "GROUP") {
      if (
        source.droppableId === destination.droppableId &&
        source.index === destination.index
      ) return;

      const destCol   = getColumnsOrder().find(c => c.name === destination.droppableId);
      const group     = groups.find(g => String(g.id) === String(draggableId));

      if (!destCol || !group) return;

      /* ========= VALIDACIONES ESPECIALES NUEVAS ================= */
      if (!canMove(group, destCol.name)) {
        toast.error(`Movimiento no permitido.\nDesde "${group.status}" solo puedes ir a un estado válido.`);
        return;
      }

      /* ========= VALIDACIONES YA EXISTENTES  ==================== */
      const currentGroupEstado = (group.estado || group.status || '').toLowerCase();

      /* Sólo columnas custom si el estado NO está entre los prohibidos */
      if (destCol.sys === false) {
        if (NOT_ALLOWED_CUSTOM.includes(currentGroupEstado)) {
          toast.error(
            `Este grupo NO puede ir a columnas personalizadas porque su estado es "${group.status}".`
          );
          return;
        }
      }

      /* Validación antigua para pasar a En tránsito */
      if (destCol.name.toLowerCase().includes('en tránsito')) {
        if (!todasSolicitudesCompletadas(group)) {
          toast.error(
            'Para mover a "En tránsito" debes tener la solicitud de transporte COMPLETA en cada cotización aceptada.'
          );
          return;
        }
      }

      /* -------------- ACTUALIZAR EN BACKEND -------------------- */
      updateGroupStatus(draggableId,
        destCol.name === 'Pre-Solicitud' ? 'borrador' : destCol.name
      )
        .then(() => loadGroups())
        .then(() => toast.success('Estado actualizado correctamente.'))
        .catch(err  => {
          console.error(err);
          toast.error('No se pudo mover el grupo. Intenta de nuevo.');
        });
    }
  }

  /* ------------- UTIL PARA VALIDAR SOLICITUDES ------------------ */
  function todasSolicitudesCompletadas(group) {
    const aceptadas = (group.cotizaciones || []).filter(
      q => q.decision_cliente === "aceptada"
    );
    if (aceptadas.length === 0) return false;
    return aceptadas.every(
      q => q.solicitud && q.solicitud.estado === "completada"
    );
  }

  /* ------------------ RENDER ------------------------------------ */
  const groupsByStatus = getGroupsByStatus();
  const columnsOrder   = getColumnsOrder();
  const totalGlobal    = groups
    .reduce((sum, g) => sum + (parseFloat(g.valor_total) || 0), 0);

  return (
    <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
      {/* ────────────────── MODALES ───────────────────────────── */}
      <TransitGroupModal
        open={transitoModalOpen}
        onClose={closeTransitoModal}
        group={selectedTransitoGroup}
      />

      <Toaster position="top-center" />

      {/* ────────────────── HEADER CON TÍTULO Y BOTÓN ────────── */}
      <div className="w-full max-w-[1700px] flex justify-between items-center mb-6 px-4">
        <div className="flex items-center gap-4">
          <h1 className="text-3xl font-bold text-gray-800">Gestión de Cotizaciones</h1>
          <div className="bg-white px-4 py-2 rounded-full shadow-sm border">
            <span className="text-sm text-gray-600">
              {groups.length} Grupos Totales
            </span>
          </div>
        </div>
        
        <button
          className="bg-gradient-to-r from-[#FF7C32] to-[#FF6B1A] text-white font-semibold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2 btn-hover-lift"
          onClick={() => setModalOpen(true)}
        >
          <RiInsertColumnLeft className="w-5 h-5" />
          Nueva Columna
        </button>
      </div>

      {/* ────────────────── MODAL CREACIÓN COLUMNA ────────────── */}
      <CreateColumnModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        onCreate={({ name, color }) =>
          createUserColumn({ name, color }).then(res => {
            setCustomColumns(cols => [...cols, res.data]);
            setModalOpen(false);
            toast.success(`Columna "${name}" creada con éxito`);
          })
        }
      />

      {/* ────────────────── DND CONTEXT ───────────────────────── */}
      <DragDropContext onDragEnd={handleDragEnd}>
        {/* ------------ CONTENEDOR HORIZONTAL DE COLUMNAS -------- */}
        <Droppable
          droppableId="columns-droppable"
          direction="horizontal"
          type="COLUMN"
        >
          {provided => (
            <div
              className="flex flex-row gap-6 overflow-x-auto w-full max-w-[1700px] h-[78vh] px-4 pb-4"
              {...provided.droppableProps}
              ref={provided.innerRef}
            >
              {/* ---------- 3.3  COLUMNAS CUSTOM  ----------------- */}
              {customColumns.map((col, colIdx) => (
                <Draggable
                  key={col.id}
                  draggableId={`custom-col-${col.id}`}
                  index={colIdx}
                >
                  {dragProvided => (
                    <div
                      ref={dragProvided.innerRef}
                      {...dragProvided.draggableProps}
                      {...dragProvided.dragHandleProps}
                      className="flex-shrink-0 flex flex-col w-[90vw] sm:w-[380px] h-full min-w-[280px] bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 quotes-column"
                      style={dragProvided.draggableProps.style}
                    >
                      {/* CABECERA CUSTOM */}
                      <div
                        style={{ background: `linear-gradient(135deg, ${col.color}, ${col.color}dd)` }}
                        className="sticky top-0 flex items-center justify-between px-6 pt-6 pb-5 rounded-t-2xl mb-1 shadow-sm border-b border-white border-opacity-30"
                      >
                        <div className="flex items-center gap-3">
                          <div className="w-3 h-3 rounded-full bg-white bg-opacity-60"></div>
                          <span className="text-[#202020] text-lg font-semibold truncate">
                            {col.name}
                          </span>
                        </div>
                        <button
                          className="flex items-center justify-center w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 text-red-600 rounded-full transition-all duration-200 hover:scale-110"
                          onClick={() => {
                            if (!window.confirm("¿Eliminar columna personalizada?")) return;
                            deleteUserColumn(col.id).then(() => {
                              setCustomColumns(cs => cs.filter(c => c.id !== col.id));
                              loadGroups();
                            });
                          }}
                          title="Eliminar columna"
                        >
                          <FiTrash className="w-4 h-4" />
                        </button>
                      </div>

                      {/* DROPPABLE DE GRUPOS */}
                      <GroupsDroppable
                        droppableId={col.name}
                        groups={groupsByStatus[col.name]}
                        totalGlobal={totalGlobal}
                        showSummary={false}
                        onTransitoGroupClick={openTransitoModal}
                      />
                    </div>
                  )}
                </Draggable>
              ))}

              {/* ---------- 3.4  COLUMNAS FIJAS  ------------------ */}
              {fixedColumns.map(col => (
                <div
                  key={col.key}
                  className="flex-shrink-0 flex flex-col w-[90vw] sm:w-[380px] h-full min-w-[280px] bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 quotes-column"
                >
                  <div
                    style={{ background: `linear-gradient(135deg, ${col.color}, ${col.color}dd)` }}
                    className="sticky top-0 flex items-center gap-3 px-6 pt-6 pb-5 rounded-t-2xl mb-1 shadow-sm border-b border-white border-opacity-30"
                  >
                    <div className="w-3 h-3 rounded-full bg-white bg-opacity-60"></div>
                    <span className="text-[#202020] text-lg font-semibold">
                      {col.name}
                    </span>
                    <div className="ml-auto px-3 py-1 bg-white bg-opacity-20 rounded-full">
                      <span className="text-xs font-medium text-[#202020]">
                        Sistema
                      </span>
                    </div>
                  </div>

                  <GroupsDroppable
                    droppableId={col.name}
                    groups={groupsByStatus[col.name]}
                    totalGlobal={totalGlobal}
                    showSummary={col.key === 'Pre-Solicitud'}
                    onTransitoGroupClick={openTransitoModal}
                  />
                </div>
              ))}
              {provided.placeholder}
            </div>
          )}
        </Droppable>
      </DragDropContext>
    </div>
  );
}

export default ChannelsWithCustomColumns;

/* -----------------------------------------------------------------
 *  SUB-COMPONENTE GroupsDroppable   (sin cambios lógicos)
 * ---------------------------------------------------------------- */
function GroupsDroppable({ droppableId, groups, totalGlobal, showSummary, onTransitoGroupClick }) {
  return (
    <Droppable droppableId={droppableId} type="GROUP">
      {(provided, snapshot) => (
        <div
          ref={provided.innerRef}
          {...provided.droppableProps}
          className={
            "overflow-y-auto flex-1 px-4 pb-4 min-h-[40px] transition-all duration-200 rounded-b-2xl quotes-scrollbar" +
            (snapshot.isDraggingOver ? " bg-gradient-cool bg-opacity-50" : "")
          }
          style={{ maxHeight: '60vh' }}
        >
          {showSummary && (
            <div className="mb-4 px-2">
              <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 bg-blue-400 rounded-full"></div>
                    <span className="text-sm font-medium text-gray-700">Resumen</span>
                  </div>
                </div>
                <div className="mt-2 grid grid-cols-2 gap-3 text-sm">
                  <div className="bg-white rounded-lg p-2 text-center">
                    <div className="font-bold text-lg text-gray-800">{groups.length}</div>
                    <div className="text-xs text-gray-600">Grupos</div>
                  </div>
                  <div className="bg-white rounded-lg p-2 text-center">
                    <div className="font-bold text-lg text-green-600">${totalGlobal.toLocaleString("es-CO")}</div>
                    <div className="text-xs text-gray-600">Total</div>
                  </div>
                </div>
              </div>
            </div>
          )}

          <ChannelColumnContent
            groups={groups}
            onTransitoGroupClick={onTransitoGroupClick}
          />
          {provided.placeholder}
        </div>
      )}
    </Droppable>
  );
}