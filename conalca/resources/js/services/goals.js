import axios from 'axios';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export const getGoals = () =>
  axios.get('/goals', { headers: { 'X-CSRF-TOKEN': getCsrfToken() }});

export const updateGoal = (id, data) =>
  axios.put(`/goals/${id}`, data, { headers: { 'X-CSRF-TOKEN': getCsrfToken() }});

export const getNotifications = () =>
  axios.get('/boss/notifications', { headers: { 'X-CSRF-TOKEN': getCsrfToken() }});

export const readNotification = (id) =>
  axios.post(`/boss/notifications/${id}/read`, {}, { headers: { 'X-CSRF-TOKEN': getCsrfToken() }});

export const getMyGoal = () =>
  axios.get('/my-goal', {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });