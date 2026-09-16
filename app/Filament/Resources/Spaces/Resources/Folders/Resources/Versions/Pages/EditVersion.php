<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\VersionResource;
use App\Models\Folder;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVersion extends EditRecord
{
    protected static string $resource = VersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (Folder $record): bool {
                    $record->deleteInClickUp();

                    return true;
                }),
        ];
    }

    /**
     * @param  Folder  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->updateInClickUp($data);

        return $record;
    }
}
