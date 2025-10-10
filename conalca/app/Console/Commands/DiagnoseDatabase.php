<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use PDO;

class DiagnoseDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:diagnose {--fix : Attempt to fix common issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose database connection issues and provide solutions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnóstico de Conexión a Base de Datos');
        $this->line('=====================================');

        // Test basic connection
        $this->testBasicConnection();

        // Test database configuration
        $this->testDatabaseConfig();

        // Test network connectivity
        $this->testNetworkConnectivity();

        // Test database permissions
        $this->testDatabasePermissions();

        // Provide recommendations
        $this->provideRecommendations();

        if ($this->option('fix')) {
            $this->attemptFixes();
        }
    }

    private function testBasicConnection()
    {
        $this->line("\n📡 Probando conexión básica...");

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $end = microtime(true);

            $this->info("✅ Conexión exitosa ({$this->formatTime($end - $start)})");
        } catch (\Exception $e) {
            $this->error("❌ Error de conexión: " . $e->getMessage());

            if (str_contains($e->getMessage(), 'MySQL server has gone away')) {
                $this->warn("💡 Este error indica que la instancia RDS puede estar detenida o inaccesible");
            }
        }
    }

    private function testDatabaseConfig()
    {
        $this->line("\n⚙️  Verificando configuración...");

        $config = config('database.connections.mysql');

        $this->line("Host: {$config['host']}");
        $this->line("Port: {$config['port']}");
        $this->line("Database: {$config['database']}");
        $this->line("Username: {$config['username']}");
        $this->line("Charset: {$config['charset']}");

        // Check if host is reachable
        $host = $config['host'];
        $port = $config['port'];

        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$connection) {
            $this->error("❌ No se puede conectar al host {$host}:{$port} - {$errstr}");
        } else {
            $this->info("✅ Host accesible");
            fclose($connection);
        }
    }

    private function testNetworkConnectivity()
    {
        $this->line("\n🌐 Probando conectividad de red...");

        $host = config('database.connections.mysql.host');

        // Ping test
        $ping = shell_exec("ping -c 3 -W 2 {$host} 2>&1");
        if (str_contains($ping, '100% packet loss')) {
            $this->error("❌ No hay conectividad de red al host {$host}");
        } else {
            $this->info("✅ Conectividad de red OK");
        }
    }

    private function testDatabasePermissions()
    {
        $this->line("\n🔐 Probando permisos de base de datos...");

        try {
            $results = DB::select('SELECT 1 as test');
            $this->info("✅ Permisos de lectura OK");

            // Test write permissions
            DB::select('SELECT 1 as test_write');
            $this->info("✅ Permisos de escritura OK");

        } catch (\Exception $e) {
            $this->error("❌ Error de permisos: " . $e->getMessage());
        }
    }

    private function provideRecommendations()
    {
        $this->line("\n💡 Recomendaciones:");
        $this->line("==================");

        $this->line("1. Verificar que la instancia RDS esté ejecutándose:");
        $this->line("   - Ir a AWS Console > RDS > Instances");
        $this->line("   - Verificar estado de 'ai-transport'");

        $this->line("\n2. Verificar Security Groups:");
        $this->line("   - Asegurarse de que el puerto 3306 esté abierto");
        $this->line("   - Verificar IP de origen permitida");

        $this->line("\n3. Verificar configuración de red:");
        $this->line("   - Confirmar que la instancia no esté en una VPC privada");
        $this->line("   - Verificar DNS resolution");

        $this->line("\n4. Para troubleshooting avanzado:");
        $this->line("   - Ejecutar: telnet ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com 3306");
        $this->line("   - Verificar logs de Laravel: tail -f storage/logs/laravel.log");
    }

    private function attemptFixes()
    {
        $this->line("\n🔧 Intentando correcciones automáticas...");

        // Clear database cache
        $this->line("Limpiando caché de base de datos...");
        Cache::store('database')->clear();

        // Clear config cache
        $this->call('config:clear');
        $this->call('cache:clear');

        $this->info("✅ Caché limpiado");

        // Test connection again
        $this->line("\nRe-probando conexión...");
        $this->testBasicConnection();
    }

    private function formatTime($seconds)
    {
        return round($seconds * 1000, 2) . 'ms';
    }
}
