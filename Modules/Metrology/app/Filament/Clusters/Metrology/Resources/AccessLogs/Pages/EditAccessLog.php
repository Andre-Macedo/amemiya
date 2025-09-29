<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\AccessLogResource;

class EditAccessLog extends EditRecord
{
    protected static string $resource = AccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
