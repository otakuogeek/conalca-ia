<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\GroupCotization;
use App\Mail\StepCompleted;

class QuoteEmailController extends Controller
{
    public function sendQuoteEmail(Request $request)
    {
        try {
            Log::info('Datos recibidos para envío de email', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // Validar datos requeridos
            $request->validate([
                'group_id' => 'required|integer|exists:group_cotizations,id',
                'client_email' => 'required',
                'email_data' => 'required|array'
            ]);

            $groupId = $request->group_id;
            $emailData = $request->email_data;

            // Normalize client_email: accept string (comma/semicolon separated) or array
            $rawEmail = $request->client_email;
            if (is_array($rawEmail)) {
                $clientEmails = array_map('trim', $rawEmail);
            } else {
                $clientEmails = array_map('trim', preg_split('/[,;]/', $rawEmail));
            }
            $clientEmails = array_filter($clientEmails, function ($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
            });
            $clientEmails = array_values($clientEmails);

            if (empty($clientEmails)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron correos electrónicos válidos',
                ], 422);
            }

            Log::info('Iniciando envío de email de cotización', [
                'group_id' => $groupId,
                'client_emails' => $clientEmails,
                'recipients_count' => count($clientEmails),
                'user_id' => auth()->id(),
                'email_data_keys' => array_keys($emailData)
            ]);

            // Obtener el grupo de cotización con sus rutas
            $group = GroupCotization::with('cotizaciones')->find($groupId);
            
            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grupo de cotización no encontrado'
                ], 404);
            }

            // Preparar las rutas desde la base de datos (no del frontend)
            $routesFromDB = $group->cotizaciones
                ->filter(function($cotizacion) {
                    // Filtrar rutas con valor 0 o NULL
                    return $cotizacion->valor > 0;
                })
                ->map(function($cotizacion) {
                    return [
                        'ciudad_origen' => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        'vehiculo_requerido' => $cotizacion->vehiculo_filtrado ?? $cotizacion->vehiculo_requerido,
                        'vehiculo_solicitado' => $cotizacion->vehiculo_requerido,
                        'peso_mercancia' => $cotizacion->peso_mercancia,
                        'valor' => $cotizacion->valor,
                        'valor_final' => $cotizacion->valor,
                        'valor_declarado' => $cotizacion->valor_declarado,
                        'tipo_producto' => $cotizacion->tipo_producto,
                        'tipo_mercancia' => $cotizacion->tipo_mercancia,
                        'tipaco_codigo' => $cotizacion->tipaco_codigo ?? null,
                        'itesoltra_vehiculoacompanamiento' => $cotizacion->itesoltra_vehiculoacompanamiento ?? 0,
                        'itesoltra_acompanamientovalor' => $cotizacion->itesoltra_acompanamientovalor ?? 0,
                    ];
                })->toArray();
            
            // Si no hay rutas válidas, retornar error
            if (empty($routesFromDB)) {
                Log::warning('No hay rutas válidas para enviar (todas tienen valor 0)', [
                    'group_id' => $groupId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No hay rutas con precio válido para enviar'
                ], 400);
            }

            Log::info('Rutas obtenidas de la base de datos', [
                'routes_count' => count($routesFromDB),
                'routes' => $routesFromDB
            ]);

            // Preparar datos para el email en el formato esperado por StepCompleted
            $emailDataForMail = [
                // Cliente
                'group_cotization_id'   => $groupId,
                'client_name'           => $emailData['client_name'] ?? 'No especificado',
                'client_document'       => $emailData['client_document'] ?? 'No especificado',
                'client_location'       => $emailData['client_location'] ?? 'No especificada',
                'client_phone_numbers'  => $emailData['client_phone_numbers'] ?? 'No especificados',
                
                // Encabezados y saludo
                'title'                 => $emailData['title'] ?? 'Cotización de Servicios de Transporte',
                'text'                  => $emailData['text'] ?? '',
                'greeting'              => $emailData['greeting'] ?? 'Quedamos atentos a cualquier inquietud. ¡Gracias por confiar en nosotros!',
                
                // Rutas y totales (usar datos de la BD, no del frontend)
                'routes'                => $this->prepareRoutesWithTotals($routesFromDB),
                'total_price'           => $this->calculateTotalPrice($routesFromDB),
                
                // Datos de la solicitud original para comparativo
                'cargo_type'            => $group->cargo_type ?? $emailData['cargo_type'] ?? null,
                'operation_type'        => $group->operation_type ?? $emailData['operation_type'] ?? null,
                
                // Asesor
                'asesor_name'           => $emailData['asesor_name'] ?? 'Asesor Comercial',
                'asesor_phone'  => $emailData['asesor_phone'] ?? optional(auth()->user())->phone ?? '',
                'asesor_email'  => $emailData['asesor_email'] ?? optional(auth()->user())->email ?? '',
                'asesor_pbx'            => '',
                'asesor_ubicacion'      => '',
                'advisor_signature'     => null,
                
                // Otros
                'id_last_created'       => $group->id,
                'created_at'            => now(),
                'acceptOfferDecision'   => ''
            ];

            // Enviar el email a todos los destinatarios
            Mail::to($clientEmails)->send(new StepCompleted($emailDataForMail));

            Log::info('Email de cotización enviado exitosamente', [
                'group_id' => $groupId,
                'client_emails' => $clientEmails,
                'recipients_count' => count($clientEmails),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email enviado exitosamente a ' . count($clientEmails) . ' destinatario(s)',
                'group_id' => $groupId,
                'email_sent_to' => $clientEmails
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al enviar email de cotización', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al enviar email de cotización', [
                'group_id' => $request->group_id ?? null,
                'client_email' => $request->client_email ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preparar rutas con totales calculados correctamente
     */
    private function prepareRoutesWithTotals($routes)
    {
        return collect($routes)->map(function($route) {
            $valorBase = floatval($route['valor'] ?? 0);
            
            // Calcular acompañamiento si existe
            $cant = (int)($route['itesoltra_vehiculoacompanamiento'] ?? 0);
            $valorUnitario = (float)($route['itesoltra_acompanamientovalor'] ?? 0);
            $totalAcompanamiento = $cant > 0 ? $cant * $valorUnitario : 0;
            
            // Asegurar que valor_final incluya el acompañamiento
            if (!isset($route['valor_final']) || empty($route['valor_final'])) {
                $route['valor_final'] = $valorBase + $totalAcompanamiento;
            }
            
            return $route;
        })->toArray();
    }

    /**
     * Calcular el precio total de todas las rutas incluyendo acompañamiento
     */
    private function calculateTotalPrice($routes)
    {
        return collect($routes)->sum(function($route) {
            $valorBase = floatval($route['valor'] ?? 0);
            
            // Calcular acompañamiento si existe
            $cant = (int)($route['itesoltra_vehiculoacompanamiento'] ?? 0);
            $valorUnitario = (float)($route['itesoltra_acompanamientovalor'] ?? 0);
            $totalAcompanamiento = $cant > 0 ? $cant * $valorUnitario : 0;
            
            // Usar valor_final si existe, o calcular la suma
            return isset($route['valor_final']) && !empty($route['valor_final']) 
                ? floatval($route['valor_final'])
                : $valorBase + $totalAcompanamiento;
        });
    }
}
