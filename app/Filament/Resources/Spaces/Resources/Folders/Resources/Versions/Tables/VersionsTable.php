<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('task_lists_count')
                    ->label('Lists')
                    ->counts('taskLists')
                    ->badge(),
                TextColumn::make('task_count')
                    ->label('Tasks in ClickUp')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('archived')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fid')
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
