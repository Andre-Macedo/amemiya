<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalibrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Calibration Details')
                    ->schema([
                        TextEntry::make('instrument.name')
                            ->label('Instrument'),
                        TextEntry::make('referenceStandards.name')
                            ->label('Reference Standards')
                            ->listWithLineBreaks(),
                        TextEntry::make('calibration_date')
                            ->date(),
                        TextEntry::make('type')
                            ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                        TextEntry::make('result')
                            ->formatStateUsing(fn($state) => ucfirst($state ?? '')),
                        TextEntry::make('deviation')
                            ->suffix(' mm'),
                        TextEntry::make('uncertainty')
                            ->suffix(' mm'),
//                        TextEntry::make('performedBy.name')
//                            ->label('Performed By'),
                        TextEntry::make('notes'),
                    ])
                    ->columns(2),

            ]);
    }
}
