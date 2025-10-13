<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class InstrumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Instrument')
                    ->tabs([
                        Tabs\Tab::make('Details')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('serial_number')
                                    ->label('Serial Number'),
                                TextEntry::make('asset_tag')
                                    ->label('Asset Tag')
                                    ->default('-'),
                                TextEntry::make('instrumentType.name')
                                    ->label('Type'),
                                TextEntry::make('precision')
                                    ->formatStateUsing(fn($state) => ucfirst($state)),
                                TextEntry::make('location')
                                    ->default('-'),
                                TextEntry::make('acquisition_date')
                                    ->date(),
                                TextEntry::make('calibration_due')
                                    ->date()
                                    ->color(fn($record) => Carbon::parse($record->calibration_due)->isPast() ? 'danger' : null),
                                TextEntry::make('status')
                                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
//                        ImageEntry::make('image_path')
//                            ->height(200)
//                            ->default('-'),
                                TextEntry::make('notes')
                                    ->html()
                                    ->default('-'),
                            ])->columns(2),
                        Tabs\Tab::make('Última Calibração')
                            ->schema(function ($record) {
                                $calibration = $record->calibrations()->latest()->first();
                                if (!$calibration) {
                                    return [
                                        TextEntry::make('no_calibration')
                                            ->label('Last Calibration')
                                            ->default('No calibrations yet'),
                                    ];
                                }
                                return [
                                    TextEntry::make('calibration_date')
                                        ->label('Date')
                                        ->date()
                                        ->getStateUsing(fn() => $calibration->calibration_date),
                                    TextEntry::make('type')
                                        ->label('Type')
                                        ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                        ->getStateUsing(fn() => $calibration->type),
                                    TextEntry::make('result')
                                        ->label('Result')
                                        ->formatStateUsing(fn($state) => ucfirst($state ?? '-'))
                                        ->getStateUsing(fn() => $calibration->result),
                                    TextEntry::make('uncertainty')
                                        ->label('Uncertainty')
                                        ->suffix(' mm')
                                        ->default('-')
                                        ->getStateUsing(fn() => $calibration->uncertainty),
                                    TextEntry::make('performedBy.name')
                                        ->label('Performed By')
                                        ->default('-')
                                        ->getStateUsing(fn() => $calibration->performedBy ? $calibration->performedBy->name : '-'),
                                    RepeatableEntry::make('checklist.items')
                                        ->label('Checklist Items')
                                        ->schema([
                                            TextEntry::make('step'),
                                            TextEntry::make('question_type')
                                                ->formatStateUsing(fn($state) => ucfirst($state)),
                                            TextEntry::make('reference_standard_type')
                                                ->default('-'),
                                            IconEntry::make('completed')
                                                ->boolean(),
                                            TextEntry::make('readings')
                                                ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : '-')
                                                ->visible(fn($record) => $record->question_type === 'numeric'),
                                            TextEntry::make('uncertainty')
                                                ->suffix(' mm')
                                                ->default('-')
                                                ->visible(fn($record) => $record->question_type === 'numeric'),
                                            TextEntry::make('result')
                                                ->default('-')
                                                ->visible(fn($record) => $record->question_type !== 'text'),
                                            TextEntry::make('notes')
                                                ->default('-'),
                                        ])
                                        ->columns(2)
                                        ->visible(fn() => $calibration->type === 'internal')
                                        ->getStateUsing(fn() => $calibration->checklist ? $calibration->checklist->items->toArray() : []),
                                ];
                            })
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
