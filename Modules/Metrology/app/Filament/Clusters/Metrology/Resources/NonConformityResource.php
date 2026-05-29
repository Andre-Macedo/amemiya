<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Pages;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Schemas\NonConformityForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Tables\NonConformityTable;
use Modules\Metrology\Models\NonConformity;

/**
 * Resource for managing Quality Deviation / Non-Conformity Reports (RNC).
 */
class NonConformityResource extends Resource
{
    protected static ?string $model = NonConformity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $modelLabel = 'Não-Conformidade';

    protected static ?string $pluralModelLabel = 'Não-Conformidades';

    protected static ?string $navigationLabel = 'RNCs';

    public static function form(Schema $schema): Schema
    {
        return NonConformityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NonConformityTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNonConformities::route('/'),
            'edit' => Pages\EditNonConformity::route('/{record}/edit'),
            'view' => Pages\ViewNonConformity::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', '!=', 'closed')->count() ?: null;
    }
}
