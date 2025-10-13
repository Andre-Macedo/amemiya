<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\Calibration;

class CalibrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Label')->tabs([
                Tabs\Tab::make('Calibration Details')
                    ->schema([
                        Section::make()->schema([
                            TextEntry::make('instrument.name'),
                            TextEntry::make('referenceStandards.name')->listWithLineBreaks(),
                            TextEntry::make('calibration_date')->date(),
                            TextEntry::make('type')->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                            TextEntry::make('result')->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'N/A')),
                            TextEntry::make('deviation')->suffix(' mm'),
                            TextEntry::make('uncertainty')->suffix(' mm'),
                            TextEntry::make('performedBy.name'),
                            TextEntry::make('notes'),
                        ])->columns(2),
                    ]),
                Tabs\Tab::make('Checklist')
                    ->schema([
                        RepeatableEntry::make('checklist.items')
                            ->schema([
                                TextEntry::make('step'),
                                TextEntry::make('question_type')->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                TextEntry::make('completed')->label('Result')->formatStateUsing(function (string $state, $record) {
                                    switch ($record->question_type) {
                                        case 'boolean':
                                            return $state ? 'Pass' : 'Fail';
                                        case 'numeric':
                                            return implode(', ', $record->readings);
                                        case 'text':
                                            return $record->notes;
                                        default:
                                            return 'N/A';
                                    }
                                }),
                            ])
                            ->columns(3)
                            ->getStateUsing(function (Calibration $record) {
                                return $record->checklist ? $record->checklist->items : [];
                            }),
                    ])
                    ->visible(fn ($record) => $record->type === 'internal' && $record->checklist),
            ])->columnSpanFull(),
        ]);
    }
}
