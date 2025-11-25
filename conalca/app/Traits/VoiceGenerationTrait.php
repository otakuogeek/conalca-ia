<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait VoiceGenerationTrait
{
    /**
     * Genera un archivo de audio usando ElevenLabs TTS
     *
     * @param string $text Texto a convertir en voz
     * @param string $remoteFileName Nombre del archivo remoto
     * @return string Ruta del archivo WAV generado
     * @throws \Exception Si hay error en la generación
     */
    public function generateVoicePrompt($text, $remoteFileName)
    {
        set_time_limit(120);

        try {
            $client = new \GuzzleHttp\Client();
            $apiKey = config('elevenlabs.api_key');
            $voiceId = config('elevenlabs.default_voice_id');

            Log::info("Generando audio ElevenLabs", [
                'text_length' => strlen($text),
                'remote_file' => $remoteFileName,
                'voice_id' => $voiceId
            ]);

            // Validar entrada
            if (empty(trim($text))) {
                throw new \Exception('El texto no puede estar vacío');
            }

            if (strlen($text) > 2500) {
                throw new \Exception('El texto es demasiado largo (máximo 2500 caracteres)');
            }

            // Send request to Eleven Labs API
            $response = $client->post("https://api.elevenlabs.io/v1/text-to-speech/$voiceId", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'xi-api-key' => $apiKey
                ],
                'json' => [
                    'text' => $text,
                    'voice_settings' => [
                        'stability' => 1,
                        'similarity_boost' => 1
                    ]
                ]
            ]);

            // Guardar el archivo MP3 localmente
            $localMp3File = storage_path("app/public/$remoteFileName.mp3");
            file_put_contents($localMp3File, $response->getBody());

            Log::info("Audio MP3 guardado localmente", ['file' => $localMp3File]);

            // Transferir a servidor Asterisk via SFTP
            $sftp = new \phpseclib3\Net\SFTP(env('SFTP_HOST'));

            if (!$sftp->login(env('SFTP_USER'), env('SFTP_PASSWORD'))) {
                throw new \Exception('Error de conexión SFTP');
            }

            $remoteMp3Path = "/var/lib/asterisk/sounds/$remoteFileName.mp3";
            $remoteWavPath = "/var/lib/asterisk/sounds/$remoteFileName.wav";

            $sftp->put($remoteMp3Path, $localMp3File, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
            Log::info("Archivo transferido a Asterisk", ['remote_path' => $remoteMp3Path]);

            // Eliminar archivo local para ahorrar espacio
            unlink($localMp3File);

            // Convertir MP3 a WAV usando Sox en el servidor Asterisk
            $soxCommand = "sox $remoteMp3Path -r 8000 -c 1 -e signed-integer $remoteWavPath";

            $ssh = new \phpseclib3\Net\SSH2(env('SSH_HOST'));
            if (!$ssh->login(env('SSH_USER'), env('SSH_PASSWORD'))) {
                throw new \Exception('Error de conexión SSH');
            }

            $ssh->exec($soxCommand);
            Log::info("Audio convertido a WAV", ['wav_path' => $remoteWavPath]);

            // Cerrar conexiones
            $ssh->disconnect();
            $sftp->disconnect();

            return $remoteWavPath;

        } catch (\Exception $e) {
            Log::error("Error generando audio", [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100) . '...',
                'file' => $remoteFileName
            ]);
            throw $e;
        }
    }
}
