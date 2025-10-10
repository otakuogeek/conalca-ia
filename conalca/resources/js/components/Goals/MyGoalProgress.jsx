import React, { useEffect, useState } from 'react';
import { getMyGoal } from '../../services/goals';
import { FaBullseye, FaCheckCircle, FaExclamationTriangle } from 'react-icons/fa';

const MyGoalProgress = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    getMyGoal().then(r => {
      setData(r.data);
      setLoading(false);
    });
  };

  useEffect(() => { load(); }, []);

  if (loading) {
    return (
      <div className="w-full flex justify-center py-10">
        <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2" style={{borderTopColor: '#ff9f1c', borderBottomColor: '#ff9f1c'}} />
      </div>
    );
  }

  // Sin meta asignada
  if (!data || data.target_amount === 0) {
    return (
      <div className="px-6 py-4 rounded-lg shadow flex items-center gap-3" style={{backgroundColor: '#ffffffff', border: '1px solid #f79d65'}}>
        <FaExclamationTriangle style={{color: '#ff9f1c'}} className="text-2xl" />
        <div>
          <p className="text-gray-800 font-medium">Sin meta asignada</p>
          <p className="text-gray-600 text-sm">No tienes una meta configurada para este mes.</p>
        </div>
      </div>
    );
  }

  const pct = Math.min(data.percentage, 100); // máx 100% para la barra
  const fullPct = data.percentage.toFixed(2);

  let barColor = '#f79d65';
  if (pct >= 100) barColor = '#ff9f1c';
  else if (pct >= 75) barColor = '#ffbf69';
  else if (pct >= 50) barColor = '#f79d65';

  return (
    <div className="px-6 py-5 space-y-4">
      <div className="mb-4 flex items-center gap-3">
        <div className="w-12 h-12 rounded-full flex items-center justify-center" style={{background: 'linear-gradient(to bottom right, #ff9f1c, #f79d65)'}}>
          <FaBullseye className="text-white text-xl" />
        </div>
        <div>
          <h2 className="text-lg font-bold tracking-tight text-gray-900">Mi Meta Personal</h2>
          <p className="text-gray-600 text-sm">{data.month}/{data.year}</p>
        </div>
      </div>

      <div className="space-y-4 mb-6">
        <div className="rounded-lg p-4" style={{backgroundColor: '#ffbf69', border: '1px solid #ff9f1c'}}>
          <div className="flex items-center justify-between text-gray-600 text-sm mb-2">
            <span className="font-medium">Meta Asignada</span>
            <span className="font-bold text-gray-900 text-lg">$ {Number(data.target_amount).toLocaleString()}</span>
          </div>
          <div className="flex items-center justify-between text-gray-600 text-sm">
            <span className="font-medium">Logrado</span>
            <div className="text-right">
              <span className="font-bold text-gray-900 text-lg">
                $ {Number(data.achieved_amount).toLocaleString()}
              </span>
              <span className="ml-2 font-semibold" style={{color: '#ff9f1c'}}>({fullPct}%)</span>
            </div>
          </div>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="w-full h-4 mb-3 rounded-full bg-gray-200 overflow-hidden shadow-inner">
        <div
          className="h-4 transition-all duration-1000 ease-out rounded-full relative"
          style={{ 
            width: `${pct}%`,
            backgroundColor: barColor
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-transparent to-white opacity-20 rounded-full"></div>
        </div>
      </div>
      <div className="text-sm flex justify-between text-gray-600">
        <span>Progreso actual</span>
        <span className="font-semibold" style={{color: '#ff9f1c'}}>{pct.toFixed(1)}% completado</span>
      </div>

      {data.status === 'achieved' && (
        <div className="flex items-center gap-3 mt-6 py-3 px-4 rounded-lg shadow-sm" style={{background: 'linear-gradient(to right, #ffbf69, #f79d65)', border: '1px solid #ff9f1c', color: '#8B4513'}}>
          <FaCheckCircle style={{color: '#ff9f1c'}} className="text-xl" />
          <div>
            <p className="font-bold">¡Felicitaciones!</p>
            <p className="text-sm">Has cumplido tu meta del mes</p>
          </div>
        </div>
      )}

      {data.status !== 'achieved' && pct >= 80 && (
        <div className="flex items-center gap-3 mt-6 py-3 px-4 rounded-lg shadow-sm" style={{background: 'linear-gradient(to right, #ffbf69, #f79d65)', border: '1px solid #ff9f1c', color: '#8B4513'}}>
          <FaBullseye style={{color: '#ff9f1c'}} className="text-xl" />
          <div>
            <p className="font-bold">¡Casi lo logras!</p>
            <p className="text-sm">Estás muy cerca de cumplir tu meta</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default MyGoalProgress;