<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CallDriversJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $driver;
    protected $quotation_id;
    protected $total_drivers;
    protected $callId;

    public function __construct(array $driver, $quotation_id, $total_drivers, $callId)
    {
        $this->driver = $driver;
        $this->quotation_id = $quotation_id;
        $this->total_drivers = $total_drivers;
        $this->callId = $callId;
    }

    public function handle()
    {
        // Verificar si las llamadas han sido canceladas antes de ejecutar
        $callStatus = DB::table('calls')
            ->where('quotation_id', $this->quotation_id)
            ->value('status');

       // Solo realiza la acción si el registro existe y el estado es 'Cancelado'
        if ($callStatus && $callStatus === 'canceled') {
            return;
        }
        
        try {
            set_time_limit(300);

            Log::info('Iniciando CallDriversJob para el conductor: ' . json_encode($this->driver));

            // Crear instancia del controlador
            $transportController = new TransportController();

            // Precalification process - all drivers should have the opportunity to accept or reject
            // No limit on number of acceptances during precalification
            Log::info('Starting precalification call - allowing all drivers to respond');

            // Generar mensaje de saludo
            $greetingPrompt = $transportController->generateVoicePrompt("Hola, te hablamos de Conalca, ¿hablo con " . $this->driver['name'] . "?", 'greeting_message');

            // Realizar la llamada
            $status = $transportController->makeCall($this->driver['phone_number']);
            Log::info('Respuesta de la llamada: ' . $status);

            // Save the driver's response in 'call_drivers' table
            $transportController->addCallDriver(
                $this->callId,
                $this->driver['id'],
                $this->driver['name'],
                $this->driver['phone_number'],
                $this->driver['type_vehicle'],
                $status
            );

            // Block driver if they did not respond or declined
            if ($status === 'not_answered' || $status === 'declined') {
                $transportController->blockDriver(
                    $this->driver['id'],
                    $this->driver['name'],
                    $this->driver['phone_number'],
                    $this->driver['type_vehicle'],
                );
            }

            // Obtener el valor de calls_made para el $callId
            $callsMade = DB::table('calls')
            ->where('id', $this->callId)
            ->value('calls_made');

            // Verificar si calls_made es igual a total_drivers
            if ($this->total_drivers == $callsMade) {
                // Actualizar el estado de la llamada
                DB::table('calls')
                    ->where('id', $this->callId)
                    ->update(['status' => 'all_called']);

                Log::info("All drivers have been called. Only $acceptedCount accepted out of 7 required.");
            }

        } catch (\Exception $e) {
            Log::error('Error en CallDriversJob: ' . $e->getMessage());
            throw $e;
        }
    }

}
