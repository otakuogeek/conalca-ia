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
            // Validar datos requeridos
            $request->validate([
                'group_id' => 'required|integer|exists:group_cotizations,id',
                'client_email' => 'required|email',
                'email_data' => 'required|array'
            ]);

            $groupId = $request->group_id;
            $clientEmail = $request->client_email;
            $emailData = $request->email_data;

            Log::info('Iniciando envío de email de cotización', [
                'group_id' => $groupId,
                'client_email' => $clientEmail,
                'user_id' => auth()->id()
            ]);

            // Obtener el grupo de cotización con sus rutas
            $group = GroupCotization::with('cotizaciones')->find($groupId);
            
            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grupo de cotización no encontrado'
                ], 404);
            }

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
                
                // Rutas y totales
                'routes'                => $emailData['routes'] ?? [],
                'total_price'           => $emailData['total_price'] ?? 0,
                
                // Asesor
                'asesor_name'           => $emailData['asesor_name'] ?? 'Asesor Comercial',
                'asesor_phone'          => $emailData['asesor_phone'] ?? '',
                'asesor_email'          => $emailData['asesor_email'] ?? 'sebastianmarketing6@gmail.com',
                'asesor_pbx'            => '',
                'asesor_ubicacion'      => '',
                'advisor_signature'     => null,
                
                // Otros
                'id_last_created'       => $group->id,
                'created_at'            => now(),
                'acceptOfferDecision'   => ''
            ];

            // Enviar el email
            Mail::to($clientEmail)->send(new StepCompleted($emailDataForMail));

            Log::info('Email de cotización enviado exitosamente', [
                'group_id' => $groupId,
                'client_email' => $clientEmail,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email enviado exitosamente',
                'group_id' => $groupId,
                'email_sent_to' => $clientEmail
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
}
