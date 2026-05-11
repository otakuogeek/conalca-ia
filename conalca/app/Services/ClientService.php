<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientService
{
    /**
     * Resuelve cliente por NIT (documento) o nombre. Si no existe, retorna prompt para crear.
     *
     * @param string|null $nit
     * @param string|null $name
     * @param array $additionalData
     * @return array
     */
    public function resolveOrCreateClient(?string $nit, ?string $name, array $additionalData = []): array
    {
        $client = null;

        // Buscar por NIT (documento) - coincidencia exacta
        if ($nit) {
            $client = Client::where('documento', $nit)->first();
        }

        // Buscar por nombre - coincidencia parcial
        if (!$client && $name) {
            $client = Client::where('cliente', 'LIKE', '%' . trim($name) . '%')->first();
        }

        // Cliente encontrado
        if ($client) {
            return [
                'success' => true,
                'exists' => true,
                'client' => $client,
                'message' => "Cliente encontrado: {$client->cliente} (NIT: {$client->documento})"
            ];
        }

        // Cliente no existe: ¿crear automáticamente?
        if (!empty($additionalData['create_if_not_exists'])) {
            return $this->createClient(array_merge([
                'documento' => $nit,
                'cliente' => $name,
            ], $additionalData));
        }

        // Retornar prompt para crear
        return [
            'success' => false,
            'exists' => false,
            'message' => 'Cliente no encontrado en el sistema.',
            'prompt' => '¿Desea crear un nuevo cliente? Por favor proporcione los siguientes datos:',
            'required_fields' => [
                'cliente' => 'Nombre o razón social (requerido)',
                'documento' => 'NIT o documento (requerido)',
                'telefono' => 'Teléfono (opcional)',
                'direccion' => 'Dirección (opcional)',
                'ciudad' => 'Ciudad (opcional)'
            ],
            'next_step' => 'Al proporcionar estos datos, el cliente se creará y se continuará con el proceso de cotización.'
        ];
    }

    /**
     * Crea un nuevo cliente y retorna los datos.
     *
     * @param array $data
     * @return array
     */
    public function createClient(array $data): array
    {
        $validator = Validator::make($data, [
            'cliente' => 'required|string|max:255',
            'documento' => 'required|string|max:50|unique:clients,documento',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
                'message' => 'Error al crear cliente: ' . implode(', ', $validator->errors()->all())
            ];
        }

        try {
            $client = Client::create([
                'cliente' => $data['cliente'],
                'documento' => $data['documento'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
            ]);

            Log::info('Cliente creado automáticamente', [
                'client_id' => $client->id,
                'cliente' => $client->cliente,
                'documento' => $client->documento
            ]);

            return [
                'success' => true,
                'exists' => true,
                'client' => $client,
                'message' => "Cliente creado exitosamente: {$client->cliente} (NIT: {$client->documento}). Continuando con el proceso de cotización..."
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear cliente', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ];
        }
    }
}
