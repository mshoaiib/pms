<?php

namespace App\Filament\Resources\Spaces\Resources\Folders\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('versions_count')
                    ->label('Versions')
                    ->counts('versions')
                    ->badge(),
                TextColumn::make('task_count')
                    ->label('Tasks in ClickUp')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('hidden')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
