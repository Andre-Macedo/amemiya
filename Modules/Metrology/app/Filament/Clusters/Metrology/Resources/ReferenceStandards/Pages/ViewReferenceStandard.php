<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Pages\RecallSimulatorPage;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Widgets\DriftChart;
use Modules\Metrology\Models\ReferenceStandard;

class ViewReferenceStandard extends ViewRecord
{
    use InteractsWithCluster;

    protected static string $resource = ReferenceStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simulate_recall')
                ->label('Simular Janela de Exposição (Recall)')
                ->icon('heroicon-m-backward')
                ->color('warning')
                ->url(fn (ReferenceStandard $record) => RecallSimulatorPage::getUrl(['standard_id' => $record->id])),
            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            DriftChart::class,
        ];
    }
}
