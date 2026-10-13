<?php

namespace App\Filament\Resources\Cars\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('price')->money('USD', decimalPlaces: 0),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
