import React, { useEffect, useState } from 'react';
import { fetchSolicitations } from '../../services/solicitations';
import SolicitationDetail from './SolicitationDetail';
import SolicitationForm from './SolicitationForm';
import { getCurrentUser }     from '../../services/userService';
import { normalizeRole }    from '../../services/roles';

// ICONOS
import { FiSearch, FiChevronDown, FiEye } from 'react-icons/fi';

export default function SolicitationList() {
  const [solicitations, setSolicitations] = useState([]);
  const [selected, setSelected] = useState(null);
  const [status, setStatus] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [user,           setUser]          = useState(null);

  useEffect(() => {
    getCurrentUser().then(res => setUser(res.data));
  }, []);

  useEffect(() => {
    if (
      user &&                           // usuario cargado
      status === '' &&                  // todavía no se eligió filtro
      user.roles?.some(r => normalizeRole(r.name) === 'SUPER_ADMIN')
    ) {
      setStatus('');          // selecciona "En proceso"
    }
  }, [user]);   

  const fetchData = async () => {
    const { data } = await fetchSolicitations({ status, from, to });
    setSolicitations(data);
  };

  useEffect(() => { fetchData(); }, [status, from, to]);

  // Etiqueta visual para la importancia
  const importanceBadge = (level) => {
    const baseStyle = { fontFamily: 'Product Sans, sans-serif' };
    if (level === "HIGH")
      return <span className="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-medium gap-1 border border-red-200" style={baseStyle}><span className="whitespace-nowrap">Alta</span> <span className="text-red-500 text-base">●</span></span>;
    if (level === "MEDIUM")
      return <span className="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-700 font-medium gap-1 border border-yellow-200" style={baseStyle}><span className="whitespace-nowrap">Media</span> <span className="text-yellow-500 text-base">●</span></span>;
    return <span className="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium gap-1 border border-green-200" style={baseStyle}><span className="whitespace-nowrap">Baja</span> <span className="text-green-500 text-base">●</span></span>;
  };

  const hasRole = r =>
  user?.roles?.some(role => normalizeRole(role.name) === normalizeRole(r));

  // Etiqueta visual para el status
  const statusBadge = (status) => {
    const c = 'px-3 py-1 text-xs rounded-full font-medium tracking-wide border';
    const baseStyle = { fontFamily: 'Product Sans, sans-serif' };
    switch (status) {
      case 'PENDING':
        return <span className={`bg-gray-50 text-gray-600 border-gray-200 ${c}`} style={baseStyle}>Pendiente</span>;
      case 'IN_PROCESS':
        return <span className={`bg-blue-50 text-blue-700 border-blue-200 ${c}`} style={baseStyle}>En proceso</span>;
      case 'ANSWERED':
        return <span className={`bg-indigo-50 text-indigo-700 border-indigo-200 ${c}`} style={baseStyle}>Contestada</span>;
      case 'FINALIZED':
        return <span className={`bg-green-50 text-green-700 border-green-200 ${c}`} style={baseStyle}>Finalizada</span>;
      case 'SENT':
        return <span className={`bg-purple-50 text-purple-700 border-purple-200 ${c}`} style={baseStyle}>Enviada</span>;
      case 'REJECTED':
        return <span className={`bg-red-50 text-red-700 border-red-200 ${c}`} style={baseStyle}>Rechazada</span>;
      default:
        return <span className={`bg-gray-50 text-gray-400 border-gray-200 ${c}`} style={baseStyle}>{status}</span>;
    }
  };

  const STATUS_OPTIONS = [
    { value: '',           label: 'Todos'      },
    { value: 'PENDING',    label: 'Pendiente'  },
    { value: 'IN_PROCESS', label: 'En proceso' },
    { value: 'ANSWERED',   label: 'Contestada' },
    { value: 'FINALIZED',  label: 'Finalizada' },
    { value: 'REJECTED',   label: 'Rechazada'  },
  ];

  return (
    <div className="w-full mx-auto bg-white rounded-2xl p-6 shadow-lg border border-gray-200" style={{ fontFamily: 'Product Sans, sans-serif' }}>
      <h2 className="text-2xl font-semibold mb-6 text-gray-800 flex items-center gap-3" style={{ fontFamily: 'Product Sans, sans-serif' }}>
        <FiSearch className="text-orange-500 text-xl" /> Solicitudes
      </h2>

      {/* FILTROS */}
      <div className="mb-8 flex flex-wrap gap-4 items-end p-4 bg-gray-50 rounded-xl border border-gray-200">
        <div className="relative">
          <select
            value={status}
            onChange={e => setStatus(e.target.value)}
            className="appearance-none pl-4 pr-10 py-2.5 bg-white rounded-lg border border-gray-300
                      focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-gray-700 text-sm shadow-sm transition-all"
            style={{ fontFamily: 'Product Sans, sans-serif' }}
          >
            {STATUS_OPTIONS.map(opt => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          <FiChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400"/>
        </div>
        <input type="date" value={from}
          onChange={e => setFrom(e.target.value)}
          className="px-4 py-2.5 rounded-lg border border-gray-300 bg-white focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-gray-700 text-sm shadow-sm transition-all"
          style={{ fontFamily: 'Product Sans, sans-serif' }}
        />
        <input type="date" value={to}
          onChange={e => setTo(e.target.value)}
          className="px-4 py-2.5 rounded-lg border border-gray-300 bg-white focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-gray-700 text-sm shadow-sm transition-all"
          style={{ fontFamily: 'Product Sans, sans-serif' }}
        />
        <button
          onClick={fetchData}
          className="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 text-white rounded-lg text-sm font-medium shadow-sm hover:bg-orange-600 hover:shadow-md transition-all duration-200"
          style={{ fontFamily: 'Product Sans, sans-serif' }}
        >
          <FiSearch />Filtrar
        </button>
      </div>

      {hasRole('ASISTENTE_COMERCIAL') && (
          <SolicitationForm onCreated={fetchData} />
        )}

      {/* TABLA */}
      <div className="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
        <table className="min-w-full text-sm bg-white rounded-xl" style={{ fontFamily: 'Product Sans, sans-serif' }}>
          <thead>
            <tr className="bg-gray-50 border-b border-gray-200">
              <th className="text-left p-3 font-medium text-gray-700 tracking-wide w-16">ID</th>
              <th className="text-left p-3 font-medium text-gray-700 tracking-wide w-40">Origen</th>
              <th className="text-left p-3 font-medium text-gray-700 tracking-wide w-40">Destino</th>
              <th className="text-left p-3 font-medium text-gray-700 tracking-wide w-28">Importancia</th>
              <th className="text-left p-3 font-medium text-gray-700 tracking-wide w-28">Estado</th>
              <th className="text-center p-3 font-medium text-gray-700 tracking-wide w-36">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {solicitations.length === 0 &&
              <tr>
                <td colSpan={6} className="text-gray-500 text-center px-8 py-12">
                  <div className="flex flex-col items-center gap-2">
                    <FiSearch className="text-gray-400 text-2xl" />
                    <span style={{ fontFamily: 'Product Sans, sans-serif' }}>No hay solicitudes disponibles</span>
                  </div>
                </td>
              </tr>
            }
            {solicitations.map((s, i) => (
              <tr
                key={s.id}
                className={`border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors duration-200 group`}
              >
                <td className="p-3 text-gray-700 font-medium" style={{ fontFamily: 'Product Sans, sans-serif' }}>{s.id}</td>
                <td className="p-3 text-gray-600 max-w-[160px] truncate" style={{ fontFamily: 'Product Sans, sans-serif' }} title={s.origin}>{s.origin}</td>
                <td className="p-3 text-gray-600 max-w-[160px] truncate" style={{ fontFamily: 'Product Sans, sans-serif' }} title={s.destination}>{s.destination}</td>
                <td className="p-3">{importanceBadge(s.importance)}</td>
                <td className="p-3">{statusBadge(s.status)}</td>
                <td className="p-3 text-center">
                  <button
                    className="inline-flex items-center gap-1 text-orange-600 font-medium text-xs hover:bg-orange-50 hover:text-orange-700 px-3 py-1.5 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md border border-orange-200 hover:border-orange-300"
                    onClick={() => setSelected(s.id)}
             
                    style={{ fontFamily: 'Product Sans, sans-serif' }}
                  >
                    <FiEye className="text-sm" /> Ver
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* DETALLE */}
      {selected &&
        <SolicitationDetail id={selected}
          onClose={() => setSelected(null)} 
          onUpdated={fetchData}
          />}
    </div>
  );
}