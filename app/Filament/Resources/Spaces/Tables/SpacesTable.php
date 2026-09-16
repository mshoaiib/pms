<?php

namespace App\Filament\Resources\Spaces\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SpacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->toggleable(),
                TextColumn::make('folders_count')
                    ->label('Folders')
                    ->counts('folders')
                    ->badge(),
                IconColumn::make('private')
                    ->boolean(),
                TextColumn::make('sid')
                    ->label('ClickUp ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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
