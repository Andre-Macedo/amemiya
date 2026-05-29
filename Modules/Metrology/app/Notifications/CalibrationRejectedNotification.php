<?php

namespace Modules\Metrology\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Metrology\Models\Calibration;

class CalibrationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Calibration $calibration) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $item = $this->calibration->calibratedItem;
        $name = $item ? $item->name : 'Unknown Instrument';
        $serial = $item ? $item->serial_number : 'N/A';

        return (new MailMessage)
            ->error()
            ->subject("🔥 CRITICAL: Calibration Rejected - {$name}")
            ->greeting('Attention Quality Manager,')
            ->line('An instrument has failed its calibration and requires immediate attention.')
            ->line("Instrument: **{$name}** (SN: {$serial})")
            ->line("Deviation: **{$this->calibration->deviation}**")
            ->line("Uncertainty: **{$this->calibration->uncertainty}**")
            ->action('Open Non-Conformity Report', url('/dashboard/metrology/non-conformities'))
            ->line('This instrument must be removed from production until a corrective action is implemented.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'critical_failure',
            'title' => 'Instrument Calibration Rejected',
            'message' => "{$this->calibration->calibratedItem?->name} failed calibration. Automatic RNC opened.",
            'id' => $this->calibration->id,
            'link' => '/dashboard/metrology/non-conformities',
        ];
    }
}
