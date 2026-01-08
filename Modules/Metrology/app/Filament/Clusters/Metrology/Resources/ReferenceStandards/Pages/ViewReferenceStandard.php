<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;

class ViewReferenceStandard extends ViewRecord
{
    use InteractsWithCluster;
    protected static string $resource = ReferenceStandardResource::class;

    protected function getHeaderActions(): array
    {

        return [
            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Widgets\DriftChart::class,
        ];
    }
}
