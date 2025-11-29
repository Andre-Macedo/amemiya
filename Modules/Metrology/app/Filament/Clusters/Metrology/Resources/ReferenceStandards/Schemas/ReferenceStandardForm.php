<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas;

use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ReferenceStandardForm
{
    /**
     * @throws Exception
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('serial_number')
                    ->label('Número de Série')
                    ->required(fn (Get $get) => $get('parent_id') === null)
                    ->unique(ignoreRecord: true),
                TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('calibration_date')
                    ->nullable(),
                DatePicker::make('calibration_due')
                    ->nullable(),
                TextInput::make('traceability')
                    ->nullable()
                    ->maxLength(255),
                FileUpload::make('certificate_path')
                    ->directory('reference-certificates')
                    ->acceptedFileTypes(['application/pdf'])
                    ->nullable(),
                Textarea::make('description')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
