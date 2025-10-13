<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\Instrument;

class InstrumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Instrument')
                    ->tabs([
                        Tabs\Tab::make('Details')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('serial_number')
                                    ->required()
                                    ->unique(Instrument::class, 'serial_number', ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('stock_number')
                                    ->unique(Instrument::class, 'stock_number', ignoreRecord: true)
                                    ->maxLength(255)
                                    ->nullable(),
                                        Select::make('instrument_type_id')
                                            ->relationship('instrumentType', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(),
                                            ]),
                                Select::make('precision')
                                    ->options([
                                        'centesimal' => 'Centesimal',
                                        'milesimal' => 'Milesimal',
                                    ])
                                    ->required(),
                                TextInput::make('location')
                                    ->maxLength(255)
                                    ->nullable(),
                                DatePicker::make('acquisition_date')
                                    ->required(),
                                DatePicker::make('calibration_due')
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Ativo',
                                        'in_calibration' => 'Em Calibração',
                                        'expired' => 'Vencido',
                                    ])
                                    ->default('active')
                                    ->required(),
                                FileUpload::make('image_path')
                                    ->directory('instrument_images')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                    ->imagePreviewHeight('250')
                                    ->nullable(),
                                RichEditor::make('notes')
                                    ->nullable()
                                    ->columns(2),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('Last Calibration')
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
                                        ->state($calibration->calibration_date),
                                    TextEntry::make('type')
                                        ->label('Type')
                                        ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                        ->state($calibration->type),
                                    TextEntry::make('result')
                                        ->label('Result')
                                        ->formatStateUsing(fn($state) => ucfirst($state ?? '-'))
                                        ->state($calibration->result),
                                    TextEntry::make('uncertainty')
                                        ->label('Uncertainty')
                                        ->suffix(' mm')
                                        ->default('-')
                                        ->state($calibration->uncertainty),
                                    TextEntry::make('performedBy.name')
                                        ->label('Performed By')
                                        ->default('-')
                                        ->state($calibration->performedBy ? $calibration->performedBy->name : '-'),
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
                                        ->visible(fn($record) => $calibration->type === 'internal')
                                        ->state($calibration->checklist ? $calibration->checklist->items->toArray() : []),
                                ];
                            })
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
