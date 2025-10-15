<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReferenceStandardTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes do Tipo de Padrão')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('reference_standards_count')
                            ->counts('referenceStandards')
                            ->label('Nº de Padrões Associados'),
                        TextEntry::make('created_at')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),

            ]);
    }
}
