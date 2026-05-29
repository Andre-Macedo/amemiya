<?php

namespace Modules\System\Filament\Clusters\System\Resources;

use App\Actions\ImportMasterDataAction;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\System\Filament\Clusters\System\Resources\TenantResource\Pages;
use Modules\System\Filament\Clusters\System\Resources\TenantResource\RelationManagers;
use Modules\System\Filament\Clusters\System\SystemCluster;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Cliente (Tenant)';

    protected static ?string $pluralModelLabel = 'Clientes (Tenants)';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Tabs::make('Detalhes do Cliente')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Identificação')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome da Empresa')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Subdomínio / Identificador')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn (string $operation) => $operation === 'edit'),

                                Forms\Components\Select::make('status')
                                    ->label('Status da Conta')
                                    ->options([
                                        'trial' => 'Período de Teste',
                                        'active' => 'Ativo',
                                        'suspended' => 'Suspenso',
                                        'canceled' => 'Cancelado',
                                    ])
                                    ->required()
                                    ->default('trial'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Plano e Assinatura')
                            ->schema([
                                Forms\Components\Select::make('plan_id')
                                    ->label('Plano de Assinatura')
                                    ->relationship('plan', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\DateTimePicker::make('trial_ends_at')
                                    ->label('Fim do Período de Teste'),

                                Forms\Components\DateTimePicker::make('subscription_ends_at')
                                    ->label('Fim da Assinatura Atual'),

                                Forms\Components\Fieldset::make('Overrides de Limites')
                                    ->schema([
                                        Forms\Components\TextInput::make('limit_instruments_override')
                                            ->label('Limite Customizado de Instrumentos')
                                            ->numeric()
                                            ->helperText('Deixe vazio para usar o limite do plano'),

                                        Forms\Components\TextInput::make('limit_users_override')
                                            ->label('Limite Customizado de Usuários')
                                            ->numeric()
                                            ->helperText('Deixe vazio para usar o limite do plano'),
                                    ]),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Contato')
                            ->schema([
                                Forms\Components\TextInput::make('contact_email')
                                    ->label('E-mail de Contato')
                                    ->email(),

                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Telefone/WhatsApp'),

                                Forms\Components\Textarea::make('address')
                                    ->label('Endereço Completo')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Subdomínio')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plano')
                    ->sortable()
                    ->placeholder('Nenhum'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'trial' => 'Teste',
                        'active' => 'Ativo',
                        'suspended' => 'Suspenso',
                        'canceled' => 'Cancelado',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trial' => 'Teste',
                        'active' => 'Ativo',
                        'suspended' => 'Suspenso',
                        'canceled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plano')
                    ->relationship('plan', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('import_master_data')
                    ->label('Importar Dados Mestres')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Importar Biblioteca Global')
                    ->modalDescription('Deseja importar a biblioteca padrão de materiais e tipos de instrumentos para este cliente? Registros existentes não serão afetados.')
                    ->action(function (Tenant $record) {
                        (new ImportMasterDataAction)->execute($record->id);

                        Notification::make()
                            ->title('Dados Importados')
                            ->success()
                            ->send();
                    }),
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
            RelationManagers\ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
