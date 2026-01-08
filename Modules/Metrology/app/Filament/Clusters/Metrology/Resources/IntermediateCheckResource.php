<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\RelationManagers;
use Modules\Metrology\Models\IntermediateCheck;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology;

class IntermediateCheckResource extends Resource
{
    protected static ?string $model = IntermediateCheck::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $modelLabel = 'Checagem Intermediária';
    protected static ?string $pluralModelLabel = 'Checagens Intermediárias';

    public static function form(Form|\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Section::make('Dados da Checagem')
                    ->schema([
                        Select::make('instrument_id')
                            ->relationship('instrument', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('reference_standard_id')
                            ->label('Padrão Utilizado (Opcional)')
                            ->relationship('referenceStandard', 'name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('check_date')
                            ->label('Data da Checagem')
                            ->default(now())
                            ->required(),
                        Select::make('result')
                            ->label('Resultado')
                            ->options([
                                'passed' => 'Aprovado',
                                'failed' => 'Reprovado',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('performed_by')
                            ->label('Realizado por')
                            ->relationship('performer', 'name')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])->columns(2),

                Section::make('Condições Ambientais')
                    ->schema([
                        TextInput::make('temperature')
                            ->label('Temperatura (°C)')
                            ->numeric(),
                        TextInput::make('humidity')
                            ->label('Umidade (%)')
                            ->numeric(),
                    ])->columns(2),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('notes')
                            ->label('')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('instrument.name')
                    ->label('Instrumento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('result')
                    ->label('Resultado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'passed' => 'Aprovado',
                        'failed' => 'Reprovado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('performer.name')
                    ->label('Responsável')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('result')
                    ->options([
                        'passed' => 'Aprovado',
                        'failed' => 'Reprovado',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIntermediateChecks::route('/'),
            'create' => Pages\CreateIntermediateCheck::route('/create'),
            'edit' => Pages\EditIntermediateCheck::route('/{record}/edit'),
        ];
    }
}
