<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FavoriteResource\Pages;
use App\Models\Favorite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FavoriteResource extends Resource
{
    protected static ?string $model = Favorite::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Client details';
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Favorite';
    protected static ?string $pluralModelLabel = 'Favorites';
    protected static ?string $navigationLabel = 'Favorites';

    // ✅ هنا دوال الترجمة
      public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }
    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::$pluralModelLabel);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label(__('User'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest_uuid')
                    ->label(__('Guest UUID'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_id')
                    ->label(__('Product'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                ->label(__('Product Name'))
                ->sortable()
                ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // حذفنا زر التعديل
            ])
            ->bulkActions([
                // حذفنا Bulk Actions
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFavorites::route('/'),
            // مفيش إنشاء أو تعديل
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
