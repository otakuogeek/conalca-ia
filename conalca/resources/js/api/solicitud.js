
/* =========================================================================
 *  Axios configuración global
 * =========================================================================*/
import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials                    = true;

/* =========================================================================
 *  Helper para CSRF
 * =========================================================================*/
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/* =========================================================================
 *  Prefijos de las diferentes “áreas” de la API
 * =========================================================================*/
const SOLICITUD_API = '/solicitud';   // rutas que creamos en web.php
const CATALOG_API   = '/catalog'; // para endpoints de catálogos

/* =========================================================================
 *  Catálogos (búsqueda de combos/autocomplete)
 * =========================================================================*/

// Clientes
export const searchClientes    = q =>
  axios.get(`${CATALOG_API}/clientes`, { params: { q } });

// Ciudades
export const searchCiudades    = q =>
  axios.get(`${CATALOG_API}/ciudades`, { params: { q } });

// Vendedores
export const searchVendedores  = q =>
  axios.get(`${CATALOG_API}/vendedores`, { params: { q } });

// Productos
export const searchProductos   = q =>
  axios.get(`${CATALOG_API}/productos`, { params: { q } });

// Empaques
export const searchEmpaques    = q =>
  axios.get(`${CATALOG_API}/empaques`, { params: { q } });

// Clases de vehículo
export const searchClases      = q =>
  axios.get(`${CATALOG_API}/vehiculos/clases`, { params: { q } });

// Carrocerías de vehículo
export const searchCarrocerias = q =>
  axios.get(`${CATALOG_API}/vehiculos/carrocerias`, { params: { q } });

/* Prefills:  GET /solicitud/{cotizacion}/prefill   */
export const fetchPrefill = (cotizacionId) =>
  axios.get(`${SOLICITUD_API}/${cotizacionId}/prefill`, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

/* =========================================================================
 *  Solicitud de transporte  (wizard)
 * =========================================================================*/

/**
 *  Guarda un paso del wizard.
 *  payload = {
 *     step : 'step_1' … 'step_6',
 *     CotizacionModelId : 123,
 *     … campos del paso …
 *  }
 */
export const saveStep = (payload) =>
  axios.post(`${SOLICITUD_API}/progreso`, payload, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

/**
 *  Trae la solicitud (y sub-tablas) asociada a una cotización.
 */
export const fetchSolicitud = (cotizacionId) =>
  axios.get(`${SOLICITUD_API}/${cotizacionId}/data`, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

