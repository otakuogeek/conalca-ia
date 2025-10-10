<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\Goal;

class GoalStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Goal $goal) {}

    public function via($notifiable): array
    {
        return ['database', 'mail']; // quitar mail si no se usa
    }

    public function toMail($notifiable): MailMessage
    {
        $commercial = $this->goal->commercial->name;
        $target     = number_format($this->goal->target_amount, 2);
        $achieved   = number_format($this->goal->achieved_amount, 2);
        $stateText  = $this->goal->status === 'achieved' ? 'cumplida' : 'no cumplida';

        return (new MailMessage)
            ->subject("Meta {$stateText}")
            ->line("El comercial {$commercial} ha {$stateText} su meta.")
            ->line("Meta: $ {$target}")
            ->line("Logrado: $ {$achieved}")
            ->line('Fecha: '.now()->format('d/m/Y'));
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'commercial' => $this->goal->commercial->name,
            'target'     => $this->goal->target_amount,
            'achieved'   => $this->goal->achieved_amount,
            'status'     => $this->goal->status,
            'date'       => now()->toDateString(),
        ]);
    }
}
