<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\AccessLogResource;

class ViewAccessLog extends ViewRecord
{
    protected static string $resource = AccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
