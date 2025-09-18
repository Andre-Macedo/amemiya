<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;

class ListReferenceStandards extends ListRecords
{
    protected static string $resource = ReferenceStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
