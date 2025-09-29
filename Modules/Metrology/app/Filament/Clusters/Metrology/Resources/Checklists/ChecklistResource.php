<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages\CreateChecklist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages\EditChecklist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages\ListChecklists;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages\ViewChecklist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Schemas\ChecklistForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Schemas\ChecklistInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Tables\ChecklistsTable;
use Modules\Metrology\Models\Checklist;

class ChecklistResource extends Resource
{
    protected static ?string $model = Checklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = MetrologyCluster::class;

    public static function form(Schema $schema): Schema
    {
        return ChecklistForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChecklistInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistsTable::configure($table);
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
            'index' => ListChecklists::route('/'),
            'create' => CreateChecklist::route('/create'),
            'view' => ViewChecklist::route('/{record}'),
            'edit' => EditChecklist::route('/{record}/edit'),
        ];
    }
}
