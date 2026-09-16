<?php

namespace App\Filament\Resources\Spaces\RelationManagers;

use App\Filament\Resources\Spaces\Resources\Folders\FolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FoldersRelationManager extends RelationManager
{
    protected static string $relationship = 'folders';

    protected static ?string $relatedResource = FolderResource::class;

    public function table(Table $table): Table
    {
        return FolderResource::table($table);
    }
}
