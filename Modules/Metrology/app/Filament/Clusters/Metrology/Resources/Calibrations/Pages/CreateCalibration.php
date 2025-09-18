<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;

class CreateCalibration extends CreateRecord
{
    protected static string $resource = CalibrationResource::class;
}
