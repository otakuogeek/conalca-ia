/**
 * Servicio de Arcangel API - Cliente JavaScript
 * 
 * Uso en Vue/React/JavaScript:
 * import ArcangelService from '@/services/ArcangelService';
 * 
 * const vehiculos = await ArcangelService.filtrarVehiculos({
 *   ciudad: 'BOGOTA',
 *   clase: 'TURBO',
 *   minScore: 30
 * });
 */

class ArcangelService {
  constructor(baseURL = '/api/arcangel', axios = null) {
    this.baseURL = baseURL;
    this.axios = axios || (typeof window !== 'undefined' && window.axios);
    
    if (!this.axios) {
      console.warn('Axios no está disponible. Por favor, proporciona una instancia.');
    }
  }

  /**
   * Realizar petición GET
   */
  async get(endpoint, params = {}) {
    try {
      const response = await this.axios.get(`${this.baseURL}${endpoint}`, {
        params,
        headers: {
          'Accept': 'application/json',
        }
      });
      return response.data;
    } catch (error) {
      this.handleError(error);
      throw error;
    }
  }

  /**
   * Verificar salud de la API
   */
  async healthCheck() {
    return await this.get('/health');
  }

  /**
   * Obtener todas las ciudades disponibles
   * @param {boolean} useCache - Usar caché
   * @returns {Promise<Object>}
   */
  async obtenerCiudades(useCache = false) {
    return await this.get('/ciudades', { use_cache: useCache });
  }

  /**
   * Obtener vehículos cercanos a una ciudad
   * @param {string} ciudad - Nombre de la ciudad
   * @param {boolean} useCache - Usar caché
   * @returns {Promise<Object>}
   */
  async obtenerVehiculosCercanos(ciudad, useCache = false) {
    return await this.get('/vehiculos/cercanos', {
      ciudad,
      use_cache: useCache
    });
  }

  /**
   * Obtener clases de vehículos disponibles en una ciudad
   * @param {string} ciudad - Nombre de la ciudad
   * @param {boolean} useCache - Usar caché
   * @returns {Promise<Object>}
   */
  async obtenerClasesDisponibles(ciudad, useCache = false) {
    return await this.get('/vehiculos/clases', {
      ciudad,
      use_cache: useCache
    });
  }

  /**
   * Filtrar vehículos por múltiples criterios
   * @param {Object} filtros - Criterios de filtrado
   * @param {string} filtros.ciudad - Nombre de la ciudad (requerido)
   * @param {string|string[]} filtros.clase - Clase(s) de vehículo
   * @param {number} filtros.minScore - Score mínimo
   * @param {number} filtros.limit - Límite de resultados
   * @param {boolean} filtros.useCache - Usar caché
   * @returns {Promise<Object>}
   */
  async filtrarVehiculos({
    ciudad,
    clase = null,
    clases = null,
    minScore = null,
    limit = null,
    useCache = false
  }) {
    const params = {
      ciudad,
      use_cache: useCache
    };

    // Si se proporciona 'clase' (singular)
    if (clase && !clases) {
      params.clase = clase;
    }

    // Si se proporcionan 'clases' (plural - array)
    if (clases && Array.isArray(clases)) {
      clases.forEach((c, index) => {
        params[`clases[${index}]`] = c;
      });
    }

    if (minScore !== null) {
      params.min_score = minScore;
    }

    if (limit !== null) {
      params.limit = limit;
    }

    return await this.get('/vehiculos/filtrar', params);
  }

  /**
   * Limpiar caché de Arcangel
   */
  async limpiarCache() {
    try {
      const response = await this.axios.post(`${this.baseURL}/clear-cache`);
      return response.data;
    } catch (error) {
      this.handleError(error);
      throw error;
    }
  }

  /**
   * Manejar errores de API
   */
  handleError(error) {
    if (error.response) {
      // Error de respuesta del servidor
      console.error('Error de API:', {
        status: error.response.status,
        message: error.response.data.message || 'Error desconocido',
        errors: error.response.data.errors || {}
      });
    } else if (error.request) {
      // Error de red
      console.error('Error de red: No se recibió respuesta del servidor');
    } else {
      // Error de configuración
      console.error('Error:', error.message);
    }
  }
}

// Exportar como módulo ES6
export default ArcangelService;

// También exportar como CommonJS para compatibilidad
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ArcangelService;
}

// ============================================================================
// EJEMPLOS DE USO
// ============================================================================

/**
 * Ejemplo 1: Vue 3 Composition API
 */
/*
<script setup>
import { ref, onMounted } from 'vue';
import ArcangelService from '@/services/ArcangelService';

const arcangel = new ArcangelService('/api/arcangel', axios);
const vehiculos = ref([]);
const loading = ref(false);
const selectedCity = ref('BOGOTA');
const selectedClase = ref('TURBO');

const cargarVehiculos = async () => {
  loading.value = true;
  try {
    const response = await arcangel.filtrarVehiculos({
      ciudad: selectedCity.value,
      clase: selectedClase.value,
      minScore: 30,
      limit: 20
    });
    vehiculos.value = response.data.vehiculos;
  } catch (error) {
    console.error('Error cargando vehículos:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(cargarVehiculos);
</script>

<template>
  <div>
    <h2>Vehículos Disponibles</h2>
    <div v-if="loading">Cargando...</div>
    <div v-else>
      <div v-for="vehiculo in vehiculos" :key="vehiculo.placa">
        {{ vehiculo.placa }} - {{ vehiculo.conductor }} ({{ vehiculo.clase }})
      </div>
    </div>
  </div>
</template>
*/

