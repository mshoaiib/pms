<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Tables\TasksTable;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\TaskListResource;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $parentResource = TaskListResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Tasks reach their tenant through their List, which is already scoped.
     */
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
