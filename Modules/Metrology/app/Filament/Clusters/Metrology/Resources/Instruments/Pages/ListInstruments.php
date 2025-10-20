<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages;

use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder; // <-- Apenas este Builder é necessário
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\InstrumentResource;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Widgets\InstrumentStatsWidget;
use Modules\Metrology\Models\Instrument;

class ListInstruments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = InstrumentResource::class;


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InstrumentStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos os Instrumentos'),
            'active' => Tab::make('Ativos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'expired' => Tab::make('Vencidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired'))
                ->badge(Instrument::where('status', 'expired')->count())
                ->badgeColor('danger'),
            'in_calibration' => Tab::make('Em Calibração')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_calibration')),
        ];
    }
}
