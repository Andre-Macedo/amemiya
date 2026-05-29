<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTSensorData\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTSensorData\IoTSensorDataResource;

class ManageIoTSensorData extends ManageRecords
{
    protected static string $resource = IoTSensorDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
