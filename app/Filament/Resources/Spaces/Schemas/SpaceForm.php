<?php

namespace App\Filament\Resources\Spaces\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SpaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                ColorPicker::make('color'),
                Toggle::make('private')
                    ->helperText('Private Spaces are only visible to their members in ClickUp.'),
                Toggle::make('multiple_assignees')
                    ->default(true),
            ]);
    }
}
