<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use Exception;

class TestElevenLabsConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elevenlabs:test {--voice=JBFqnCBsd6RMkjVDRZzb}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test ElevenLabs configuration and API connectivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing ElevenLabs Configuration...');
        $this->newLine();

        // 1. Check API Key
        $this->line('1. Checking API Key configuration...');
        if (!env('ELEVENLABS_API_KEY')) {
            $this->error('❌ ELEVENLABS_API_KEY not found in .env file');
            $this->info('💡 Add your ElevenLabs API key to .env:');
            $this->info('   ELEVENLABS_API_KEY=your_api_key_here');
            return Command::FAILURE;
        }
        $this->info('✅ API Key configured');

        try {
            $elevenLabs = new ElevenLabsService();

            // 2. Test API connectivity with voices
            $this->line('2. Testing API connectivity...');
            $voices = $elevenLabs->getVoices();
            $this->info('✅ Successfully connected to ElevenLabs API');
            $this->info("📊 Found {$voices['voices']} voices available");

            // 3. Test Spanish voices
            $this->line('3. Checking Spanish voices...');
            $spanishVoices = $elevenLabs->getSpanishVoices();
            $this->info("✅ Found " . count($spanishVoices) . " recommended Spanish voices");

            // 4. Display voice being used
            $voiceId = $this->option('voice');
            $this->line("4. Testing voice: {$voiceId}...");

            // Find voice name
            $voiceName = 'Unknown';
            foreach ($spanishVoices as $voice) {
                if ($voice['voice_id'] === $voiceId) {
                    $voiceName = $voice['name'];
                    break;
                }
            }
            $this->info("🎤 Using voice: {$voiceName} ({$voiceId})");

            // 5. Test TTS with a short phrase
            $this->line('5. Testing text-to-speech generation...');
            $testText = "Hola, esta es una prueba de configuración de ElevenLabs.";
            
            $result = $elevenLabs->textToSpeech($testText, $voiceId);
            
            $this->info('✅ Text-to-speech generation successful');
            $this->info("📏 Audio size: " . number_format($result['size']) . " bytes");
            $this->info("🎵 Content type: {$result['content_type']}");

            // 6. Test file saving
            $this->line('6. Testing file saving...');
            $filename = 'test_config_' . time() . '.mp3';
            $filePath = $elevenLabs->saveAudioFile($result['audio_content'], $filename);
            $this->info("💾 Test file saved: storage/app/public/{$filePath}");

            $this->newLine();
            $this->info('🎉 All tests passed! ElevenLabs is configured correctly.');
            $this->info('🚀 Your IVR system is ready to use ElevenLabs for TTS.');

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->newLine();
            $this->warn('🔧 Troubleshooting tips:');
            $this->line('• Check your internet connection');
            $this->line('• Verify your ElevenLabs API key is valid');
            $this->line('• Ensure you have credits in your ElevenLabs account');
            $this->line('• Check ELEVENLABS_IMPLEMENTATION.md for more details');
            
            return Command::FAILURE;
        }
    }
}
