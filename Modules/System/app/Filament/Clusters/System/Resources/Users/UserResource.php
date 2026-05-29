<?php

namespace Modules\System\Filament\Clusters\System\Resources\Users;

use App\NavigationGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\System\Filament\Clusters\System\Resources\Users\Pages\CreateUser;
use Modules\System\Filament\Clusters\System\Resources\Users\Pages\EditUser;
use Modules\System\Filament\Clusters\System\Resources\Users\Pages\ListUsers;
use Modules\System\Filament\Clusters\System\Resources\Users\Pages\ViewUser;
use Modules\System\Filament\Clusters\System\Resources\Users\Schemas\UserForm;
use Modules\System\Filament\Clusters\System\Resources\Users\Schemas\UserInfolist;
use Modules\System\Filament\Clusters\System\Resources\Users\Tables\UsersTable;
use Modules\System\Filament\Clusters\System\SystemCluster;
use Modules\System\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-users';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Usuário';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Security->getLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
