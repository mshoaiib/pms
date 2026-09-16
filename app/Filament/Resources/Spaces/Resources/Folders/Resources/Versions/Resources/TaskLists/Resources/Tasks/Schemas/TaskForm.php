<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Schemas;

use App\Models\Task;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->helperText('Must match one of the statuses configured on the Space in ClickUp.'),
                Select::make('priority')
                    ->options(Task::priorities()),
                DateTimePicker::make('due_date')
                    ->seconds(false),
            ]);
    }
}
