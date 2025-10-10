// resources/js/components/Goals/NotificationList.jsx
import React, { useEffect, useState } from 'react';
import { getNotifications, readNotification } from '../../services/goals';
import { FiX, FiAward, FiAlertTriangle } from 'react-icons/fi';

const NotificationList = () => {
  const [items, setItems] = useState([]);

  const load = () => getNotifications().then(r => setItems(r.data));

  useEffect(() => { load(); }, []);

  const markRead = (id) =>
    readNotification(id).then(() => setItems(items.filter(n => n.id !== id)));

  return (
    <div className="space-y-4">
      <h2 className="text-xl font-semibold text-gray-800 mb-4">Notificaciones Recientes</h2>
      {items.length === 0 && (
        <div className="text-center py-8">
          <div className="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4" style={{backgroundColor: '#ffbf69'}}>
            <FiAward style={{color: '#ff9f1c'}} className="text-2xl" />
          </div>
          <p className="text-gray-500 text-lg">No hay notificaciones</p>
          <p className="text-gray-400 text-sm">Las notificaciones aparecerán aquí cuando haya actualizaciones</p>
        </div>
      )}
      <div className="space-y-3">
        {items.map(n => (
          <div
            key={n.id}
            className="flex items-center justify-between rounded-xl px-4 py-3 transition-all duration-200 hover:shadow-md"
            style={{
              background: 'linear-gradient(to right, #ffbf69, #f79d65)',
              border: '1px solid #ff9f1c'
            }}
            onMouseEnter={(e) => e.target.style.borderColor = '#f79d65'}
            onMouseLeave={(e) => e.target.style.borderColor = '#ff9f1c'}
          >
            <div className="flex items-center space-x-3">
              {n.data.status === 'achieved' ? (
                <div className="w-10 h-10 rounded-full flex items-center justify-center" style={{backgroundColor: '#ff9f1c'}}>
                  <FiAward className="text-white text-lg" />
                </div>
              ) : (
                <div className="w-10 h-10 rounded-full flex items-center justify-center" style={{backgroundColor: '#f79d65'}}>
                  <FiAlertTriangle className="text-white text-lg" />
                </div>
              )}
              <div className="text-gray-700">
                <p className="font-semibold text-gray-900">{n.data.commercial}</p>
                {n.data.status === 'achieved' ? (
                  <p className="font-medium" style={{color: '#8B4513'}}>🎯 Meta cumplida</p>
                ) : (
                  <p className="font-medium" style={{color: '#8B4513'}}>⚠️ Meta no cumplida</p>
                )}
                <p className="text-gray-500 text-sm">
                  Progreso: {n.data.achieved} / {n.data.target}
                </p>
              </div>
            </div>
            <button
              aria-label="Marcar como leída"
              className="focus:outline-none transition-colors duration-200 p-1 rounded-full"
              style={{color: '#8B4513'}}
              onMouseEnter={(e) => {e.target.style.color = '#ff9f1c'; e.target.style.backgroundColor = '#ffbf69'}}
              onMouseLeave={(e) => {e.target.style.color = '#8B4513'; e.target.style.backgroundColor = 'transparent'}}
              onClick={() => markRead(n.id)}
            >
              <FiX size={20} />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};

export default NotificationList;