# 🎯 GUÍA RÁPIDA DE INTEGRACIÓN - FILTRADO DE VEHÍCULOS

## 🚀 Inicio Rápido (5 minutos)

### Paso 1: Verificar que el API funciona

```bash
# Sin autenticación (health check)
curl https://conalcaia.conalca.com.co/api/arcangel/health

# Respuesta esperada:
# {"success": true, "message": "API Arcangel disponible"}
```

### Paso 2: Obtener un token de autenticación

```bash
# Opción A: Desde tu aplicación web (login normal)
# El token se guarda automáticamente en localStorage

# Opción B: Desde terminal (para pruebas)
php artisan tinker
>>> $user = App\Models\User::where('email', 'tu@email.com')->first();
>>> $token = $user->createToken('api-test')->plainTextToken;
>>> echo $token;
```

### Paso 3: Hacer tu primera consulta

```bash
# Obtener vehículos TURBO en Bogotá
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&clase=TURBO&limit=5" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

---

## 📱 Integración en Frontend

### Vue 3 - Ejemplo Completo

```vue
<template>
  <div class="vehiculos-container">
    <h2>Buscar Vehículos Disponibles</h2>
    
    <!-- Filtros -->
    <div class="filtros">
      <select v-model="filters.ciudad" @change="cargarVehiculos">
        <option value="">Selecciona ciudad</option>
        <option v-for="ciudad in ciudades" :key="ciudad" :value="ciudad">
          {{ ciudad }}
        </option>
      </select>
      
      <select v-model="filters.clase" @change="cargarVehiculos">
        <option value="">Todas las clases</option>
        <option value="TURBO">Turbo</option>
        <option value="CAMIONETA">Camioneta</option>
        <option value="SENCILLO">Sencillo</option>
      </select>
      
      <input 
        v-model.number="filters.minScore" 
        type="number" 
        min="0" 
        max="100"
        placeholder="Score mínimo"
        @input="cargarVehiculos"
      />
      
      <button @click="limpiarFiltros">Limpiar</button>
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="loading">
      <span>Cargando vehículos...</span>
    </div>
    
    <!-- Resultados -->
    <div v-else-if="vehiculos.length > 0" class="resultados">
      <p>{{ vehiculos.length }} vehículos encontrados</p>
      
      <div 
        v-for="vehiculo in vehiculos" 
        :key="vehiculo.placa"
        class="vehiculo-card"
        @click="seleccionarVehiculo(vehiculo)"
      >
        <div class="vehiculo-header">
          <span class="placa">{{ vehiculo.placa }}</span>
          <span class="score" :class="scoreClass(vehiculo.score)">
            Score: {{ vehiculo.score }}
          </span>
        </div>
        <div class="vehiculo-body">
          <p class="conductor">{{ vehiculo.conductor }}</p>
          <p class="telefono">📞 {{ vehiculo.telefono }}</p>
          <span class="clase-badge">{{ vehiculo.clase }}</span>
        </div>
      </div>
    </div>
    
    <!-- Sin resultados -->
    <div v-else class="sin-resultados">
      <p>No se encontraron vehículos con los filtros aplicados</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

// Estado
const vehiculos = ref([]);
const ciudades = ref([]);
const loading = ref(false);

const filters = reactive({
  ciudad: 'BOGOTA',
  clase: '',
  minScore: 0
});

// Cargar ciudades disponibles
const cargarCiudades = async () => {
  try {
    const response = await axios.get('/api/arcangel/ciudades');
    ciudades.value = response.data.data.ciudades;
  } catch (error) {
    console.error('Error cargando ciudades:', error);
  }
};

// Cargar vehículos con filtros
const cargarVehiculos = async () => {
  if (!filters.ciudad) return;
  
  loading.value = true;
  try {
    const params = {
      ciudad: filters.ciudad,
      ...(filters.clase && { clase: filters.clase }),
      ...(filters.minScore > 0 && { min_score: filters.minScore })
    };
    
    const response = await axios.get('/api/arcangel/vehiculos/filtrar', { params });
    vehiculos.value = response.data.data.vehiculos;
  } catch (error) {
    console.error('Error cargando vehículos:', error);
    alert('Error al cargar vehículos');
  } finally {
    loading.value = false;
  }
};

// Limpiar filtros
const limpiarFiltros = () => {
  filters.clase = '';
  filters.minScore = 0;
  cargarVehiculos();
};

// Seleccionar vehículo
const seleccionarVehiculo = (vehiculo) => {
  console.log('Vehículo seleccionado:', vehiculo);
  // Aquí puedes emitir un evento o navegar a otra vista
  // emit('vehiculo-seleccionado', vehiculo);
};

