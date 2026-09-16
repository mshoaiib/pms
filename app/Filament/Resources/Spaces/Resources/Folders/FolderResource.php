<?php

namespace App\Filament\Resources\Spaces\Resources\Folders;

use App\Filament\Resources\Spaces\Resources\Folders\Pages\CreateFolder;
use App\Filament\Resources\Spaces\Resources\Folders\Pages\EditFolder;
use App\Filament\Resources\Spaces\Resources\Folders\RelationManagers\VersionsRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Schemas\FolderForm;
use App\Filament\Resources\Spaces\Resources\Folders\Tables\FoldersTable;
use App\Filament\Resources\Spaces\SpaceResource;
use App\Models\Folder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $parentResource = SpaceResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Folders reach their tenant through their Space, which is already scoped.
     */
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return FolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoldersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'versions' => VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateFolder::route('/create'),
            'edit' => EditFolder::route('/{record}/edit'),
        ];
    }
}
