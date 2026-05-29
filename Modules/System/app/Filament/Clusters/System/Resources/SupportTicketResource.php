<?php

namespace Modules\System\Filament\Clusters\System\Resources;

use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\System\Filament\Clusters\System\Resources\SupportTicketResource\Pages;
use Modules\System\Filament\Clusters\System\Resources\SupportTicketResource\RelationManagers;
use Modules\System\Filament\Clusters\System\SystemCluster;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Chamado de Suporte';

    protected static ?string $pluralModelLabel = 'Chamados de Suporte';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make('Informações do Chamado')
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->label('Assunto')
                                    ->disabled(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição Inicial')
                                    ->disabled()
                                    ->rows(5),
                            ])->columnSpan(2),

                        Forms\Components\Section::make('Status e Controle')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'open' => 'Aberto',
                                        'in_progress' => 'Em Atendimento',
                                        'resolved' => 'Resolvido',
                                        'closed' => 'Fechado',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('priority')
                                    ->label('Prioridade')
                                    ->options([
                                        'low' => 'Baixa',
                                        'medium' => 'Média',
                                        'high' => 'Alta',
                                        'urgent' => 'Urgente',
                                    ])
                                    ->required(),

                                Forms\Components\Placeholder::make('tenant_name')
                                    ->label('Cliente')
                                    ->content(fn ($record) => $record?->tenant?->name),

                                Forms\Components\Placeholder::make('user_name')
                                    ->label('Solicitante')
                                    ->content(fn ($record) => $record?->user?->name),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aberto em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aberto',
                        'in_progress' => 'Em Atendimento',
                        'resolved' => 'Resolvido',
                        'closed' => 'Fechado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Aberto',
                        'in_progress' => 'Em Atendimento',
                        'resolved' => 'Resolvido',
                        'closed' => 'Fechado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Atender'),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
