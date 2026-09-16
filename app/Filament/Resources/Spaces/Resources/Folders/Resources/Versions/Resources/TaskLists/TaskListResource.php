<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Pages\CreateTaskList;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Pages\EditTaskList;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Schemas\TaskListForm;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Tables\TaskListsTable;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\VersionResource;
use App\Models\TaskList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskListResource extends Resource
{
    protected static ?string $model = TaskList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $parentResource = VersionResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'list';

    protected static ?string $pluralModelLabel = 'lists';

    /**
     * Lists reach their tenant through their version, which is already scoped.
     */
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return TaskListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'tasks' => TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateTaskList::route('/create'),
            'edit' => EditTaskList::route('/{record}/edit'),
        ];
    }
}
