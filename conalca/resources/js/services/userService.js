import axios from 'axios';

// Configuración global para Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Ruta base actual: /users
const API = '/users';

// Obtener CSRF token desde la meta etiqueta del layout Blade
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Funciones exportadas
export const getCurrentUser = () =>
  axios.get(`${API}/me`, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });


export const fetchUsers = (params = {}) =>
  axios.get(API, {
    params,
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const getAssignableRoles = () =>
  axios.get(`${API}/assignable-roles`, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const getPotentialParents = (role) =>
  axios.get(`${API}/potential-parents`, {
    params: { role },
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });


export const createUser = (data) =>
  axios.post(API, data, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const updateUser = (id, data) =>
  axios.put(`${API}/${id}`, data, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const deleteUser = (id) =>
  axios.delete(`${API}/${id}`, {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
