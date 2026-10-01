<?php

declare(strict_types=1);

namespace Modules\Metrology\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Metrology\Models\Instrument;

class CalibrationDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<int, Instrument>  $instruments
     */
    public function __construct(
        protected Collection $instruments,
        protected int $daysUntilDue
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $count = $this->instruments->count();
        $timeframe = $this->daysUntilDue === 0 ? 'HOJE' : "em {$this->daysUntilDue} dias";

        $hasCritical = $this->instruments->contains(fn (Instrument $item): bool => $item->isCritical());
        $urgencyPrefix = $hasCritical ? '🚨 [URGENTE - ITENS CRÍTICOS] ' : '⚠️ ';

        $name = is_object($notifiable) && isset($notifiable->name) ? (string) $notifiable->name : 'Usuário';

        $mail = (new MailMessage)
            ->subject("{$urgencyPrefix}Alerta de Calibração: {$count} instrumento(s) com vencimento {$timeframe}")
            ->greeting("Olá {$name},")
            ->line("Os seguintes instrumentos estão com calibração vencendo {$timeframe}:");

        foreach ($this->instruments->take(5) as $instrument) {
            $criticalTag = $instrument->isCritical() ? " [CRÍTICO: {$instrument->criticality->getShortLabel()}]" : '';
            $mail->line("- **{$instrument->name}**{$criticalTag} (SN: {$instrument->serial_number})");
        }

        if ($count > 5) {
            $mail->line('...e mais '.($count - 5).' outro(s).');
        }

        return $mail
            ->action('View All Instruments', url('/dashboard/metrology/instruments?status=due'))
            ->line('Please schedule these calibrations to maintain compliance.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $hasCritical = $this->instruments->contains(fn (Instrument $item): bool => $item->isCritical());

        return [
            'title' => $hasCritical ? 'Alerta Crítico de Calibração' : 'Alerta de Calibração',
            'message' => "{$this->instruments->count()} instrumento(s) com calibração a vencer em {$this->daysUntilDue} dia(s).",
            'count' => $this->instruments->count(),
            'days_until_due' => $this->daysUntilDue,
            'has_critical' => $hasCritical,
        ];
    }
}
