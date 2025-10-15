<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\ReferenceStandardTypeResource;

class ViewReferenceStandardType extends ViewRecord
{
    protected static string $resource = ReferenceStandardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
