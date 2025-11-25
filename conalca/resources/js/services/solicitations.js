import axios from 'axios';

/* ─── GLOBAL AXIOS CONFIG (already done once) ───
   – keeps withCredentials = true
   – keeps X-Requested-With, etc.
   – csrf-token helper
*/
const getCsrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const API = '/solicitations';        // <── important path

/* ─── CRUD + chat helpers ─────────────────────── */
export const fetchSolicitations = (params = {}) =>
  axios.get(API, { params, headers: { 'X-CSRF-TOKEN': getCsrf() } });

export const getSolicitation = (id) =>
  axios.get(`${API}/${id}`, { headers: { 'X-CSRF-TOKEN': getCsrf() } });

export const createSolicitation = (data) =>
  axios.post(API, data, { headers: { 'X-CSRF-TOKEN': getCsrf() } });

export const updateSolicitation = (id, data) =>
  axios.put(`${API}/${id}`, data, { headers: { 'X-CSRF-TOKEN': getCsrf() } });

export const sendSolicitation = (id) =>
  axios.post(`${API}/${id}/send`, null, { headers: { 'X-CSRF-TOKEN': getCsrf() } });

/* ─── chat messages ───────────────────────────── */
/* chat helpers  */
export const fetchMessages = (id, channel) =>
axios.get(`/solicitations/${id}/messages/${channel}`,
{ headers:{'X-CSRF-TOKEN':getCsrf()} });

export const createMessage = (id, channel, content) =>
axios.post(`/solicitations/${id}/messages/${channel}`,
{ content },
{ headers:{'X-CSRF-TOKEN':getCsrf()} });

export const assignPrice = (id, data) =>
  axios.put(`${API}/${id}/price`, data, { headers:{'X-CSRF-TOKEN':getCsrf()} });

export const addSuperNote = (id, note) =>
  axios.post(`${API}/${id}/note`, { superadmin_note: note },
             { headers:{'X-CSRF-TOKEN':getCsrf()} });

export const escalateRequest = (id) =>
  axios.post(`${API}/${id}/escalate`, null,
             { headers:{'X-CSRF-TOKEN':getCsrf()} });


             