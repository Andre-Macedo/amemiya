<?php

namespace Modules\System\Filament\Clusters\System\Resources\Suppliers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Pages\CreateSupplier;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Pages\EditSupplier;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Pages\ListSuppliers;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Pages\ViewSupplier;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Schemas\SupplierForm;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Schemas\SupplierInfolist;
use Modules\System\Filament\Clusters\System\Resources\Suppliers\Tables\SuppliersTable;
use Modules\System\Filament\Clusters\System\SystemCluster;
use Modules\System\Models\Supplier;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Fornecedor / Parceiro';

    protected static ?string $pluralModelLabel = 'Fornecedores / Parceiros';

    protected static string|null|\UnitEnum $navigationGroup = 'Corporativo';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
