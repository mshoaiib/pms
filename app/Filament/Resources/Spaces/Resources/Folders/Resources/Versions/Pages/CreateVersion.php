<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\VersionResource;
use App\Models\Folder;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVersion extends CreateRecord
{
    protected static string $resource = VersionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Folder $folder */
        $folder = $this->getParentRecord();

        return $folder->createVersionInClickUp($data);
    }
}
