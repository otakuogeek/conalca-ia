import axios from 'axios';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Cache en memoria — se carga una sola vez por sesión de página
let _productsCache = null;

export const fetchProducts = () => {
  if (_productsCache) return Promise.resolve(_productsCache);
  return axios.get('/products', {
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    withCredentials: true,
  }).then(resp => {
    _productsCache = resp;
    return resp;
  });
};