// Clase CSS según score
const scoreClass = (score) => {
  if (score >= 40) return 'score-excelente';
  if (score >= 25) return 'score-bueno';
  return 'score-regular';
};

// Inicializar
onMounted(() => {
  cargarCiudades();
  cargarVehiculos();
});
</script>

<style scoped>
.vehiculos-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.filtros {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filtros select,
.filtros input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
}

.filtros button {
  padding: 10px 20px;
  background: #3490dc;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.filtros button:hover {
  background: #2779bd;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.resultados {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.vehiculo-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 15px;
  cursor: pointer;
  transition: all 0.3s;
}

.vehiculo-card:hover {
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.vehiculo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.placa {
  font-weight: bold;
  font-size: 18px;
  color: #2d3748;
}

.score {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.score-excelente {
  background: #48bb78;
  color: white;
}

.score-bueno {
  background: #ecc94b;
  color: #744210;
}

.score-regular {
  background: #cbd5e0;
  color: #4a5568;
}

.conductor {
  font-weight: 500;
  margin: 5px 0;
  color: #4a5568;
}

.telefono {
  color: #718096;
  font-size: 14px;
  margin: 5px 0;
}

.clase-badge {
  display: inline-block;
  background: #667eea;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  margin-top: 10px;
}

.sin-resultados {
  text-align: center;
  padding: 40px;
  color: #a0aec0;
}
</style>
```

---

## 🔧 Integración en Controlador Laravel

### Ejemplo: Asignación automática en Cotizaciones

```php
<?php

namespace App\Http\Controllers;

use App\Services\ArcangelService;
use App\Models\Cotizacion;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    protected ArcangelService $arcangel;
    
    public function __construct(ArcangelService $arcangel)
    {
        $this->arcangel = $arcangel;
    }
    
    /**
     * Asignar automáticamente el mejor conductor disponible
     */
    public function asignarConductorAutomatico(Request $request, $cotizacionId)
    {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        
        try {
            // Determinar tipo de vehículo necesario según la carga
            $tipoVehiculo = $this->determinarTipoVehiculo($cotizacion);
            
            // Buscar los mejores 3 conductores disponibles
            $resultado = $this->arcangel->getVehiculosFiltrados(
                ciudad: $cotizacion->ciudad_origen,
                clases: $tipoVehiculo,
                minScore: 30,  // Score mínimo aceptable
                limit: 3,      // Top 3 conductores
                useCache: false // Datos en tiempo real
            );
            
            if (empty($resultado['vehiculos'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay conductores disponibles en este momento'
                ], 404);
            }
            
            // Asignar el conductor con mejor score
            $conductor = $resultado['vehiculos'][0];
            
            $cotizacion->update([
                'conductor_placa' => $conductor['placa'],
                'conductor_nombre' => $conductor['conductor'],
                'conductor_telefono' => $conductor['telefono'],
                'conductor_tipo_vehiculo' => $conductor['clase'],
                'conductor_score' => $conductor['score'],
                'estado' => 'asignado'
            ]);
            
            // Notificar al conductor (SMS, llamada, etc.)
            $this->notificarConductor($conductor, $cotizacion);
            
            return response()->json([
                'success' => true,
                'message' => 'Conductor asignado exitosamente',
                'conductor' => $conductor,
                'alternativas' => array_slice($resultado['vehiculos'], 1)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar conductor: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener conductores disponibles para una cotización
     */
    public function obtenerConductoresDisponibles(Request $request, $cotizacionId)
    {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        
        $tipoVehiculo = $this->determinarTipoVehiculo($cotizacion);
        
        $resultado = $this->arcangel->getVehiculosFiltrados(
            ciudad: $cotizacion->ciudad_origen,
            clases: $tipoVehiculo,
            minScore: $request->input('min_score', 20),
            limit: $request->input('limit', 20)
        );
        
        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
    
    /**
     * Determinar tipo de vehículo según la carga
     */
    private function determinarTipoVehiculo(Cotizacion $cotizacion): array
    {
        $peso = $cotizacion->peso_total;
        $volumen = $cotizacion->volumen_total;
        
        // Lógica de negocio para determinar tipo de vehículo
        if ($peso > 5000 || $volumen > 20) {
            return ['TRACTOMULA 3', 'TRACTOMULA3'];
        } elseif ($peso > 2000 || $volumen > 10) {
            return ['TURBO', 'CAMIONETA'];
        } elseif ($peso > 500) {
            return ['CAMIONETA', 'PATINETA2'];
        } else {
            return ['SENCILLO', 'PATINETA2'];
        }
    }
    
    /**
     * Notificar al conductor (implementar según tu sistema)
     */
    private function notificarConductor($conductor, $cotizacion)
    {
        // Implementar notificación por SMS, WhatsApp, llamada, etc.
        // Ejemplo: enviar SMS con detalles de la cotización
    }
}
```

---

## 📊 Dashboard de Estadísticas

### Ejemplo: Componente de estadísticas en tiempo real

```vue
<template>
  <div class="dashboard">
    <h2>Disponibilidad de Vehículos en {{ ciudadActual }}</h2>
    
    <div class="stats-grid">
      <div class="stat-card" v-for="(cantidad, clase) in estadisticas" :key="clase">
        <div class="stat-icon">🚗</div>
        <div class="stat-info">
          <h3>{{ clase }}</h3>
          <p class="stat-number">{{ cantidad }}</p>
          <p class="stat-label">vehículos disponibles</p>
        </div>
        <button @click="filtrarPorClase(clase)" class="stat-button">
          Ver Todos
        </button>
      </div>
    </div>
    
    <div class="grafico">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

const ciudadActual = ref('BOGOTA');
const estadisticas = ref({});
const chartInstance = ref(null);
const chartCanvas = ref(null);

const cargarEstadisticas = async () => {
  try {
    const response = await axios.get('/api/arcangel/vehiculos/clases', {
      params: { ciudad: ciudadActual.value }
    });
    
    estadisticas.value = response.data.data.clases;
    actualizarGrafico();
  } catch (error) {
    console.error('Error cargando estadísticas:', error);
  }
};

const actualizarGrafico = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }
  
  const ctx = chartCanvas.value.getContext('2d');
  
  chartInstance.value = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: Object.keys(estadisticas.value),
      datasets: [{
        label: 'Vehículos Disponibles',
        data: Object.values(estadisticas.value),
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
};

const filtrarPorClase = (clase) => {
  // Navegar a la vista de vehículos con el filtro aplicado
  window.location.href = `/vehiculos?ciudad=${ciudadActual.value}&clase=${clase}`;
};

onMounted(() => {
  cargarEstadisticas();
  
  // Actualizar cada 2 minutos
  setInterval(cargarEstadisticas, 120000);
});
</script>

<style scoped>
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin: 30px 0;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.stat-number {
  font-size: 36px;
  font-weight: bold;
  color: #2d3748;
  margin: 10px 0;
}

.stat-label {
  color: #718096;
  font-size: 14px;
}

.stat-button {
  margin-top: 15px;
  padding: 8px 16px;
  background: #3490dc;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.grafico {
  margin-top: 40px;
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
```

---

## 🧪 Testing

### Prueba unitaria (PHPUnit)

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArcangelFiltradoTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $token;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }
    
    /** @test */
    public function puede_filtrar_vehiculos_por_clase()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&clase=TURBO');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'ciudad',
                         'filtros',
                         'vehiculos',
                         'total_vehiculos'
                     ]
                 ]);
                 
        $this->assertTrue($response->json('success'));
    }
    
    /** @test */
    public function puede_filtrar_vehiculos_por_multiple_clases()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&clases[]=TURBO&clases[]=CAMIONETA');
        
        $response->assertStatus(200);
        
        $vehiculos = $response->json('data.vehiculos');
        foreach ($vehiculos as $vehiculo) {
            $this->assertContains($vehiculo['clase'], ['TURBO', 'CAMIONETA']);
        }
    }
    
    /** @test */
    public function requiere_autenticacion()
    {
        $response = $this->getJson('/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA');
        
        $response->assertStatus(401);
    }
}
```

---

## 📝 Checklist de Integración

### ✅ Backend
- [x] ArcangelService con métodos de filtrado
- [x] ArcangelController con endpoints REST
- [x] Rutas registradas en api.php
- [x] Validación de parámetros
- [x] Autenticación Sanctum
- [x] Logging de operaciones
- [x] Manejo de errores

### ✅ Frontend
- [ ] Servicio JavaScript creado
- [ ] Componente Vue de búsqueda
- [ ] Dashboard de estadísticas
- [ ] Integración en formularios existentes
- [ ] Notificaciones de disponibilidad
- [ ] Tests E2E

### ✅ Testing
- [x] Script PHP de pruebas
- [x] Script Bash con curl
- [ ] Tests unitarios PHPUnit
- [ ] Tests de integración
- [ ] Tests E2E con Cypress

### ✅ Documentación
- [x] README del endpoint
- [x] Ejemplos de uso
- [x] Guía de integración
- [x] Comentarios en código

---

## 🎉 ¡Listo para usar!

El sistema está completamente funcional y listo para integrar en tu aplicación.

**¿Necesitas ayuda?** Consulta los archivos de documentación:
- `ENDPOINT_FILTRADO_VEHICULOS.md` - Documentación completa del API
- `RESUMEN_IMPLEMENTACION_FILTROS.md` - Resumen técnico de la implementación

**Para probar:** Ejecuta `php test_filtro_vehiculos.php`
