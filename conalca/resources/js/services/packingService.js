import axios from 'axios';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Cache en memoria — se carga una sola vez por sesión de página
let _packingsCache = null;

export const fetchPackings = () => {
  if (_packingsCache) return Promise.resolve(_packingsCache);
  return axios.get('/packings', {
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    withCredentials: true,
  }).then(resp => {
    _packingsCache = resp;
    return resp;
  });
};