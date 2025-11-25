<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Llamada;

class Conversation extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'call_sid',
        'caller_number',
        'caller_city',
        'caller_country',
        'direction',
        'status',
        'context',
        'last_user_input',
        'last_bot_response',
        'summary',
        'topic',
        'sentiment_score',
        'sentiment_label',
        'turn_count',
        'duration_seconds',
        'comprehension_score',
        'goal_achieved',
        'completion_reason',
        'metadata',
        'voice_id',
        'audio_generation_time_ms',
        'total_audio_size_bytes',
        'started_at',
        'completed_at',
        'last_interaction_at',
    ];
    
    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'sentiment_score' => 'float',
        'comprehension_score' => 'float',
        'goal_achieved' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_interaction_at' => 'datetime',
    ];
    
    protected $dates = [
        'started_at',
        'completed_at',
        'last_interaction_at',
    ];
    
    // Relaciones
    
    /**
     * Relación con Llamada (ElevenLabs) si existe
     */
    public function llamada()
    {
        return $this->belongsTo(Llamada::class, 'call_sid', 'call_sid');
    }
    
    // Mutators y Accessors
    
    /**
     * Inicializar contexto por defecto
     */
    public function getContextAttribute($value)
    {
        $context = json_decode($value, true) ?? [];
        
        // Asegurar estructura mínima
        if (!isset($context['system_message'])) {
            $context['system_message'] = $this->getDefaultSystemMessage();
        }
        
        if (!isset($context['history'])) {
            $context['history'] = [];
        }
        
        return $context;
    }
    
    /**
     * Obtener el historial de conversación limpio
     */
    public function getHistoryAttribute()
    {
        $context = $this->context;
        return $context['history'] ?? [];
    }
    
    /**
     * Obtener duración formateada
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }
        
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    /**
     * Obtener sentiment como texto legible
     */
    public function getSentimentTextAttribute()
    {
        $labels = [
            'very_negative' => 'Muy Negativo',
            'negative' => 'Negativo',
            'neutral' => 'Neutral',
            'positive' => 'Positivo',
            'very_positive' => 'Muy Positivo',
        ];
        
        return $labels[$this->sentiment_label] ?? 'No analizado';
    }
    
    /**
     * Obtener color del sentiment para UI
     */
    public function getSentimentColorAttribute()
    {
        $colors = [
            'very_negative' => 'red-600',
            'negative' => 'red-400',
            'neutral' => 'gray-400',
            'positive' => 'green-400',
            'very_positive' => 'green-600',
        ];
        
        return $colors[$this->sentiment_label] ?? 'gray-400';
    }
    
    // Métodos de manipulación de conversación
    
    /**
     * Añadir mensaje al historial
     */
    public function addToHistory($role, $content, $metadata = [])
    {
        try {
            $context = $this->context;
            
            $message = [
                'role' => $role,
                'content' => $content,
                'timestamp' => now()->toISOString(),
                'metadata' => $metadata
            ];
            
            $context['history'][] = $message;
            
            // Actualizar campos relevantes
            $updates = [
                'context' => $context,
                'turn_count' => count($context['history']),
                'last_interaction_at' => now()
            ];
            
            if ($role === 'user') {
                $updates['last_user_input'] = $content;
            } elseif ($role === 'assistant') {
                $updates['last_bot_response'] = $content;
            }
            
            $this->update($updates);
            
            Log::info('Message added to conversation', [
                'conversation_id' => $this->id,
                'role' => $role,
                'content_length' => strlen($content),
                'turn_count' => $this->turn_count
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error adding message to conversation', [
                'conversation_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Marcar conversación como completada
     */
    public function markCompleted($reason = 'natural_end', $goalAchieved = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_reason' => $reason,
            'goal_achieved' => $goalAchieved,
            'duration_seconds' => $this->calculateDuration()
        ]);
        
        Log::info('Conversation marked as completed', [
            'conversation_id' => $this->id,
            'reason' => $reason,
            'duration' => $this->duration_seconds,
            'turns' => $this->turn_count
        ]);
    }
    
    /**
     * Calcular duración de la conversación
     */
    protected function calculateDuration()
    {
        if (!$this->started_at) {
            return null;
        }
        
        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }
    
    /**
     * Obtener transcripción completa
     */
    public function getTranscript($includeMetadata = false)
    {
        $transcript = "";
        $history = $this->history;
        
        foreach ($history as $message) {
            $role = $message['role'] === 'user' ? 'Cliente' : 'Andrea';
            $content = $message['content'];
            $timestamp = isset($message['timestamp']) 
                ? Carbon::parse($message['timestamp'])->format('H:i:s') 
                : '';
            
            if ($includeMetadata && $timestamp) {
                $transcript .= "[{$timestamp}] ";
            }
            
            $transcript .= "{$role}: {$content}\n\n";
        }
        
        return trim($transcript);
    }
    
    /**
     * Obtener contexto para OpenAI
     */
    public function getOpenAIContext()
    {
        $context = $this->context;
        
        // Preparar mensajes para OpenAI
        $messages = [
            ['role' => 'system', 'content' => $context['system_message']]
        ];
        
        foreach ($context['history'] as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        return $messages;
    }
    
    /**
     * Analizar sentiment del último intercambio
     */
    public function analyzeSentiment($text = null)
    {
        $textToAnalyze = $text ?? $this->last_user_input;
        
        if (!$textToAnalyze) {
            return null;
        }
        
        // Análisis básico por palabras clave (mejorar con OpenAI después)
        $positiveWords = ['gracias', 'excelente', 'perfecto', 'bueno', 'si', 'ok', 'bien'];
        $negativeWords = ['problema', 'mal', 'error', 'no', 'terrible', 'horrible', 'no funciona'];
        
        $text = strtolower($textToAnalyze);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) {
                $negativeCount++;
            }
        }
        
        // Calcular score simple
        $score = ($positiveCount - $negativeCount) / max(1, $positiveCount + $negativeCount);
        
        // Determinar label
        $label = 'neutral';
        if ($score > 0.5) $label = 'positive';
        elseif ($score > 0.8) $label = 'very_positive';
        elseif ($score < -0.5) $label = 'negative';
        elseif ($score < -0.8) $label = 'very_negative';
        
        $this->update([
            'sentiment_score' => $score,
            'sentiment_label' => $label
        ]);
        
        return ['score' => $score, 'label' => $label];
    }
    
    /**
     * Detectar tema/intención de la conversación
     */
    public function detectTopic()
    {
        $transcript = strtolower($this->getTranscript());
        
        // Patrones para detectar temas
        $topics = [
            'cotización' => ['cotizar', 'cotización', 'precio', 'costo', 'tarifa', 'valor'],
            'seguimiento' => ['seguimiento', 'rastrea', 'dónde está', 'ubicación', 'tracking', 'guía'],
            'servicios' => ['servicios', 'qué ofrecen', 'especialidades', 'tipos de transporte'],
            'queja' => ['queja', 'reclamo', 'problema', 'mal servicio', 'insatisfecho'],
            'información' => ['información', 'horarios', 'teléfono', 'dirección', 'contacto'],
        ];
        
        $scores = [];
        foreach ($topics as $topic => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($transcript, $keyword) !== false) {
                    $score++;
                }
            }
            $scores[$topic] = $score;
        }
        
        // Obtener el tema con mayor puntuación
        $detectedTopic = array_keys($scores, max($scores))[0];
        
        if (max($scores) > 0) {
            $this->update(['topic' => $detectedTopic]);
            return $detectedTopic;
        }
        
        return null;
    }
    
    /**
     * Obtener mensaje del sistema por defecto
     */
    protected function getDefaultSystemMessage()
    {
        return "Eres Andrea, asistente virtual de CONALCA, una empresa líder en transporte y logística en Colombia. 
        Tu objetivo es ayudar a los clientes con sus consultas sobre cotizaciones, seguimiento de cargas y servicios de transporte.
        Mantén tus respuestas concisas pero completas (máximo 3-4 oraciones por respuesta) ya que estás en una llamada telefónica.
        Habla en un tono amable y profesional. No inventes información sobre precios específicos.
        Si no conoces algún dato, ofrece tomar los datos del cliente para que un asesor se comunique después.
        Para cotizaciones, pregunta: origen, destino, tipo de carga, peso y dimensiones aproximadas.";
    }
    
    // Scopes para consultas
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }
    
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }
    
    public function scopeByTopic($query, $topic)
    {
        return $query->where('topic', $topic);
    }
    
    public function scopeWithPositiveSentiment($query)
    {
        return $query->whereIn('sentiment_label', ['positive', 'very_positive']);
    }
    
    public function scopeWithNegativeSentiment($query)
    {
        return $query->whereIn('sentiment_label', ['negative', 'very_negative']);
    }
}