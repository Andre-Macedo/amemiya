<?php

namespace Modules\System\Filament\Clusters\System\Resources;

use App\Models\Plan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\System\Filament\Clusters\System\Resources\PlanResource\Pages;
use Modules\System\Filament\Clusters\System\SystemCluster;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Plano de Assinatura';

    protected static ?string $pluralModelLabel = 'Planos de Assinatura';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Detalhes do Plano')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Plano')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identificador (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('price')
                            ->label('Preço Mensal')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Limites do Plano')
                    ->description('Defina as restrições para este nível de assinatura.')
                    ->schema([
                        Forms\Components\TextInput::make('max_instruments')
                            ->label('Limite de Instrumentos')
                            ->numeric()
                            ->default(50)
                            ->helperText('0 para ilimitado'),

                        Forms\Components\TextInput::make('max_users')
                            ->label('Limite de Usuários')
                            ->numeric()
                            ->default(5)
                            ->helperText('0 para ilimitado'),

                        Forms\Components\TextInput::make('max_storage_mb')
                            ->label('Armazenamento (MB)')
                            ->numeric()
                            ->default(1024)
                            ->suffix('MB'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_instruments')
                    ->label('Inst.')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('max_users')
                    ->label('Usuários')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Assinantes')
                    ->counts('tenants')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Apenas Ativos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
