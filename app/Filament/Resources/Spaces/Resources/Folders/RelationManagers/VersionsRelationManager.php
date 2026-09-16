<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\RelationManagers;

use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\VersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $relatedResource = VersionResource::class;

    public function table(Table $table): Table
    {
        return VersionResource::table($table);
    }
}
