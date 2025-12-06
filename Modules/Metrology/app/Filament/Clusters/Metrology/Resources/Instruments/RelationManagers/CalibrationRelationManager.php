<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Metrology\Models\Calibration;

class CalibrationRelationManager extends RelationManager
{
    protected static string $relationship = 'calibrations';

    protected static ?string $title = "Historico de Calibrações";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('calibration_date')
                        ->label('Data')
                        ->required(),

                    Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'internal' => 'Interna',
                            'external_rbc' => 'Externa',
                        ])
                        ->required(),

                    Select::make('result')
                        ->label('Resultado')
                        ->options([
                            'approved' => 'Aprovado',
                            'rejected' => 'Rejeitado',
                            'approved_with_restrictions' => 'Aprovado c/ Restrições',
                        ])
                        ->required(),

                    Select::make('provider_id')
                        ->label('Fornecedor')
                        ->relationship('provider', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('type') === 'external_rbc'),
                ]),

                FileUpload::make('certificate_path')
                    ->label('Certificado (PDF)')
                    ->directory('calibration-certificates')
                    ->acceptedFileTypes(['application/pdf'])
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
            ]);

    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('calibration_date')
            ->defaultSort('calibration_date', 'desc')
            ->columns([
                TextColumn::make('calibration_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Interna',
                        'external_rbc' => 'Externa',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'internal' => 'info',
                        'external_rbc' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('executor') // Nome virtual
                ->label('Executado Por / Fornecedor')
                    ->getStateUsing(function (Calibration $record) {
                        // Lógica: Se Interna -> Técnico. Se Externa -> Fornecedor.
                        if ($record->type === 'internal') {
                            return $record->performedBy?->name ?? 'Técnico Interno';
                        }
                        return $record->provider?->name ?? 'Fornecedor Externo';
                    })
                    ->icon(fn (Calibration $record) =>
                    $record->type === 'internal' ? 'heroicon-o-user' : 'heroicon-o-building-office'
                    )
                    ->description(fn (Calibration $record) =>
                    $record->type === 'internal' ? 'Laboratório Interno' : 'Serviço Terceirizado'
                    ),


                TextColumn::make('result')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprovado',
                        'rejected' => 'Reprovado',
                        'approved_with_restrictions' => 'Com Restrições',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'approved_with_restrictions' => 'warning',
                        default => 'gray',
                    }),

            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'internal' => 'Interna',
                        'external_rbc' => 'Externa',
                    ]),
                SelectFilter::make('result')
                    ->label('Resultado')
                    ->options([
                        'approved' => 'Aprovado',
                        'rejected' => 'Reprovado',
                    ]),
            ])
            ->headerActions([
//                CreateAction::make()->label('Nova Calibração'),
            ])
            ->actions([
                // AÇÃO DE CERTIFICADO (A mesma da tabela principal)
               Action::make('certificate')
                    ->label('Certificado')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (Calibration $record) => route('calibration.certificate.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Calibration $record) =>
                        ($record->type === 'internal' && $record->result === 'approved') ||
                        ($record->type === 'external_rbc' && $record->certificate_path)
                    ),

                ViewAction::make(),
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
