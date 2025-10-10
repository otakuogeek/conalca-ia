<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\VoiceGenerationTrait;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use App\Jobs\CallDriversJob;
use Illuminate\Support\Facades\Cache;  
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\CallDriver;
use App\Models\BlockedDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Models\SolicitudTransporte;
use App\Models\CotizacionModel;
use App\Models\Pricing;

class TransportController extends Controller
{
    use VoiceGenerationTrait;

    public function callDrivers(Request $request)
    {
        set_time_limit(120); 

        $prompt = $request->input('prompt');
        $callAllDrivers = $request->input('call_all_drivers');
        $excludeAcceptorsLastOffer = $request->input('exclude_acceptors_last_offer');



        // $type_vehicle = $request->input('type_vehicle');
        // $lastRecordId = DB::table('solicitud_transportes')->latest('id')->value('id');

        $cotizacionId = $request->input('cotizacion_id');
        $solicitud = SolicitudTransporte::where('cotizacion_model_id', $cotizacionId)->first();

        if (!$solicitud) {
            return response()->json(['error' => 'No se encontró la solicitud de transporte'], 404);
        }
        $solicitudId = $solicitud->id;
        
        $type_vehicle = $this->getVehicleType($solicitudId);

        $quotation_id = $solicitudId;

        // Obtener conductores desde la base de datos en lugar de datos hardcodeados
        $drivers = \App\Models\VehicleOwnerHolderDriver::query()
            ->whereRaw('LOWER(clasevehiculo) = ?', [strtolower($type_vehicle)])
            ->select([
                'id',
                \Illuminate\Support\Facades\DB::raw("COALESCE(telefonoconductor, telefonopropietario, telefonoposeedor) as phone_number"),
                'conductor as name',
                'clasevehiculo as type_vehicle'
            ])
            ->get()
            ->map(function ($driver) {
                $num = preg_replace('/[^0-9]/', '', $driver->phone_number);
                if (strlen($num) > 10) {
                    $num = substr($num, -10);
                }
                $driver->phone_number = $num;
                return $driver;
            })
            ->toArray();

        $blockedDrivers = DB::table('blocked_drivers')->pluck('driver_id')->toArray();

        // Obtener conductores que aceptaron la última oferta si el filtro está activo
        $acceptedDriversLastOffer = [];

        if ($excludeAcceptorsLastOffer) {
            $lastCallId = DB::table('calls')->latest('id')->value('id');

            if ($lastCallId) {
                $acceptedDriversLastOffer = DB::table('call_drivers')
                    ->where('call_id', $lastCallId)
                    ->where('response', 'accepted')
                    ->pluck('driver_id')
                    ->toArray();
            }
        }

        $filteredDrivers = array_filter($drivers, function ($driver) use ($type_vehicle, $blockedDrivers, $callAllDrivers, $excludeAcceptorsLastOffer, $acceptedDriversLastOffer) {
            if ($callAllDrivers) {
                // No excluir bloqueados, pero sí excluir los que aceptaron la última oferta si el filtro está activo
                return 
                    !($excludeAcceptorsLastOffer && in_array($driver['id'], $acceptedDriversLastOffer)) &&
                    ($type_vehicle === 'todos' || $driver['type_vehicle'] === $type_vehicle);
            }
        
            // Cuando callAllDrivers está desactivado, se excluyen los bloqueados y los que aceptaron la última oferta (si aplica)
            return 
                !in_array($driver['id'], $blockedDrivers) && 
                !($excludeAcceptorsLastOffer && in_array($driver['id'], $acceptedDriversLastOffer)) &&
                ($type_vehicle === 'todos' || $driver['type_vehicle'] === $type_vehicle);
        });

        $total_drivers = count($filteredDrivers);

        if($total_drivers == 0) {
            return response()->json(['message' => 'No hay conductores para llamar']);
        }

        $call = $this->createOrRetrieveCall($quotation_id, $total_drivers);
        $callId = $call->id; 

        DB::table('jobs')->truncate();

        $voicePrompt = $this->generateVoicePrompt(
            "Tenemos una oferta. $prompt",
            'transport_offer'
        );

        $voicePrompt = $this->generateVoicePrompt(
            "¿Como estas?",
            'how_are_you'
        );

        $voicePrompt = $this->generateVoicePrompt(
            "Para aceptar la oferta Presione 1 o 2 si no le interesa",
            'basic_confirmation_offer'
        );

        $secondVoicePrompt = $this->generateVoicePrompt(
            "Gracias por aceptar la oferta. Pronto se proporcionarán más detalles sobre el viaje.",
            'offer_accepted'
        );

        $thirdVoicePrompt = $this->generateVoicePrompt(
            "Lamentamos que no esté interesado. Nos pondremos en contacto con usted para ofrecerle otras ofertas en el futuro.",
            'offer_declined'
        );

        $thirdVoicePrompt = $this->generateVoicePrompt(
            "La opción marcada no es válida. Por favor, intente de nuevo.",
            'invalid_option'
        );
        

        if (!$drivers || count($drivers) === 0) {
            return response()->json(['message' => 'No drivers provided'], 400);
        }
        
        // Iterar sobre cada conductor y despachar un trabajo individual
        foreach ($filteredDrivers as $driver) {
            dispatch(new CallDriversJob($driver, $quotation_id, $total_drivers, $callId));
        }
    
        return response()->json(['message' => 'LLamando a los conductores...']);
    }

