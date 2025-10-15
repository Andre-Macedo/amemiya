<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\ReferenceStandardTypeResource;

class ListReferenceStandardTypes extends ListRecords
{
    protected static string $resource = ReferenceStandardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
