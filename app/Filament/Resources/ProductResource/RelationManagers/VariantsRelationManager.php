<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'متغيرات المنتج';

  // في VariantsRelationManager.php


public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('optionValuesSummary')
                ->label('المواصفات')
                ->description(fn ($record) => 'SKU: ' . $record->sku),

            Tables\Columns\TextColumn::make('price')
                ->label('السعر')
                ->money('EGP')
                ->color('success'),

            Tables\Columns\TextColumn::make('stock')
                ->label('المخزون')
                ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

            Tables\Columns\ImageColumn::make('image')
                ->label('')
                ->circular()
                ->size(40)
        ])
        ->actions([
            Tables\Actions\EditAction::make()->icon('heroicon-o-pencil'),
            Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('إضافة متغير')
                ->modalHeading('إنشاء متغير جديد'),
        ]);
}
}
