import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
const API  = '/cotizations';

export const fetchSacCotizations = params =>
  axios.get(`${API}/sac`, { params, headers:{'X-CSRF-TOKEN':CSRF()} });

export const fetchTransitEvents  = () =>
  axios.get(`${API}/transit-events`, { headers:{'X-CSRF-TOKEN':CSRF()} });

export const addNote = (id,data) =>
  axios.post(`${API}/${id}/notes`, data,{ headers:{'X-CSRF-TOKEN':CSRF()} });

export const getNotes = id =>
  axios.get(`${API}/${id}/notes`, { headers:{'X-CSRF-TOKEN':CSRF()} });

export const updateGroupStatus = (groupId,status) =>
  axios.patch(`${API}/group/${groupId}`,{status},{
      headers:{'X-CSRF-TOKEN':CSRF()}
  });

export const fetchGroups = params =>
  axios.get(`${API}/sac-groups`, { params, headers:{'X-CSRF-TOKEN':CSRF()} });

export const eventsList = () => axios.get('/cotizations/transit-events',
                                {headers:{'X-CSRF-TOKEN':CSRF()}});