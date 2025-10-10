import axios from 'axios';

/* ------------- CONFIG GLOBAL ------------- */
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

/* ------------- HELPERS ------------- */
const API = '/api';                                     // prefijo
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/* ------------- ENDPOINTS ------------- */
export const fetchCallStatus = (cotizacionId) =>
  axios.get(`calls/${cotizacionId}`, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

export const startCallingDrivers = (cotizacionId, prompt = '') =>
  axios.post(
    `${API}/call-drivers`,
    { cotizacion_model_id: cotizacionId, prompt }, 
    { headers: { 'X-CSRF-TOKEN': getCsrfToken() } }
  );

export const startCallingDriversGroup = (groupCotizationId, prompt = '') =>
  axios.post(
    `${API}/call-drivers-group`,
    { group_cotization_id: groupCotizationId, prompt }, 
    { headers: { 'X-CSRF-TOKEN': getCsrfToken() } }
  );

export const selectDriver = (cotizacionId, driverId) =>
  axios.post(
    `calls/${cotizacionId}/select-driver`,
    { driver_id: driverId },
    { headers: { 'X-CSRF-TOKEN': getCsrfToken() } }
  );

export const startElevenLabsCalls = (cotizacionId) =>
  axios.post(
    `${API}/start-elevenlabs-calls/${cotizacionId}`,
    {},
    { headers: { 'X-CSRF-TOKEN': getCsrfToken() } }
  );