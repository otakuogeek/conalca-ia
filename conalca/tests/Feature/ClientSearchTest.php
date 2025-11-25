<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Client;

class ClientSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_search_with_one_character_and_prioritizes_starts_with()
    {
        // Arrange: crear clientes
        Client::factory()->create([
            'cliente' => 'Beta Cargo',
            'documento' => '900999001',
            'email' => 'beta@example.com',
            'telefono' => '3000000001',
            'ciudad' => 'Bogota',
        ]);

        $alpha = Client::factory()->create([
            'cliente' => 'Alpha Logistics SAS',
            'documento' => '800888001',
            'email' => 'alpha@example.com',
            'telefono' => '3000000002',
            'ciudad' => 'Bogota',
        ]);

        // Act: consulta con una sola letra y preferencia starts_with
        $response = $this->getJson('/api/clients/search?q=a&limit=10&starts_with=1');

        // Assert
        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['clients', 'total']);

        $payload = $response->json();
        $this->assertGreaterThanOrEqual(1, $payload['total']);

        $first = $payload['clients'][0] ?? null;
        $this->assertNotNull($first);

        // Verificar que el primero empiece por 'a' en el nombre del cliente
        $this->assertTrue(stripos($first['cliente'], 'a') === 0, 'El primer resultado debe empezar por "a"');
    }

    /** @test */
    public function it_returns_success_for_one_char_without_param()
    {
        Client::factory()->create(['cliente' => 'AALPE LOGISTICA SAS']);
        $response = $this->getJson('/api/clients/search?q=a');
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
