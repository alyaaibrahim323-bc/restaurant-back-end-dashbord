<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\CartResource\Pages;
// use App\Filament\Resources\CartResource\RelationManagers;
// use App\Models\Cart;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

// class CartResource extends Resource
// {
//     protected static ?string $model = Cart::class;

//     protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
//     protected static ?int $navigationSort = 5;


//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\TextInput::make('user_id')
//                     ->numeric()
//                     ->default(null),
//                 Forms\Components\TextInput::make('guest_uuid')
//                     ->maxLength(36)
//                     ->default(null),
//                 Forms\Components\TextInput::make('product_id')
//                     ->required()
//                     ->numeric(),
//                 Forms\Components\TextInput::make('quantity')
//                     ->required()
//                     ->numeric()
//                     ->default(1),
//                 Forms\Components\TextInput::make('variant_id')
//                     ->numeric()
//                     ->default(null),
//             ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 Tables\Columns\TextColumn::make('user_id')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('guest_uuid')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('product_id')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('quantity')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('created_at')
//                     ->dateTime()
//                     ->sortable()
//                     ->toggleable(isToggledHiddenByDefault: true),
//                 Tables\Columns\TextColumn::make('updated_at')
//                     ->dateTime()
//                     ->sortable()
//                     ->toggleable(isToggledHiddenByDefault: true),
//                 Tables\Columns\TextColumn::make('variant_id')
//                     ->numeric()
//                     ->sortable(),
//             ])
//             ->filters([
//                 //
//             ])
//             ->actions([
//                 Tables\Actions\EditAction::make(),
//             ])
//             ->bulkActions([
//                 Tables\Actions\BulkActionGroup::make([
//                     Tables\Actions\DeleteBulkAction::make(),
//                 ]),
//             ]);
//     }

//     public static function getRelations(): array
//     {
//         return [
//             //
//         ];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListCarts::route('/'),
//             'create' => Pages\CreateCart::route('/create'),
//             'edit' => Pages\EditCart::route('/{record}/edit'),
//         ];
//     }
// }
