<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Pages\ListAccessLogs;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Schemas\AccessLogForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Schemas\AccessLogInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Tables\AccessLogsTable;
use Modules\Metrology\Models\AccessLog;

class AccessLogResource extends Resource
{
    protected static ?string $model = AccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Operações';

    public static function form(Schema $schema): Schema
    {
        return AccessLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccessLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccessLogsTable::configure($table);
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
            'index' => ListAccessLogs::route('/'),
//            'create' => CreateAccessLog::route('/create'),
//            'view' => ViewAccessLog::route('/{record}'),
//            'edit' => EditAccessLog::route('/{record}/edit'),
        ];
    }
}
