<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('instrument_id')
                    ->relationship('instrument', 'name')
                    ->required()
                    ->searchable(),
                Select::make('reference_standards')
                    ->relationship('referenceStandards', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
                DatePicker::make('calibration_date')
                    ->required(),
                Select::make('type')
                    ->options([
                        'internal' => 'Interna',
                        'external_rbc' => 'Externa RBC',
                    ])
                    ->default('internal')
                    ->required(),
                Select::make('result')
                    ->options([
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ])
                    ->nullable(),
                TextInput::make('deviation')
                    ->numeric()
                    ->suffix('mm')
                    ->nullable(),
                TextInput::make('uncertainty')
                    ->numeric()
                    ->suffix('mm')
                    ->nullable(),
                Textarea::make('notes')
                    ->nullable()
                    ->columnSpanFull(),
                FileUpload::make('certificate_path')
                    ->directory('calibration-certificates')
                    ->acceptedFileTypes(['application/pdf'])
                    ->nullable(),
//                Select::make('performed_by')
//                    ->relationship('performedBy')
//                    ->required()
//                    ->searchable(),

            ]);
    }
}
