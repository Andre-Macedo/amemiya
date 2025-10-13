<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CalibrationRelationManager extends RelationManager
{
    protected static string $relationship = 'calibrations';

    protected static ?string $title = "Historico de Calibrações";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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
                Select::make('performed_by_id')
                    ->relationship('performedBy', 'name')
                    ->required()
                    ->searchable(),
                Select::make('calibration_interval')
                    ->label('Intervalo até Próxima Calibração (meses)')
                    ->options([
                        6 => '6 meses',
                        12 => '12 meses',
                        18 => '18 meses',
                        24 => '24 meses',
                    ])
                    ->default(12)
                    ->required(),
                FileUpload::make('certificate_path')
                    ->directory('calibration-certificates')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required(fn ($get) => $get('type') === 'external_rbc')
                    ->nullable(fn ($get) => $get('type') !== 'external_rbc'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('calibration_date')
                    ->date(),
//                Tables\Columns\TextColumn::make('type')
//                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('result')
                    ->formatStateUsing(fn($state) => ucfirst($state ?? '-')),
                Tables\Columns\TextColumn::make('uncertainty')
                    ->suffix(' mm')
                    ->default('-'),
                Tables\Columns\TextColumn::make('performedBy.name')
                    ->label('Performed By')
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'internal' => 'Interna',
                        'external_rbc' => 'Externa RBC',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
