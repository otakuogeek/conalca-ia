<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\CotizacionModel;
use App\Services\QuoteAssistantService;
use App\Services\SilogtranService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateQuote extends Component
{
    public $step = 0;

    public $search = '900346779';
    public $client_name = '';

    public $messages = [];

    public ?Client $client;

    public $openai_thread = "";

    public $openai_current_run = "";

    public $input_message = "";

    public $quote_data = [];

    public function render()
    {
        $this->syncChat();

        return view('livewire.create-quote');
    }

    public function submitClient()
    {
        $this->validate([
            'search' => 'required'
        ]);

        $client_name = '';
        $existing_client = Client::where('document', $this->search)->first();
        if ($existing_client) {
            $client_name = $existing_client->name;
            $this->client = $existing_client;
        } else {
            $client_name = SilogtranService::getClient($this->search);
            if (!$client_name) {
                $this->addError('search', 'Cliente no encontrado');
                return;
            }

            $this->client = Client::firstOrCreate(
                [
                    'document' => $this->search
                ],
                [
                    'name' => $client_name
                ]
            );
        }

        $this->client_name = $client_name;

        $this->openai_thread = QuoteAssistantService::getThread($this->client);
        $this->openai_current_run = $this->client->openai_current_run;
        $this->step = 1;
    }

    public function syncChat()
    {
        if ($this->openai_thread && $this->step == 1) {
            $this->messages = QuoteAssistantService::getMessages($this->openai_thread);

            if ($this->openai_current_run) {
                Log::info("Checking run status " . $this->openai_current_run);
                $quote_data = QuoteAssistantService::checkRunStatus($this->openai_thread, $this->openai_current_run);
                if ($quote_data == 'finished') {
                    $this->openai_current_run = null;
                    $this->client->openai_current_run = null;
                    $this->client->save();
                    Log::info("This just finished ");

                } else if ($quote_data) {
                    Log::info("Finally the quote data: ", [$quote_data]);
                    $this->quote_data = $quote_data;
                    $this->openai_current_run = null;
                    $this->client->openai_current_run = null;
                    $this->client->save();
                }
            }
        }
    }

    public function sendMessage()
    {
        $this->validate([
            'input_message' => 'required|string|min:1'
        ]);

        $new_message = QuoteAssistantService::createMessage($this->openai_thread, $this->input_message);

        if ($new_message) {
            $this->messages[] = $new_message;

            $run = QuoteAssistantService::runAssistant($this->openai_thread);
            if (isset($run['id'])) {
                $this->openai_current_run = $run['id'];
                $this->client->openai_current_run = $this->openai_current_run;
                $this->client->save();
            } else {
                Log::info("No run created", [$run]);
            }

        }

        $this->input_message = "";

    }

    public function saveCotizacion(){
        $cotizacion = new CotizacionModel([
            'origen' => $this->quote_data['ciudad_origen'],
            'destino' => $this->quote_data['ciudad_destino'],
            'cantidad_mercancia' => $this->quote_data['valor_flete'],
            'peso' => $this->quote_data['peso_mercancia'],
            'producto' => $this->quote_data['producto'],
            'empaque' => $this->quote_data['empaque'],
            'cantidad_vehiculos' => $this->quote_data['cantidad_vehiculos'],
            'clase_vehiculo' => $this->quote_data['clase_vehiculo'],
            'carroceria' => $this->quote_data['carroceria'],
            'minimo_modelo' => $this->quote_data['minimo_modelo'],
            'tipo_flete' => $this->quote_data['tipo_flete'],
            'conductor' => $this->quote_data['conductor'],
            'flete_ministerio' => $this->quote_data['flete_ministerio'],
            'tipo_de_tarifa' => $this->quote_data['tipo_de_tarifa'],
            'tarifa_cliente' => $this->quote_data['tarifa_cliente'],
            'valor_mercancia' => $this->quote_data['valor_mercancia'],
            'descripcion_mercancia' => $this->quote_data['descripcion_mercancia'],
            'sub_cliente' => $this->quote_data['sub_cliente'],
            'partner' => $this->quote_data['partner'],
            'tipo_viaje' => $this->quote_data['tipo_viaje'],
        ]);
        $cotizacion->save();
    }
    // public function saveCotizacion()
    // {
    //     // Si tienes VARIAS cotizaciones en $this->quotes_data (array de arrays)
    //     foreach ($this->quotes_data as $quote_data) {
    //         $cotizacion = new CotizacionModel([
    //             'origen' => $quote_data['ciudad_origen'],
    //             'destino' => $quote_data['ciudad_destino'],
    //             'cantidad_mercancia' => $quote_data['valor_flete'], // Asegúrate que este campo existe
    //             'peso' => $quote_data['peso_mercancia'],
    //             'producto' => $quote_data['producto'],
    //             'empaque' => $quote_data['empaque'],
    //             'cantidad_vehiculos' => $quote_data['cantidad_vehiculos'],
    //             'clase_vehiculo' => $quote_data['clase_vehiculo'],
    //             'carroceria' => $quote_data['carroceria'],
    //             'minimo_modelo' => $quote_data['minimo_modelo'],
    //             'tipo_flete' => $quote_data['tipo_flete'],
    //             'conductor' => $quote_data['conductor'],
    //             'flete_ministerio' => $quote_data['flete_ministerio'],
    //             'tipo_de_tarifa' => $quote_data['tipo_de_tarifa'],
    //             'tarifa_cliente' => $quote_data['tarifa_cliente'],
    //             'valor_mercancia' => $quote_data['valor_mercancia'],
    //             'descripcion_mercancia' => $quote_data['descripcion_mercancia'],
    //             'sub_cliente' => $quote_data['sub_cliente'],
    //             'partner' => $quote_data['partner'],
    //             'tipo_viaje' => $quote_data['tipo_viaje'],
    //         ]);
    //         $cotizacion->save();
    //     }
    // }
}
