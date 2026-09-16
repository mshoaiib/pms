<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\RelationManagers;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\TaskListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TaskListsRelationManager extends RelationManager
{
    protected static string $relationship = 'taskLists';

    protected static ?string $relatedResource = TaskListResource::class;

    public function table(Table $table): Table
    {
        return TaskListResource::table($table);
    }
}
