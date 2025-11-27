// services/pricingService.js
import axios from 'axios';

const API = '/pricings-solutions';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export const fetchLatestPricingsByRoute = ({ origin, destination }) =>
  axios.get(`${API}/latest-by-route`, {
    params: { origin, destination },
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const fetchVehicleSuggestions = (routesPayload) =>
  axios.post('/pricing-suggestions', routesPayload, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });