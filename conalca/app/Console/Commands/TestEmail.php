<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        try {
            $this->info('Intentando enviar email de prueba a: ' . $email);
            
            Mail::raw('Este es un email de prueba desde Conalca', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Prueba de Email - Conalca')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $this->info('Email enviado exitosamente!');
            Log::info('Email de prueba enviado exitosamente', ['email' => $email]);
            
        } catch (\Exception $e) {
            $this->error('Error al enviar email: ' . $e->getMessage());
            Log::error('Error al enviar email de prueba', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
