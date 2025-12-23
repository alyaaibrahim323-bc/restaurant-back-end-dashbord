<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'خيارات المنتج';

// في OptionsRelationManager.php


public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('نوع الخيار')
                ->description(fn ($record) => $record->values->pluck('value')->join(', '))
                ->badge()
                ->color('primary'),
        ])
        ->actions([
            // Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
            // Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
        ])
        ->headerActions([
            // Tables\Actions\CreateAction::make()
            //     ->modalHeading('إضافة خيار جديد')
            //     ->label('إضافة خيار'),
        ]);
}
}
