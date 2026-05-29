<?php

namespace Modules\System\Filament\Clusters\System\Resources;

use App\Models\Subscription;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\System\Filament\Clusters\System\Resources\SubscriptionResource\Pages;
use Modules\System\Filament\Clusters\System\SystemCluster;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Assinatura';

    protected static ?string $pluralModelLabel = 'Assinaturas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Vínculo')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Cliente')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plano')
                            ->relationship('plan', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Estado do Pagamento')
                    ->schema([
                        Forms\Components\Select::make('gateway')
                            ->label('Provedor')
                            ->options([
                                'manual' => 'Manual / Faturamento Direto',
                                'asaas' => 'Asaas',
                                'mercadopago' => 'Mercado Pago',
                                'stripe' => 'Stripe',
                            ])
                            ->required()
                            ->default('manual'),

                        Forms\Components\TextInput::make('gateway_id')
                            ->label('ID Externo (Gateway)'),

                        Forms\Components\Select::make('status')
                            ->label('Status Local')
                            ->options([
                                'trialing' => 'Período de Teste',
                                'active' => 'Ativo',
                                'past_due' => 'Pagamento Atrasado',
                                'canceled' => 'Cancelado',
                                'ended' => 'Encerrado',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(3),

                Forms\Components\Section::make('Datas e Ciclo')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Fim do Trial'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Expira em'),
                        Forms\Components\DateTimePicker::make('next_billing_at')
                            ->label('Próxima Cobrança'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plano')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'trialing' => 'Teste',
                        'active' => 'Ativo',
                        'past_due' => 'Atrasado',
                        'canceled' => 'Cancelado',
                        'ended' => 'Encerrado',
                    ]),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Vencimento')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trialing' => 'Teste',
                        'active' => 'Ativo',
                        'past_due' => 'Atrasado',
                        'canceled' => 'Cancelado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
