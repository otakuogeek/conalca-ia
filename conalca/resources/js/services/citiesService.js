// resources/js/services/citiesService.js
import axios from 'axios';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

export const fetchCities = () => {
  return axios.get('/cities', {    // <--- change here
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    withCredentials: true,
  });
};