    public function makeCall($phoneNumber) 
    {
        // Esta función ha sido migrada a TwilioService
        // Usar CallController->callDrivers() para funcionalidad completa
        Log::warning('makeCall() está deprecated. Usar TwilioService o CallController.');
        return 'deprecated';
    }

    private function updateDriverStatus($phoneNumber, $status)
    {
        // Encuentra al conductor en la base de datos por el número de teléfono
        $driver = Driver::where('phone_number', $phoneNumber)->first();

        if ($driver) {
            // Actualizar el estado de la oferta (accepted o rejected)
            $driver->offer_status = $status;
            $driver->save();
        }
    }

    public function createOrRetrieveCall($quotation_id, $total_drivers) {
        // Verificar si ya existe una llamada para la cotización
        $existingCall = Call::where('quotation_id', $quotation_id)->first();
    
        if ($existingCall) {
            // Si ya existe, actualizamos su estado a "calling"
            $existingCall->update([
                'total_drivers' => $total_drivers,
                'calls_made' => 0,
                'status' => 'calling'
            ]);
            return $existingCall; // Retorna la llamada actualizada
        }
    
        // Crear una nueva llamada si no existe
        return Call::create([
            'quotation_id' => $quotation_id,
            'total_drivers' => $total_drivers,
            'calls_made' => 0,
            'calls_accepted' => 0,
            'calls_not_answered' => 0,
            'status' => 'calling'
        ]);
    }

    public function addCallDriver($call_id, $id, $name, $phoneNumber, $type_vehicle, $status) {
        // Verificar si ya existe un conductor con estado "accepted" para la misma cotización y llamada
        $existingAcceptedDriver = CallDriver::where('call_id', $call_id)
            ->where('response', 'accepted')
            ->where('driver_id', $id)
            ->first();
    
        if ($existingAcceptedDriver) {
            return response()->json([
                'message' => 'A driver has already accepted this offer',
                'call_driver' => $existingAcceptedDriver
            ], 200);
        }
    
        // Crear el nuevo registro en CallDriver
        $callDriver = CallDriver::create([
            'call_id' => $call_id,
            'driver_id' => $id,
            'driver_name' => $name,
            'driver_phone_number' => $phoneNumber,
            'type_vehicle' => $type_vehicle,
            'response' => $status
        ]);
    
        // Incrementar el número de llamadas realizadas en la tabla Calls
        Call::where('id', $call_id)->increment('calls_made');
    
        // Incrementar el conteo de respuestas aceptadas o no respondidas
        if ($status === 'accepted') {
            Call::where('id', $call_id)->increment('calls_accepted');
            Log::info('Driver accepted. Continuing with precalification process - more drivers can still accept.');
        } elseif ($status === 'not_answered') {
            Call::where('id', $call_id)->increment('calls_not_answered');
        }
    
        return response()->json([
            'message' => 'Driver response recorded',
            'call_driver' => $callDriver
        ], 201);
    }

