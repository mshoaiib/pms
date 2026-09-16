<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\TaskListResource;
use App\Models\Folder;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTaskList extends CreateRecord
{
    protected static string $resource = TaskListResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Folder $folder */
        $folder = $this->getParentRecord();

        return $folder->createTaskListInClickUp($data);
    }
}
