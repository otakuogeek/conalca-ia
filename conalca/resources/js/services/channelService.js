import axios from 'axios';

// Configuración general
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const API = '/groups-quotes'; // Ruta definida en Laravel

// Obtener CSRF token si lo necesitas para POST, PUT o DELETE
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Obtener todas las cotizaciones agrupadas
export const fetchGroupQuotations = () =>
  axios.get(API, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

  export const updateGroupStatus = (groupId, newStatus) =>
  axios.patch(`${API}/${groupId}/status`, 
    { status: newStatus }, 
    {
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    }
  );

