<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\RelationManagers;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = TaskResource::class;

    public function table(Table $table): Table
    {
        return TaskResource::table($table);
    }
}