    public function blockDriver($id, $name, $phoneNumber, $type_vehicle) {
        // Verificar si el conductor ya está bloqueado
        $blockedDriver = BlockedDriver::where('driver_id', $id)->first();
    
        if ($blockedDriver) {
            // Si ya existe, retornarlo sin crear uno nuevo
            return response()->json(['message' => 'Driver already blocked', 'blocked_driver' => $blockedDriver], 200);
        }
    
        // Si no existe, crearlo y retornarlo
        $blockedDriver = BlockedDriver::create([
            'driver_id' => $id,
            'driver_name' => $name,
            'driver_phone_number' => $phoneNumber,
            'type_vehicle' => $type_vehicle,
        ]);
    
        return response()->json(['message' => 'Driver blocked', 'blocked_driver' => $blockedDriver], 201);
    }

    public function getCallsWithAcceptedDrivers(Request $request)
    {
        // Extract filters from request
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $typeVehicle = $request->query('type_vehicle');
        $status = $request->query('status');

        // Query Calls with filtered drivers
        $calls = Call::with(['drivers' => function ($query) use ($typeVehicle) {
            $query->where('response', 'accepted');

            if ($typeVehicle) {
                $query->where('type_vehicle', $typeVehicle);
            }
        }]);

        if ($startDate && $endDate) {
            $calls->whereBetween('created_at', [
                $startDate,
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Apply status filter
        if ($status) {
            $calls->where('status', $status);
        }

        if ($typeVehicle) {
            $calls->whereHas('drivers', function ($query) use ($typeVehicle) {
                $query->where('type_vehicle', $typeVehicle);
            });
        }

        // return response()->json($calls->get());
         // Check if no filters are provided and return last 6 records by default
        if (!$startDate && !$endDate && !$typeVehicle && !$status) {
            $latestCalls = $calls->latest()->take(6)->get();
        } else {
            $latestCalls = $calls->get();
        }

        return response()->json($latestCalls);
    }

    public function getLastCallWithAcceptedDrivers()
    {
        $call = Call::with(['drivers' => function ($query) {
            $query->where('response', 'accepted'); // Filtra solo los conductores que aceptaron
        }])
        ->latest('created_at') // Ordena por la fecha de creación, descendente
        ->first(); // Obtiene solo el último registro
    
        return response()->json($call);
    }

    public function cancelCall(Request $request)
    {
        $calls = Call::where('status', 'calling')->get();

        if ($calls->isEmpty()) {
            return response()->json(['message' => 'No hay llamadas en ejecución para cancelar.'], 404);
        }

        // Actualizar el estado a "canceled"
        foreach ($calls as $call) {
            $call->update(['status' => 'canceled']);
        }

        return response()->json(['message' => 'Llamadas canceladas con éxito.']);
    }

    public function getBlockedDrivers()
    {
        $blockedDrivers = BlockedDriver::all();
        return response()->json($blockedDrivers);
    }

    public function unblockDriver(Request $request)
    {
        BlockedDriver::where('driver_id', $request->driver_id)->delete();

        return response()->json([
            'message' => 'Conductor eliminado de la lista de bloqueados correctamente.',
        ], 200);
    }

      public function getVehicleType($solicitudId = null)
    {
        // Si el ID es nulo, obtener el último registro de SolicitudTransporte
        $solicitud = $solicitudId ? SolicitudTransporte::find($solicitudId) : SolicitudTransporte::latest()->first();

        // Verificar si se encontró una solicitud
        if (!$solicitud || !$solicitud->cotizacion_model_id) {
            return "Solicitud o cotización no encontrada";
        }

        // Obtener la cotización y el pricing_id
        $cotizacion = CotizacionModel::find($solicitud->cotizacion_model_id);

        if (!$cotizacion || !$cotizacion->pricing_id) {
            return "Cotización o Pricing no encontrado";
        }

        // Obtener el tipo de vehículo desde Pricing
        $pricing = Pricing::find($cotizacion->pricing_id);

        if (!$pricing) {
            return "Pricing no encontrado";
        }

        // Retornar directamente el tipo de vehículo como texto
        return $pricing->vehicle_type;
    }
}
