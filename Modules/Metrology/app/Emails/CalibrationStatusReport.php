<?php

namespace Modules\Metrology\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalibrationStatusReport extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Collection $expiredInstruments,
        public Collection $dueSoonInstruments
    ) {

    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->subject('Relatório Diário de Metrologia - ' . now()->format('d/m/Y'))
            ->markdown('metrology::mail.calibration-status-report');
    }
}
