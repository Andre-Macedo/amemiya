<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\IoTNodeResource;

class CreateIoTNode extends CreateRecord
{
    protected static string $resource = IoTNodeResource::class;
}
