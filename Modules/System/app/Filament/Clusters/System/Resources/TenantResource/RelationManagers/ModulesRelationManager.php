<?php

namespace Modules\System\Filament\Clusters\System\Resources\TenantResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'activeModules';

    protected static ?string $title = 'Módulos e Add-ons Ativos';

    protected static ?string $modelLabel = 'Módulo';

    public function form(Schema $schema): Schema
    {
        $availableModules = collect(config('amemiya.modules'))
            ->filter(fn ($m) => ! ($m['is_core'] ?? false)) // Esconde módulos core da lista de ativação
            ->mapWithKeys(fn ($m, $id) => [$id => $m['name']])
            ->toArray();

        return $schema
            ->components([
                Forms\Components\Select::make('module_id')
                    ->label('Módulo')
                    ->options($availableModules)
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                        return $rule->where('tenant_id', $this->getOwnerRecord()->id);
                    }),

                Forms\Components\DateTimePicker::make('activated_at')
                    ->label('Ativado em')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expira em')
                    ->helperText('Deixe vazio para acesso vitalício (enquanto a assinatura durar).'),

                Forms\Components\KeyValue::make('settings')
                    ->label('Configurações do Módulo')
                    ->addActionLabel('Adicionar Configuração')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('module_id')
            ->columns([
                Tables\Columns\TextColumn::make('module_id')
                    ->label('Módulo')
                    ->formatStateUsing(fn ($state) => config("amemiya.modules.{$state}.name", $state))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('activated_at')
                    ->label('Ativado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y')
                    ->placeholder('Vitalício')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ativar Novo Módulo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Desativar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
