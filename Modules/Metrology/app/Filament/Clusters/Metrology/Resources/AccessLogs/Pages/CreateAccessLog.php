<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\AccessLogResource;

class CreateAccessLog extends CreateRecord
{
    protected static string $resource = AccessLogResource::class;
}
