// resource/js/components/services/pricingService.js
import axios from 'axios';

const API = '/pricings-solutions';

const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export const fetchLatestPricingsByRoute = ({ origin, destination, cargo_weight }) =>
  axios.get(`${API}/latest-by-route`, {
    params: { origin, destination, cargo_weight },
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });

export const fetchVehicleSuggestions = (routesPayload) =>
  axios.post('/pricing-suggestions', routesPayload, {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

export const fetchRentabilityStats = ({ origin, destination }) =>
  axios.get('/pricing-rentability-stats', {
    params: { origin, destination },
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

export const fetchPercentageSettings = () =>
  axios.get('/pricing-percentage-settings', {
    headers: { 'X-CSRF-TOKEN': getCsrfToken() },
  });

export const fetchVehicleCapacityGuide = () =>
  axios.get('/pricing/vehicle-guide', {
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });


