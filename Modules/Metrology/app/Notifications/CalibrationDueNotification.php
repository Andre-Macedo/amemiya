<?php

namespace Modules\Metrology\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CalibrationDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $instruments;

    protected $daysUntilDue;

    /**
     * Create a new notification instance.
     *
     * @param  Collection  $instruments
     */
    public function __construct($instruments, int $daysUntilDue)
    {
        $this->instruments = $instruments;
        $this->daysUntilDue = $daysUntilDue;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $count = $this->instruments->count();
        $timeframe = $this->daysUntilDue === 0 ? 'TODAY' : "in {$this->daysUntilDue} days";

        $mail = (new MailMessage)
            ->subject("⚠️ Calibration Alert: {$count} Instruments Due {$timeframe}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The following instruments are due for calibration {$timeframe}:");

        foreach ($this->instruments->take(5) as $instrument) {
            $mail->line("- **{$instrument->name}** (SN: {$instrument->serial_number})");
        }

        if ($count > 5) {
            $mail->line('...and '.($count - 5).' more.');
        }

        return $mail
            ->action('View All Instruments', url('/dashboard/metrology/instruments?status=due'))
            ->line('Please schedule these calibrations to maintain compliance.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Calibration Due Alert',
            'message' => "{$this->instruments->count()} instruments are due for calibration in {$this->daysUntilDue} days.",
            'count' => $this->instruments->count(),
            'days_until_due' => $this->daysUntilDue,
        ];
    }
}