/**
 * Ejemplo 2: Vue 3 Options API
 */
/*
import ArcangelService from '@/services/ArcangelService';

export default {
  data() {
    return {
      arcangel: new ArcangelService('/api/arcangel', this.$axios),
      vehiculos: [],
      clases: {},
      selectedCity: 'BOGOTA',
      filters: {
        clase: null,
        minScore: 0,
        limit: 50
      }
    };
  },
  methods: {
    async cargarClases() {
      const response = await this.arcangel.obtenerClasesDisponibles(this.selectedCity);
      this.clases = response.data.clases;
    },
    async filtrarVehiculos() {
      const response = await this.arcangel.filtrarVehiculos({
        ciudad: this.selectedCity,
        clase: this.filters.clase,
        minScore: this.filters.minScore,
        limit: this.filters.limit
      });
      this.vehiculos = response.data.vehiculos;
    },
    async buscarMejorConductor() {
      const response = await this.arcangel.filtrarVehiculos({
        ciudad: this.selectedCity,
        clase: 'TURBO',
        minScore: 40,
        limit: 1
      });
      return response.data.vehiculos[0];
    }
  },
  mounted() {
    this.cargarClases();
    this.filtrarVehiculos();
  }
};
*/

/**
 * Ejemplo 3: React Hooks
 */
/*
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ArcangelService from './services/ArcangelService';

const arcangel = new ArcangelService('/api/arcangel', axios);

function VehiculosDisponibles() {
  const [vehiculos, setVehiculos] = useState([]);
  const [loading, setLoading] = useState(false);
  const [ciudad, setCiudad] = useState('BOGOTA');
  const [clase, setClase] = useState('');
  
  useEffect(() => {
    cargarVehiculos();
  }, [ciudad, clase]);
  
  const cargarVehiculos = async () => {
    setLoading(true);
    try {
      const response = await arcangel.filtrarVehiculos({
        ciudad,
        clase: clase || null,
        minScore: 30
      });
      setVehiculos(response.data.vehiculos);
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div>
      <h2>Vehículos en {ciudad}</h2>
      <select value={clase} onChange={(e) => setClase(e.target.value)}>
        <option value="">Todos</option>
        <option value="TURBO">Turbo</option>
        <option value="CAMIONETA">Camioneta</option>
        <option value="SENCILLO">Sencillo</option>
      </select>
      {loading ? (
        <p>Cargando...</p>
      ) : (
        <ul>
          {vehiculos.map(v => (
            <li key={v.placa}>
              {v.placa} - {v.conductor} (Score: {v.score})
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
*/

/**
 * Ejemplo 4: JavaScript Vanilla (sin frameworks)
 */
/*
const arcangel = new ArcangelService('/api/arcangel', axios);

async function cargarYMostrarVehiculos() {
  const response = await arcangel.filtrarVehiculos({
    ciudad: 'BOGOTA',
    clases: ['TURBO', 'CAMIONETA'],
    minScore: 30,
    limit: 10
  });
  
  const vehiculos = response.data.vehiculos;
  const lista = document.getElementById('lista-vehiculos');
  
  lista.innerHTML = vehiculos.map(v => `
    <div class="vehiculo">
      <strong>${v.placa}</strong> - ${v.conductor}
      <br>
      <small>Clase: ${v.clase} | Score: ${v.score}</small>
    </div>
  `).join('');
}

document.addEventListener('DOMContentLoaded', cargarYMostrarVehiculos);
*/

/**
 * Ejemplo 5: Integración con Livewire (Alpine.js)
 */
/*
<div x-data="vehiculosComponent()">
  <select x-model="selectedCity" @change="cargarVehiculos()">
    <option value="BOGOTA">Bogotá</option>
    <option value="MEDELLIN">Medellín</option>
    <option value="CALI">Cali</option>
  </select>
  
  <select x-model="selectedClase" @change="cargarVehiculos()">
    <option value="">Todas las clases</option>
    <option value="TURBO">Turbo</option>
    <option value="CAMIONETA">Camioneta</option>
  </select>
  
  <div x-show="loading">Cargando...</div>
  
  <template x-for="vehiculo in vehiculos" :key="vehiculo.placa">
    <div class="card">
      <span x-text="vehiculo.placa"></span> -
      <span x-text="vehiculo.conductor"></span>
      (Score: <span x-text="vehiculo.score"></span>)
    </div>
  </template>
</div>

<script>
function vehiculosComponent() {
  return {
    vehiculos: [],
    loading: false,
    selectedCity: 'BOGOTA',
    selectedClase: '',
    
    async cargarVehiculos() {
      this.loading = true;
      const arcangel = new ArcangelService('/api/arcangel', axios);
      const response = await arcangel.filtrarVehiculos({
        ciudad: this.selectedCity,
        clase: this.selectedClase || null,
        minScore: 25
      });
      this.vehiculos = response.data.vehiculos;
      this.loading = false;
    },
    
    init() {
      this.cargarVehiculos();
    }
  }
}
</script>
*/
