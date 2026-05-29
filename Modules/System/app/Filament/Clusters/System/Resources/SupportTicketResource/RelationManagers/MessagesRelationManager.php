<?php

namespace Modules\System\Filament\Clusters\System\Resources\SupportTicketResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversa';

    protected static ?string $modelLabel = 'Mensagem';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Textarea::make('message')
                    ->label('Sua Resposta')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_internal')
                    ->label('Mensagem Interna (Nota Privada)')
                    ->helperText('O cliente não verá esta mensagem no frontend.')
                    ->default(false),

                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Enviado por')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Mensagem')
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Privada')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-globe-alt'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Enviar Resposta')
                    ->modalHeading('Nova Mensagem')
                    ->after(function () {
                        // Opcional: Notificar o cliente por e-mail aqui
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
