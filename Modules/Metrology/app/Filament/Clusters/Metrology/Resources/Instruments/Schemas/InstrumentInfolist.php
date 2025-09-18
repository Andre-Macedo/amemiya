<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class InstrumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Instrument Details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('serial_number')
                            ->label('Serial Number'),
                        TextEntry::make('type')
                            ->formatStateUsing(fn($state) => ucfirst($state)),
                        TextEntry::make('precision')
                            ->formatStateUsing(fn($state) => ucfirst($state)),
                        TextEntry::make('location'),
                        TextEntry::make('acquisition_date')
                            ->date(),
                        TextEntry::make('calibration_due')
                            ->date()
                            ->color(fn($record) => Carbon::parse($record->calibration_due)->isPast() ? 'danger' : null),
                        TextEntry::make('status')
                            ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                    ])
                    ->columns(2),

            ]);
    }
}
