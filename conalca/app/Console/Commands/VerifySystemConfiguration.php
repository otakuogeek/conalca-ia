<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use phpseclib3\Net\SFTP;
use OpenAI;

class VerifySystemConfiguration extends Command
{
    protected $signature = 'system:verify';
    protected $description = 'Verifica que todas las configuraciones del sistema estén correctas';

    public function handle()
    {
        $this->info('🔍 Verificando configuración del sistema...');
        $this->newLine();

        $allGood = true;

        // 1. Verificar base de datos
        $allGood &= $this->checkDatabase();
        
        // 2. Verificar Twilio
        $allGood &= $this->checkTwilio();
        
        // 3. Verificar OpenAI
        $allGood &= $this->checkOpenAI();
        
        // 4. Verificar ElevenLabs
        $allGood &= $this->checkElevenLabs();
        
        $this->newLine();
        if ($allGood) {
            $this->info('✅ ¡Todas las configuraciones están correctas!');
            $this->info('🚀 El sistema está listo para usar.');
        } else {
            $this->error('❌ Algunas configuraciones necesitan atención.');
            $this->info('📖 Revisa CONFIGURATION_GUIDE.md para más detalles.');
        }

        return $allGood ? 0 : 1;
    }

    private function checkTwilio(): bool
    {
        $this->info('📞 Verificando Twilio...');
        
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $phone = config('services.twilio.phone_number');
        
        if (!$sid || !$token || !$phone) {
            $this->line('   ❌ Configuración de Twilio incompleta');
            $this->line('   📝 Configura TWILIO_SID, TWILIO_AUTH_TOKEN y TWILIO_PHONE_NUMBER');
            return false;
        }

        if (!str_starts_with($sid, 'AC')) {
            $this->line('   ❌ TWILIO_SID parece inválido (debe empezar con AC)');
            return false;
        }

        if (!str_starts_with($phone, '+')) {
            $this->line('   ❌ TWILIO_PHONE_NUMBER debe incluir código de país (ej: +1234567890)');
            return false;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://api.twilio.com/2010-04-01/Accounts/$sid.json", [
                'auth' => [$sid, $token],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $this->line('   ✅ Conexión a Twilio exitosa');
                $this->line('   ✅ Cuenta: ' . ($data['friendly_name'] ?? 'N/A'));
                $this->line('   ✅ Teléfono configurado: ' . $phone);
                return true;
            }
        } catch (\Exception $e) {
            $this->line('   ❌ Error de Twilio: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    private function checkDatabase(): bool
    {
        $this->info('📊 Verificando conexión a base de datos...');
        
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->line("   ✅ Conectado a: {$dbName}");
            
            // Verificar tablas importantes
            $tables = ['call_driver_decisions'];
            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $this->line("   ✅ Tabla '{$table}' existe");
                } else {
                    $this->line("   ⚠️ Tabla '{$table}' no encontrada - ejecuta: php artisan migrate");
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->line("   ❌ Error de conexión: " . $e->getMessage());
            return false;
        }
    }

    private function checkOpenAI(): bool
    {
        $this->info('🧠 Verificando OpenAI...');
        
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            $this->line('   ❌ OPENAI_API_KEY no configurada');
            return false;
        }

        if (!str_starts_with($apiKey, 'sk-')) {
            $this->line('   ❌ OPENAI_API_KEY parece inválida (debe empezar con sk-)');
            return false;
        }

        try {
            $client = OpenAI::client($apiKey);
            $response = $client->models()->list();
            $this->line('   ✅ Conexión a OpenAI exitosa');
            $this->line('   ✅ Modelos disponibles: ' . count($response->data));
            return true;
        } catch (\Exception $e) {
            $this->line('   ❌ Error de OpenAI: ' . $e->getMessage());
            return false;
        }
    }

    private function checkElevenLabs(): bool
    {
        $this->info('🎤 Verificando ElevenLabs...');
        
        $apiKey = config('services.elevenlabs.api_key');
        if (!$apiKey) {
            $this->line('   ❌ ELEVENLABS_API_KEY no configurada');
            return false;
        }

        if ($apiKey === 'your_elevenlabs_api_key_here') {
            $this->line('   ❌ ELEVENLABS_API_KEY aún tiene el valor por defecto');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'xi-api-key' => $apiKey
            ])->get('https://api.elevenlabs.io/v1/voices');

            if ($response->successful()) {
                $voices = $response->json();
                $voiceCount = count($voices['voices'] ?? []);
                $this->line('   ✅ Conexión a ElevenLabs exitosa');
                $this->line("   ✅ Voces disponibles: {$voiceCount}");
                
                // Verificar voz configurada
                $defaultVoiceId = env('ELEVENLABS_DEFAULT_VOICE_ID');
                $voiceFound = collect($voices['voices'])->contains('voice_id', $defaultVoiceId);
                if ($voiceFound) {
                    $this->line("   ✅ Voz por defecto '{$defaultVoiceId}' encontrada");
                } else {
                    $this->line("   ⚠️ Voz por defecto '{$defaultVoiceId}' no encontrada");
                }
                
                return true;
            } else {
                $this->line('   ❌ Error de ElevenLabs: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            $this->line('   ❌ Error de ElevenLabs: ' . $e->getMessage());
            return false;
        }
    }

}
