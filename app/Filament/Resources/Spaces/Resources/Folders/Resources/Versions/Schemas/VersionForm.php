<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Version')
                    ->placeholder('v1.0')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
