<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (Task $record): bool {
                    $record->deleteInClickUp();

                    return true;
                }),
        ];
    }

    /**
     * @param  Task  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->updateInClickUp($data);

        return $record;
    }
}
