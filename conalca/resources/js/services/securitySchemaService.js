import axios from 'axios';

export const fetchSecuritySchemaData = () =>
    axios.get('/security-schema/data');

export const searchProducts = (q, category) =>
    axios.get('/security-schema/search-products', { params: { q, category } });

export const addProduct = (product_code, category) =>
    axios.post('/security-schema/products', { product_code, category });

export const deleteProduct = (id) =>
    axios.delete(`/security-schema/products/${id}`);

export const createPriceRange = (data) =>
    axios.post('/security-schema/price-ranges', data);

export const updatePriceRange = (id, data) =>
    axios.put(`/security-schema/price-ranges/${id}`, data);

export const deletePriceRange = (id) =>
    axios.delete(`/security-schema/price-ranges/${id}`);

export const fetchSchemaForPricing = () =>
    axios.get('/security-schema/for-pricing');

// ─── Clientes ────────────────────────────────────────────────
export const searchClients = (q) =>
    axios.get('/security-schema/search-clients', { params: { q } });

export const getAssignedClients = () =>
    axios.get('/security-schema/assigned-clients');

export const assignClient = (client_id) =>
    axios.post('/security-schema/assign-client', { client_id });

export const unassignClient = (id) =>
    axios.delete(`/security-schema/unassign-client/${id}`);

// ─── Overrides Comerciales ───────────────────────────────────
export const saveUserOverride = (data) =>
    axios.post('/security-schema/user-override', data);

export const deleteUserOverride = (baseRangeId) =>
    axios.delete(`/security-schema/user-override/${baseRangeId}`);
