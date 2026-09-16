<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\TaskListResource;
use App\Models\TaskList;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTaskList extends EditRecord
{
    protected static string $resource = TaskListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (TaskList $record): bool {
                    $record->deleteInClickUp();

                    return true;
                }),
        ];
    }

    /**
     * @param  TaskList  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->updateInClickUp($data);

        return $record;
    }
}
