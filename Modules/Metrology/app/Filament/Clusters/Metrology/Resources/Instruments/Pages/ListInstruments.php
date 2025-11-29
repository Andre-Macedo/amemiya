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
            'active' => Tab::make('Em Uso')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),

            'action_needed' => Tab::make('Atenção / Parados')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['expired', 'rejected']))
                ->badge(Instrument::whereIn('status', ['expired', 'rejected'])->count())
                ->badgeColor('danger'),

            'processing' => Tab::make('Em Processo Ext.')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['in_calibration', 'maintenance']))
                ->badge(Instrument::whereIn('status', ['in_calibration', 'maintenance'])->count())
                ->badgeColor('warning'),

            'scrapped' => Tab::make('Baixados / Sucata')
                ->icon('heroicon-m-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['scrapped', 'lost'])),
        ];
    }
}
