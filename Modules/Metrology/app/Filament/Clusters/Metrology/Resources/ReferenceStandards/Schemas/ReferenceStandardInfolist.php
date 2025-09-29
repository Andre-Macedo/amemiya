<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ReferenceStandardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reference Standard Details')
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('serial_number')->label('Serial Number'),
                        TextEntry::make('type')->label('Type'),
                        TextEntry::make('calibration_date')->date()->label('Calibration Date'),
                        TextEntry::make('calibration_due')->date()->label('Calibration Due')->color(fn($record) => Carbon::parse($record->calibration_due)->isPast() ? 'danger' : null),
                        TextEntry::make('traceability')->label('Traceability'),
                        TextEntry::make('description')->label('Description'),
                    ])
                    ->columns(2),
            ]);
    }
}
