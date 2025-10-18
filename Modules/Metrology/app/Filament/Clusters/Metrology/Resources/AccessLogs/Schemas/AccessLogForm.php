<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AccessLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('instrument_id')
                    ->label('Instrumento')
                    ->relationship('instrument', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('station_id')
                    ->label('Estação')
                    ->relationship('station', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('action')
                    ->label('Ação')
                    ->options([
                        'check_in' => 'Check In',
                        'check_out' => 'Check Out',
                    ])
                    ->required(),
            ]);
    }
}
