<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\IoTGatewayResource;

class CreateIoTGateway extends CreateRecord
{
    protected static string $resource = IoTGatewayResource::class;
}
