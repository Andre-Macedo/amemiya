<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\IoTNodeResource;

class EditIoTNode extends EditRecord
{
    protected static string $resource = IoTNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
