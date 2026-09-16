<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
