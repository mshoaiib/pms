<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\FolderResource;
use App\Models\Space;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFolder extends CreateRecord
{
    protected static string $resource = FolderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Space $space */
        $space = $this->getParentRecord();

        return $space->createFolderInClickUp($data);
    }
}
