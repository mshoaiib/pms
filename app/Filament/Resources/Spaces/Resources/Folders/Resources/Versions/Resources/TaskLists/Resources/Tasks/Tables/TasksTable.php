<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\Tables;

use App\Models\Task;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): ?string => Task::priorities()[$state] ?? null)
                    ->color(fn (?int $state): string => match ($state) {
                        1 => 'danger',
                        2 => 'warning',
                        4 => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('assignees')
                    ->label('Assignees')
                    ->formatStateUsing(fn (mixed $state): string => collect((array) $state)
                        ->pluck('username')
                        ->filter()
                        ->join(', ')),
                TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('tid')
                    ->label('ClickUp ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->options(Task::priorities()),
                SelectFilter::make('status')
                    ->options(fn (): array => Task::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->pluck('status', 'status')
                        ->all()),
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
