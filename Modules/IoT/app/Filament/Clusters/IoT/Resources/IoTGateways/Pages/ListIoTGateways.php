<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\IoTGatewayResource;

class ListIoTGateways extends ListRecords
{
    protected static string $resource = IoTGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
