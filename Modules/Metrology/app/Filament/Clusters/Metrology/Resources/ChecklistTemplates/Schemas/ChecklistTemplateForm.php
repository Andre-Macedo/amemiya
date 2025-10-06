<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('instrument_type')
                    ->required()
                    ->maxLength(255),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        TextInput::make('step')
                            ->required(),
                        Select::make('question_type')
                            ->options([
                                'boolean' => 'Boolean',
                                'numeric' => 'Numeric',
                                'text' => 'Text',
                            ])
                            ->required(),
                        TextInput::make('order')
                            ->numeric()
                            ->required(),
                        TextInput::make('required_readings')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('reference_standard_type')
                            ->nullable(),
                    ])
                    ->columns(2),
        ]);
    }
}
