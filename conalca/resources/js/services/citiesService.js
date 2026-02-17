// resources/js/services/citiesService.js
import axios from 'axios';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Cache en memoria — se carga una sola vez por sesión de página
let _citiesCache = null;

export const fetchCities = () => {
  if (_citiesCache) return Promise.resolve(_citiesCache);
  return axios.get('/cities', {
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    withCredentials: true,
  }).then(resp => {
    _citiesCache = resp;
    return resp;
  });
};