<?php

declare(strict_types=1);

namespace Modules\Metrology\Listeners;

use Modules\Metrology\Actions\ProcessCalibrationAction;
use Modules\Metrology\Events\CalibrationSaved;

class ProcessCalibrationListener
{
    /**
     * Create the event listener.
     */
    public function __construct(protected ProcessCalibrationAction $action)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CalibrationSaved $event): void
    {
        // Avoid infinite loops if the action saves the model again
        // Ideally, Action should use saveQuietly() which it already does.
        
        $this->action->execute($event->calibration);
    }
}
