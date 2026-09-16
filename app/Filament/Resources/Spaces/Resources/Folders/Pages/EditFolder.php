<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Pages;

use App\Filament\Resources\Spaces\Resources\Folders\FolderResource;
use App\Models\Folder;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFolder extends EditRecord
{
    protected static string $resource = FolderResource::class;

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
