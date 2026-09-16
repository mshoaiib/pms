<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TaskListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->badge(),
                TextColumn::make('task_count')
                    ->label('Tasks in ClickUp')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('lid')
                    ->label('ClickUp ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('orderindex')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Model $record): bool {
                        $record->deleteInClickUp();

                        return true;
                    }),
            ]);
    }
}
