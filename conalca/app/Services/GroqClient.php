<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente Groq SDK - Wrapper optimizado para Groq API
 * 
 * Proporciona métodos tipo SDK para interactuar con Groq API
 * con mejor manejo de errores, reintentos y logging
 */
class GroqClient
{
    private $apiKey;
    private $baseUrl;
    private $defaultModel;
    private $timeout;
    private $maxRetries;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $defaultModel = null
    ) {
        $this->apiKey = $apiKey ?? env('GROQ_API_KEY');
        $this->baseUrl = $baseUrl ?? 'https://api.groq.com/openai/v1';
        $this->defaultModel = $defaultModel ?? env('GROQ_MODEL', 'qwen/qwen3-32b');
        $this->timeout = 30;
        $this->maxRetries = 3;

        if (empty($this->apiKey)) {
            throw new \Exception('GROQ_API_KEY no está configurada');
        }

        Log::info('GroqClient inicializado', [
            'model' => $this->defaultModel,
            'base_url' => $this->baseUrl
        ]);
    }

    /**
     * Crear chat completion con tool calling
     * 
     * @param array $messages Array de mensajes [{role, content}, ...]
     * @param array $tools Array de herramientas/funciones disponibles
     * @param array $options Opciones adicionales (temperature, max_tokens, etc.)
     * @return array Respuesta de Groq API
     */
    public function createChatCompletion(
        array $messages,
        array $tools = [],
        array $options = []
    ): array {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $toolChoice = $options['tool_choice'] ?? 'auto';

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        Log::info('GroqClient: Creando chat completion', [
            'model' => $model,
            'messages_count' => count($messages),
            'tools_count' => count($tools),
            'temperature' => $temperature
        ]);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->baseUrl . '/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    Log::info('GroqClient: Respuesta exitosa', [
                        'model' => $model,
                        'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                        'has_tool_calls' => isset($data['choices'][0]['message']['tool_calls']),
                        'usage' => $data['usage'] ?? null,
                        'attempt' => $attempt
                    ]);

                    return $data;
                }

                // Error HTTP
                $errorBody = $response->body();
                $errorStatus = $response->status();
                
                Log::warning('GroqClient: Error HTTP', [
                    'status' => $errorStatus,
                    'body' => $errorBody,
                    'attempt' => $attempt
                ]);

                // Si es un error 4xx, no reintentar
                if ($errorStatus >= 400 && $errorStatus < 500) {
                    throw new \Exception("Error en Groq API ({$errorStatus}): {$errorBody}");
                }

                // Para 5xx, reintentar
                $lastException = new \Exception("Error temporal en Groq API ({$errorStatus}): {$errorBody}");

            } catch (\Exception $e) {
                Log::error('GroqClient: Excepción en intento', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                $lastException = $e;

                // Esperar antes de reintentar (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1); // 1s, 2s, 4s
                    Log::info('GroqClient: Esperando antes de reintentar', [
                        'wait_seconds' => $waitTime
                    ]);
                    sleep($waitTime);
                }
            }
        }

        // Si llegamos aquí, fallaron todos los reintentos
        Log::error('GroqClient: Todos los reintentos fallaron', [
            'max_retries' => $this->maxRetries,
            'last_error' => $lastException->getMessage()
        ]);

        throw $lastException;
    }

    /**
     * Extraer mensaje del asistente de la respuesta
     */
    public function extractAssistantMessage(array $response): ?array
    {
        return $response['choices'][0]['message'] ?? null;
    }

    /**
     * Verificar si la respuesta contiene tool calls
     */
    public function hasToolCalls(array $response): bool
    {
        $message = $this->extractAssistantMessage($response);
        return isset($message['tool_calls']) && !empty($message['tool_calls']);
    }

    /**
     * Extraer tool calls de la respuesta
     */
    public function extractToolCalls(array $response): array
    {
        $message = $this->extractAssistantMessage($response);
        return $message['tool_calls'] ?? [];
    }

    /**
     * Extraer contenido de texto de la respuesta
     */
    public function extractContent(array $response): ?string
    {
        $message = $this->extractAssistantMessage($response);
        return $message['content'] ?? null;
    }

    /**
     * Obtener información de uso (tokens)
     */
    public function getUsage(array $response): ?array
    {
        return $response['usage'] ?? null;
    }

    /**
     * Listar modelos disponibles
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get($this->baseUrl . '/models');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::error('GroqClient: Error al listar modelos', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('GroqClient: Excepción al listar modelos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Configurar timeout
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Configurar máximo de reintentos
     */
    public function setMaxRetries(int $retries): self
    {
        $this->maxRetries = $retries;
        return $this;
    }

    /**
     * Obtener modelo por defecto
     */
    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * Cambiar modelo por defecto
     */
    public function setDefaultModel(string $model): self
    {
        $this->defaultModel = $model;
        return $this;
    }
}
