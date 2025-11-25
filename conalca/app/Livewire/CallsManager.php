<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Llamada;
use App\Models\CotizacionModel;
use Illuminate\Support\Facades\Log;

class CallsManager extends Component
{
    public $groupCotizationId;
    public $cotizacionId;
    public $llamadas = [];
    public $statistics = [];
    public $isLoading = false;

    protected $listeners = ['refreshLlamadas' => 'loadLlamadas'];

    public function mount($groupCotizationId = null, $cotizacionId = null)
    {
        $this->groupCotizationId = $groupCotizationId;
        $this->cotizacionId = $cotizacionId;
        $this->loadLlamadas();
    }

    public function loadLlamadas()
    {
        $this->isLoading = true;
        
        try {
            $query = Llamada::with(['cotizacion', 'chofer']);

            if ($this->cotizacionId) {
                $query->where('id_cotizacion', $this->cotizacionId);
            } elseif ($this->groupCotizationId) {
                $cotizacionIds = CotizacionModel::where('group_cotization_id', $this->groupCotizationId)->pluck('id');
                $query->whereIn('id_cotizacion', $cotizacionIds);
            }

            $llamadasData = $query->orderBy('created_at', 'desc')->get();

            $this->llamadas = $llamadasData->map(function ($llamada) {
                return [
                    'id_llamada' => $llamada->id_llamada,
                    'id_cotizacion' => $llamada->id_cotizacion,
                    'cotizacion' => [
                        'origen' => $llamada->cotizacion->ciudad_origen ?? 'N/A',
                        'destino' => $llamada->cotizacion->ciudad_destino ?? 'N/A',
                        'vehiculo' => $llamada->cotizacion->vehiculo_requerido ?? 'N/A',
                        'valor' => $llamada->cotizacion->valor_declarado ?? 'N/A'
                    ],
                    'chofer' => [
                        'id' => $llamada->chofer_id,
                        'nombre' => $llamada->chofer->Conductor ?? 'N/A',
                        'telefono' => $this->formatPhoneNumber($llamada->chofer->Telefonoconductor ?? 'N/A'),
                        'placa' => $llamada->chofer->Placa ?? 'N/A',
                        'clase_vehiculo' => $llamada->chofer->Clasevehiculo ?? 'N/A'
                    ],
                    'status' => $llamada->status,
                    'status_label' => Llamada::getStatusOptions()[$llamada->status] ?? $llamada->status,
                    'status_color' => $this->getStatusColor($llamada->status),
                    'created_at' => $llamada->created_at->format('d/m/Y H:i'),
                    'updated_at' => $llamada->updated_at->format('d/m/Y H:i')
                ];
            })->toArray();

            // Calcular estadísticas
            $this->statistics = [
                'total' => count($this->llamadas),
                'pendientes' => $llamadasData->where('status', Llamada::STATUS_PENDIENTE)->count(),
                'en_curso' => $llamadasData->where('status', Llamada::STATUS_EN_CURSO)->count(),
                'finalizadas' => $llamadasData->where('status', Llamada::STATUS_FINALIZADA)->count(),
                'aceptadas' => $llamadasData->where('status', Llamada::STATUS_ACEPTADA)->count(),
                'rechazadas' => $llamadasData->where('status', Llamada::STATUS_RECHAZADA)->count()
            ];

        } catch (\Exception $e) {
            Log::error("Error cargando llamadas: " . $e->getMessage());
            session()->flash('error', 'Error al cargar las llamadas');
        } finally {
            $this->isLoading = false;
        }
    }

    public function updateStatus($llamadaId, $newStatus)
    {
        try {
            $llamada = Llamada::find($llamadaId);
            if ($llamada) {
                $llamada->status = $newStatus;
                $llamada->save();

                Log::info('Estado de llamada actualizado desde Livewire', [
                    'llamada_id' => $llamadaId,
                    'new_status' => $newStatus
                ]);

                $this->loadLlamadas(); // Recargar datos
                session()->flash('success', 'Estado actualizado correctamente');
            }
        } catch (\Exception $e) {
            Log::error("Error actualizando estado: " . $e->getMessage());
            session()->flash('error', 'Error al actualizar el estado');
        }
    }

    public function registrarLlamadasParaGrupo()
    {
        if (!$this->groupCotizationId) {
            session()->flash('error', 'No se especificó el grupo de cotización');
            return;
        }

        $this->isLoading = true;

        try {
            // Hacer solicitud al endpoint existente
            $response = \Illuminate\Support\Facades\Http::post(url('/api/call-drivers-group'), [
                'group_cotization_id' => $this->groupCotizationId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                session()->flash('success', 
                    "Llamadas registradas exitosamente: {$data['summary']['total_drivers_called']} conductores para {$data['total_cotizaciones']} cotizaciones"
                );
                $this->loadLlamadas();
            } else {
                session()->flash('error', 'Error al registrar las llamadas');
            }
        } catch (\Exception $e) {
            Log::error("Error registrando llamadas desde Livewire: " . $e->getMessage());
            session()->flash('error', 'Error al conectar con el servidor');
        } finally {
            $this->isLoading = false;
        }
    }

    private function formatPhoneNumber($phone)
    {
        if (empty($phone) || $phone === 'N/A') {
            return 'N/A';
        }

        // Tomar solo el primer número
        $firstPhone = explode(' - ', $phone)[0];
        $firstPhone = explode('-', $firstPhone)[0];
        $firstPhone = preg_replace('/[^0-9]/', '', $firstPhone);
        
        return substr($firstPhone, 0, 10);
    }

    private function getStatusColor($status)
    {
        $colors = [
            Llamada::STATUS_PENDIENTE => 'bg-yellow-100 text-yellow-800',
            Llamada::STATUS_EN_CURSO => 'bg-blue-100 text-blue-800',
            Llamada::STATUS_FINALIZADA => 'bg-gray-100 text-gray-800',
            Llamada::STATUS_ACEPTADA => 'bg-green-100 text-green-800',
            Llamada::STATUS_RECHAZADA => 'bg-red-100 text-red-800'
        ];

        return $colors[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public function render()
    {
        return view('livewire.calls-manager');
    }
}
