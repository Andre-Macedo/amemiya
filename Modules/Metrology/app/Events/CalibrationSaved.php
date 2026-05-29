<?php

declare(strict_types=1);

namespace Modules\Metrology\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Metrology\Models\Calibration;

/**
 * Evento disparado quando uma calibração é salva (criada ou atualizada).
 */
class CalibrationSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Calibration $calibration)
    {
        //
    }
}
