<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\IoTGatewayResource;

class EditIoTGateway extends EditRecord
{
    protected static string $resource = IoTGatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
