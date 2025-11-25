// resources/js/components/Goals/GoalDashboard.jsx
import React, { useEffect, useState } from 'react';
import { getGoals, updateGoal } from '../../services/goals';
import { FiEdit3, FiCheck, FiX, FiTrendingUp, FiUsers, FiTarget, FiDollarSign, FiCalendar } from 'react-icons/fi';

const GoalDashboard = () => {
  const [rows, setRows] = useState([]);
  const [edit, setEdit] = useState(null); // goal being edited
  const [value, setValue] = useState('');
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    getGoals().then(r => {
      setRows(r.data);
      setLoading(false);
    });
  };

  useEffect(() => { load(); }, []);

  const handleSave = () => {
    updateGoal(edit.goal_id, { target_amount: value }).then(() => {
      setEdit(null);
      setValue('');
      load();
    });
  };

  // Calculate stats
  const totalTeamMembers = rows.length;
  const metasCumplidas = rows.filter(r => r.estado === 'Cumplida').length;
  const totalMetas = rows.reduce((sum, r) => sum + parseFloat(r.meta_mensual || 0), 0);
  const totalCotizaciones = rows.reduce((sum, r) => sum + parseFloat(r.cotizaciones_mes || 0), 0);

  if (loading) {
    return (
      <div className="w-full bg-white rounded-2xl shadow-xl border border-orange-100 p-8">
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
          <span className="ml-3 text-gray-600 text-lg">Cargando metas...</span>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full bg-white rounded-2xl shadow-xl overflow-hidden" style={{border: '1px solid #ffbf69'}}>
      {/* Header */}
      <div className="text-white p-6 lg:p-8" style={{background: 'linear-gradient(to right, #ff9f1c, #f79d65)'}}>
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <div className="p-3 bg-white bg-opacity-20 rounded-xl">
              <FiTrendingUp className="text-2xl" />
            </div>
            <div>
              <h2 className="text-2xl lg:text-3xl font-bold">Dashboard de Metas</h2>
              <p className="text-white opacity-90 text-lg">Gestión del equipo comercial</p>
            </div>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="p-6 border-b" style={{backgroundColor: '#ffbf69', borderBottomColor: '#ff9f1c'}}>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl p-4 shadow-sm" style={{border: '1px solid #ff9f1c'}}>
            <div className="flex items-center">
              <div className="p-2 rounded-lg" style={{backgroundColor: '#ffbf69'}}>
                <FiUsers className="text-xl text-white" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Equipo</p>
                <p className="text-2xl font-bold text-gray-900">{totalTeamMembers}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm" style={{border: '1px solid #ff9f1c'}}>
            <div className="flex items-center">
              <div className="p-2 rounded-lg" style={{backgroundColor: '#f79d65'}}>
                <FiTarget className="text-xl text-white" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Cumplidas</p>
                <p className="text-2xl font-bold text-gray-900">{metasCumplidas}/{totalTeamMembers}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm" style={{border: '1px solid #ff9f1c'}}>
            <div className="flex items-center">
              <div className="p-2 rounded-lg" style={{backgroundColor: '#ff9f1c'}}>
                <FiDollarSign className="text-xl text-white" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Meta Total</p>
                <p className="text-xl font-bold text-gray-900">${totalMetas.toLocaleString()}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm" style={{border: '1px solid #ff9f1c'}}>
            <div className="flex items-center">
              <div className="p-2 rounded-lg" style={{backgroundColor: '#f79d65'}}>
                <FiCalendar className="text-xl text-white" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-600">Logrado</p>
                <p className="text-xl font-bold text-gray-900">${totalCotizaciones.toLocaleString()}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="p-6">
        <div className="overflow-x-auto">
          <table className="min-w-full">
            <thead>
              <tr style={{borderBottom: '1px solid #ff9f1c'}}>
                <th className="py-4 px-6 text-left text-sm font-semibold text-gray-700 rounded-tl-lg" style={{backgroundColor: '#ffbf69'}}>
                  Comercial
                </th>
                <th className="py-4 px-6 text-left text-sm font-semibold text-gray-700" style={{backgroundColor: '#ffbf69'}}>
                  Meta Mensual
                </th>
                <th className="py-4 px-6 text-left text-sm font-semibold text-gray-700" style={{backgroundColor: '#ffbf69'}}>
                  Cotizaciones Aceptadas
                </th>
                <th className="py-4 px-6 text-center text-sm font-semibold text-gray-700" style={{backgroundColor: '#ffbf69'}}>
                  Progreso
                </th>
                <th className="py-4 px-6 text-center text-sm font-semibold text-gray-700" style={{backgroundColor: '#ffbf69'}}>
                  Estado
                </th>
                <th className="py-4 px-6 text-center text-sm font-semibold text-gray-700" style={{backgroundColor: '#ffbf69'}}>
                  Última Actualización
                </th>
                <th className="py-4 px-6 text-center text-sm font-semibold text-gray-700 rounded-tr-lg" style={{backgroundColor: '#ffbf69'}}>
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r, index) => {
                const progress = r.meta_mensual > 0 ? Math.min((r.cotizaciones_mes / r.meta_mensual) * 100, 100) : 0;
                const isLastRow = index === rows.length - 1;
                
                return (
                  <tr key={r.id} className={`transition-colors duration-200 ${isLastRow ? '' : 'border-b'}`} style={{borderBottomColor: '#ffbf69', backgroundColor: 'transparent'}} onMouseEnter={(e) => e.target.style.backgroundColor = '#ffbf69'} onMouseLeave={(e) => e.target.style.backgroundColor = 'transparent'}>
                    <td className="py-4 px-6">
                      <div className="flex items-center">
                        <div className="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold text-sm" style={{background: 'linear-gradient(to bottom right, #ff9f1c, #f79d65)'}}>
                          {r.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="ml-3">
                          <p className="text-sm font-semibold text-gray-900">{r.name}</p>
                          <p className="text-xs text-gray-500">Comercial</p>
                        </div>
                      </div>
                    </td>
                    <td className="py-4 px-6">
                      {edit?.id === r.id ? (
                        <input
                          type="number"
                          min="0"
                          value={value}
                          onChange={e => setValue(e.target.value)}
                          className="rounded-lg px-3 py-2 w-32 focus:ring-2 transition-all"
                          style={{border: '2px solid #ff9f1c', outline: 'none'}}
                          onFocus={(e) => {e.target.style.boxShadow = '0 0 0 2px #ffbf69'; e.target.style.borderColor = 'transparent'}}
                          onBlur={(e) => {e.target.style.boxShadow = 'none'; e.target.style.borderColor = '#ff9f1c'}}
                          placeholder="Meta"
                        />
                      ) : (
                        <div className="flex items-center">
                          <FiDollarSign style={{color: '#ff9f1c'}} className="mr-1" />
                          <span className="font-mono text-lg font-semibold text-gray-900">
                            {parseFloat(r.meta_mensual || 0).toLocaleString()}
                          </span>
                        </div>
                      )}
                    </td>
                    <td className="py-4 px-6">
                      <div className="flex items-center">
                        <FiDollarSign style={{color: '#f79d65'}} className="mr-1" />
                        <span className="font-mono text-lg font-semibold text-gray-900">
                          {parseFloat(r.cotizaciones_mes || 0).toLocaleString()}
                        </span>
                      </div>
                    </td>
                    <td className="py-4 px-6">
                      <div className="w-full">
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-xs font-medium text-gray-700">{progress.toFixed(1)}%</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2">
                          <div 
                            className="h-2 rounded-full transition-all duration-500"
                            style={{
                              width: `${Math.min(progress, 100)}%`,
                              backgroundColor: progress >= 100 ? '#ff9f1c' : progress >= 75 ? '#f79d65' : '#ffbf69'
                            }}
                          ></div>
                        </div>
                      </div>
                    </td>
                    <td className="py-4 px-6 text-center">
                      {r.estado === 'Cumplida' ? (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border" style={{backgroundColor: '#ffbf69', color: '#8B4513', borderColor: '#ff9f1c'}}>
                          <FiCheck className="mr-1" /> Cumplida
                        </span>
                      ) : (
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border" style={{backgroundColor: '#f79d65', color: 'white', borderColor: '#ff9f1c'}}>
                          <FiX className="mr-1" /> Pendiente
                        </span>
                      )}
                    </td>
                    <td className="py-4 px-6 text-center">
                      <span className="text-xs text-gray-500">{r.ultima_actualizacion}</span>
                    </td>
                    <td className="py-4 px-6 text-center">
                      {edit?.id === r.id ? (
                        <div className="flex items-center justify-center space-x-2">
                          <button
                            className="text-white rounded-lg p-2 transition-colors duration-200 shadow-sm"
                            style={{backgroundColor: '#ff9f1c'}}
                            onMouseEnter={(e) => e.target.style.backgroundColor = '#f79d65'}
                            onMouseLeave={(e) => e.target.style.backgroundColor = '#ff9f1c'}
                            title="Guardar"
                            onClick={handleSave}
                          >
                            <FiCheck size={16} />
                          </button>
                          <button
                            className="text-white rounded-lg p-2 transition-colors duration-200 shadow-sm"
                            style={{backgroundColor: '#f79d65'}}
                            onMouseEnter={(e) => e.target.style.backgroundColor = '#ff9f1c'}
                            onMouseLeave={(e) => e.target.style.backgroundColor = '#f79d65'}
                            title="Cancelar"
                            onClick={() => setEdit(null)}
                          >
                            <FiX size={16} />
                          </button>
                        </div>
                      ) : (
                        <button
                          className="text-white rounded-lg p-2 transition-colors duration-200 shadow-sm"
                          style={{backgroundColor: '#ff9f1c'}}
                          onMouseEnter={(e) => e.target.style.backgroundColor = '#f79d65'}
                          onMouseLeave={(e) => e.target.style.backgroundColor = '#ff9f1c'}
                          title="Editar meta"
                          onClick={() => { setEdit(r); setValue(r.meta_mensual); }}
                          aria-label="Editar meta"
                        >
                          <FiEdit3 size={16} />
                        </button>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        
        {rows.length === 0 && (
          <div className="text-center py-12">
            <div className="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4" style={{backgroundColor: '#ffbf69'}}>
              <FiUsers className="text-white text-3xl" />
            </div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No hay metas configuradas</h3>
            <p className="text-gray-500">No se encontraron metas para el equipo comercial.</p>
          </div>
        )}
      </div>
    </div>
  );
};


export default GoalDashboard;