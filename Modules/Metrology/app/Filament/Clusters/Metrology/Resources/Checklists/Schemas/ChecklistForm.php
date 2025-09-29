<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('calibration_id')
                    ->relationship('calibration', 'id')
                    ->required(),
                Repeater::make('steps')
                    ->schema([
                        TextInput::make('step')->required(),
                        Toggle::make('completed')->default(false),
                    ])
                    ->columns(2),
                Toggle::make('completed')->default(false),

            ]);
    }
}
