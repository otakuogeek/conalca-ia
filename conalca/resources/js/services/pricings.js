import axios from 'axios';

const getCsrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const API = '/pricings-solutions';    

export const createPricing = (data) =>
  axios.post(`${API}`, data, { headers:{ 'X-CSRF-TOKEN': getCsrf() } });

export const updatePricing = (id, data) =>
  axios.put(`${API}/${id}`, data, { headers:{ 'X-CSRF-TOKEN': getCsrf() } });