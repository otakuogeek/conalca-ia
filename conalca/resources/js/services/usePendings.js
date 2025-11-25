import { useState, useCallback } from 'react';
import axios from 'axios';

// Ruta base
const API = '/pendings';

// Extraer token csrf
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Get all pendings by group_cotization_id
export const getPendings = (groupCotizationId) =>
  axios.get(`/groups/${groupCotizationId}/pendings`, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() }
  });

// Create
export const createPending = (data) =>
  axios.post(API, data, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

// Update
export const updatePending = (id, data) =>
  axios.put(`${API}/${id}`, data, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

// Delete
export const deletePending = (id) =>
  axios.delete(`${API}/${id}`, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

// Optional: custom React Hook para uso sencillo
export function usePendings(groupCotizationId) {
  const [pendings, setPendings] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchPendings = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getPendings(groupCotizationId);
      setPendings(res.data);
    } finally {
      setLoading(false);
    }
  }, [groupCotizationId]);

  const addPending = async (pending) => {
    const res = await createPending(pending);
    // Recarga lista
    fetchPendings();
    return res.data;
  };

  const editPending = async (id, changes) => {
    const res = await updatePending(id, changes);
    fetchPendings();
    return res.data;
  };

  const removePending = async (id) => {
    await deletePending(id);
    fetchPendings();
  };

  return {
    pendings, loading, fetchPendings, addPending, editPending, removePending,
  };
}