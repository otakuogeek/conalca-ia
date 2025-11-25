<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;

class RegistrarLlamadaConductor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arcangel:registrar-llamada 
                            {origen : Ciudad de origen}
                            {destino : Ciudad de destino}
                            {peso : Peso de la mercancía en kg}
                            {vehiculo : Tipo de vehículo requerido}
                            {--valor-declarado= : Valor declarado de la mercancía}
                            {--cantidad=1 : Cantidad de unidades}
                            {--tipo-embalaje=Caja : Tipo de embalaje}
                            {--json : Mostrar respuesta en formato JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra una nueva llamada de conductor en el sistema usando la API de Arcángel';

    /**
     * Arcangel Service instance
     *
     * @var ArcangelService
     */
    protected $arcangel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ArcangelService $arcangel)
    {
        parent::__construct();
        $this->arcangel = $arcangel;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $origen = $this->argument('origen');
        $destino = $this->argument('destino');
        $peso = $this->argument('peso');
        $vehiculo = $this->argument('vehiculo');
        
        $valorDeclarado = $this->option('valor-declarado') ?? '0';
        $cantidad = $this->option('cantidad');
        $tipoEmbalaje = $this->option('tipo-embalaje');
        $mostrarJson = $this->option('json');

        $this->info('🚛 Registrando nueva llamada de conductor');
        $this->newLine();

        // Mostrar información de la llamada
        $this->line("📍 Origen: {$origen}");
        $this->line("📍 Destino: {$destino}");
        $this->line("⚖️  Peso: {$peso} kg");
        $this->line("🚗 Vehículo: {$vehiculo}");
        $this->line("💰 Valor declarado: \${$valorDeclarado}");
        $this->line("📦 Cantidad: {$cantidad}");
        $this->line("📦 Embalaje: {$tipoEmbalaje}");
        $this->newLine();

        if (!$this->confirm('¿Desea continuar con el registro?', true)) {
            $this->warn('❌ Operación cancelada');
            return Command::SUCCESS;
        }

        $this->line('⏳ Procesando registro...');

        try {
            // Paso 1: Consultar localidad de origen
            $this->line('1️⃣  Consultando localidad de origen...');
            $localidadOrigen = $this->consultarLocalidad($origen);
            
            if (!$localidadOrigen) {
                $this->error("❌ No se encontró la localidad de origen: {$origen}");
                return Command::FAILURE;
            }
            
            $this->info("   ✓ Localidad origen encontrada: {$localidadOrigen['nombre']} (Código: {$localidadOrigen['codigo']})");

            // Paso 2: Consultar localidad de destino
            $this->line('2️⃣  Consultando localidad de destino...');
            $localidadDestino = $this->consultarLocalidad($destino);
            
            if (!$localidadDestino) {
                $this->error("❌ No se encontró la localidad de destino: {$destino}");
                return Command::FAILURE;
            }
            
            $this->info("   ✓ Localidad destino encontrada: {$localidadDestino['nombre']} (Código: {$localidadDestino['codigo']})");

            // Paso 3: Registrar la llamada
            $this->line('3️⃣  Registrando llamada en el sistema...');
            
            $datosLlamada = [
                'ciudad_origen' => $localidadOrigen['nombre'],
                'ciudad_origen_codigo' => $localidadOrigen['codigo'],
                'ciudad_destino' => $localidadDestino['nombre'],
                'ciudad_destino_codigo' => $localidadDestino['codigo'],
                'peso_mercancia' => $peso,
                'vehiculo_requerido' => $vehiculo,
                'valor_declarado' => $valorDeclarado,
                'cantidad' => $cantidad,
                'tipo_embalaje' => $tipoEmbalaje,
                'fecha_registro' => now()->toDateTimeString(),
            ];

            // Registrar en Arcángel (ajustar endpoint según tu API)
            $response = $this->arcangel->post('llamadas/conductores', $datosLlamada);

            $this->newLine();
            $this->info('✅ Llamada registrada exitosamente');
            
            if ($mostrarJson) {
                $this->newLine();
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->mostrarResumenLlamada($response);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error al registrar la llamada');
            $this->error("Mensaje: {$e->getMessage()}");
            
            Log::error('Error en comando arcangel:registrar-llamada', [
                'origen' => $origen,
                'destino' => $destino,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Consultar localidad por nombre de ciudad
     *
     * @param string $ciudad
     * @return array|null
     */
    protected function consultarLocalidad($ciudad)
    {
        try {
            // Obtener todas las ciudades disponibles de Arcángel
            $ciudades = $this->arcangel->getCiudades(true, 60); // Usar caché por 60 minutos

            if (empty($ciudades)) {
                return null;
            }

            // Buscar la ciudad (búsqueda flexible sin acentos)
            $ciudadBuscada = $this->normalizarTexto($ciudad);
            $ciudadesEncontradas = array_filter($ciudades, function($c) use ($ciudadBuscada) {
                return stripos($this->normalizarTexto($c), $ciudadBuscada) !== false;
            });

            if (empty($ciudadesEncontradas)) {
                return null;
            }

            // Tomar la primera coincidencia
            $ciudadEncontrada = array_values($ciudadesEncontradas)[0];

            return [
                'id' => null,
                'nombre' => $ciudadEncontrada,
                'codigo' => 'N/A',
                'departamento' => 'Colombia',
            ];

        } catch (\Exception $e) {
            Log::error("Error consultando ciudad: {$ciudad}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mostrar resumen de la llamada registrada
     *
     * @param mixed $response
     * @return void
     */
    protected function mostrarResumenLlamada($response)
    {
        $this->newLine();
        $this->info('📋 Resumen del registro:');
        $this->newLine();

        if (is_array($response)) {
            $headers = ['Campo', 'Valor'];
            $rows = [];

            foreach ($response as $key => $value) {
                if (is_scalar($value)) {
                    $rows[] = [ucfirst(str_replace('_', ' ', $key)), $value];
                }
            }

            if (!empty($rows)) {
                $this->table($headers, $rows);
            } else {
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * Normalizar texto removiendo acentos y caracteres especiales
     *
     * @param string $texto
     * @return string
     */
    protected function normalizarTexto(string $texto): string
    {
        $texto = strtoupper($texto);
        $acentos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ñ' => 'N', 'ñ' => 'n'
        ];
        return strtr($texto, $acentos);
    }
}
