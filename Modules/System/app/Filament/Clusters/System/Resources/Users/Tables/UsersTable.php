<?php

namespace Modules\System\Filament\Clusters\System\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Função')
                    ->badge()
                    ->color('info')
                    ->separator(','),

                TextColumn::make('tenant.name')
                    ->label('Cliente')
                    ->badge()
                    ->placeholder('Global (Super Admin)'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('impersonate')
                    ->label('Acessar como')
                    ->tooltip('Acessar sistema como este usuário')
                    ->icon('heroicon-o-finger-print')
                    ->color('warning')
                    ->hidden(fn ($record) => $record->tenant_id === null)
                    ->action(function ($record) {
                        $tenant = $record->tenant;
                        $domain = $tenant->domains()->first();

                        if (! $domain) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Este cliente não possui um domínio configurado.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Gera o token de personificação
                        $token = tenancy()->impersonate($tenant, $record->id, '/dashboard');

                        // Redireciona para o subdomínio com o token
                        // Nota: Em produção, usar helper para scheme (http/https)
                        $url = "http://{$domain->domain}/impersonate/{$token}";

                        return redirect()->away($url);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
