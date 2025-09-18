<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;

class EditReferenceStandard extends EditRecord
{
    protected static string $resource = ReferenceStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
