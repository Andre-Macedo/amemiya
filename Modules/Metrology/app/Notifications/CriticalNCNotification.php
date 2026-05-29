<?php

namespace Modules\Metrology\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Metrology\Models\NonConformity;

class CriticalNCNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public NonConformity $nc) {}

    public function via($notifiable): array
    {
        // Só envia e-mail se for High ou Critical
        $channels = ['database'];
        if (in_array($this->nc->priority, ['high', 'critical'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $severity = strtoupper($this->nc->priority);

        return (new MailMessage)
            ->error()
            ->subject("🚨 SEVERITY {$severity}: Non-Conformity Opened")
            ->greeting('Hello Quality Auditor,')
            ->line('A new non-conformity has been recorded in the system with high severity.')
            ->line("Title: **{$this->nc->title}**")
            ->line("Item: **{$this->nc->item?->name}**")
            ->line("Opened by: **{$this->nc->opener?->name}**")
            ->action('Start Investigation (CAPA)', url("/dashboard/metrology/non-conformities/{$this->nc->id}"))
            ->line('Immediate action may be required to prevent quality deviation in current production.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'rnc_opened',
            'severity' => $this->nc->priority,
            'title' => 'New Non-Conformity Recorded',
            'message' => "RNC #{$this->nc->id} has been opened for {$this->nc->item?->name}.",
            'id' => $this->nc->id,
            'link' => "/dashboard/metrology/non-conformities/{$this->nc->id}",
        ];
    }
}
