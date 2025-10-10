import axios from 'axios';

// Configuración global
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Ruta base de la API
const API = 'users/columns';

// Obtener token CSRF desde la meta etiqueta en tu layout Blade
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Funciones exportadas
export const getUserColumns = () =>
  axios.get(API, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const createUserColumn = (data) =>
  axios.post(API, data, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const updateUserColumn = (id, data) =>
  axios.put(`${API}/${id}`, data, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const deleteUserColumn = (id) =>
  axios.delete(`${API}/${id}`, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const reorderUserColumns = (newOrder) =>
  axios.post(`${API}/reorder`, { new_order: newOrder }, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
