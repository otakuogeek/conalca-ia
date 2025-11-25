<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Verificación de credenciales ElevenLabs ===\n\n";

echo "Leyendo desde .env:\n";
echo "  ELEVENLABS_API_KEY: " . substr(env('ELEVENLABS_API_KEY'), 0, 20) . "...\n";
echo "  ELEVENLABS_AGENT_ID: " . env('ELEVENLABS_AGENT_ID') . "\n";
echo "  ELEVENLABS_AGENT_PHONE_NUMBER_ID: " . env('ELEVENLABS_AGENT_PHONE_NUMBER_ID') . "\n";
echo "  ELEVENLABS_AGENT_PHONE_NUMBER: " . env('ELEVENLABS_AGENT_PHONE_NUMBER') . "\n\n";

echo "Leyendo desde config:\n";
echo "  API Key: " . substr(config('services.elevenlabs.api_key'), 0, 20) . "...\n";
echo "  Agent ID: " . config('services.elevenlabs.agent_id') . "\n";
echo "  Agent Phone Number ID: " . config('services.elevenlabs.agent_phone_number_id') . "\n";
echo "  Agent Phone Number: " . config('services.elevenlabs.agent_phone_number') . "\n\n";

echo "Verificando ElevenLabsCallService:\n";
$service = app(\App\Services\ElevenLabsCallService::class);

// Usar reflection para acceder a propiedades privadas
$reflection = new ReflectionClass($service);

$apiKeyProp = $reflection->getProperty('apiKey');
$apiKeyProp->setAccessible(true);
echo "  API Key: " . substr($apiKeyProp->getValue($service), 0, 20) . "...\n";

$agentIdProp = $reflection->getProperty('agentId');
$agentIdProp->setAccessible(true);
echo "  Agent ID: " . $agentIdProp->getValue($service) . "\n";

$agentPhoneNumberIdProp = $reflection->getProperty('agentPhoneNumberId');
$agentPhoneNumberIdProp->setAccessible(true);
echo "  Agent Phone Number ID: " . $agentPhoneNumberIdProp->getValue($service) . "\n\n";

echo "✅ Credenciales correctas:\n";
echo "  - Agent ID: agent_7301k9w6ndqre6kr8je9k50fzz3v\n";
echo "  - Agent Phone Number ID: phnum_1701kae3xg4pevrrccxh34d6m8k2\n";
echo "  - Número de llamada: +576017564145\n\n";

$expectedAgentId = 'agent_7301k9w6ndqre6kr8je9k50fzz3v';
$expectedPhoneNumberId = 'phnum_1701kae3xg4pevrrccxh34d6m8k2';

if ($agentIdProp->getValue($service) === $expectedAgentId) {
    echo "✅ Agent ID correcto\n";
} else {
    echo "❌ Agent ID incorrecto!\n";
    echo "   Esperado: {$expectedAgentId}\n";
    echo "   Actual: " . $agentIdProp->getValue($service) . "\n";
}

if ($agentPhoneNumberIdProp->getValue($service) === $expectedPhoneNumberId) {
    echo "✅ Agent Phone Number ID correcto\n";
} else {
    echo "❌ Agent Phone Number ID incorrecto!\n";
    echo "   Esperado: {$expectedPhoneNumberId}\n";
    echo "   Actual: " . $agentPhoneNumberIdProp->getValue($service) . "\n";
}
