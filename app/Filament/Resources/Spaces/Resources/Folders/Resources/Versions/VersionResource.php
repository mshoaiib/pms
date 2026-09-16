<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions;

use App\Filament\Resources\Spaces\Resources\Folders\FolderResource;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Pages\CreateVersion;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Pages\EditVersion;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\RelationManagers\TaskListsRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Schemas\VersionForm;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Tables\VersionsTable;
use App\Models\Folder;
use BackedEnum;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * A version is a ClickUp sub folder: a Folder nested inside a project Folder.
 */
class VersionResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'version';

    protected static ?string $pluralModelLabel = 'versions';

    /**
     * Versions reach their tenant through their Folder, which is already scoped.
     */
    protected static bool $isScopedToTenant = false;

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return FolderResource::asParent()
            ->relationship('versions')
            ->inverseRelationship('parentFolder');
    }

    public static function form(Schema $schema): Schema
    {
        return VersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'taskLists' => TaskListsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateVersion::route('/create'),
            'edit' => EditVersion::route('/{record}/edit'),
        ];
    }
}
