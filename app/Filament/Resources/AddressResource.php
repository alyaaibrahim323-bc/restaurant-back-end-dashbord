<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\AddressResource\Pages;
// use App\Filament\Resources\AddressResource\RelationManagers;
// use App\Models\Address;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

// class AddressResource extends Resource
// {
//     protected static ?string $model = Address::class;

//     protected static ?string $navigationIcon = 'heroicon-o-map-pin';
//             protected static ?int $navigationSort = 6;


//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\TextInput::make('user_id')
//                     ->required()
//                     ->numeric(),
//                 Forms\Components\TextInput::make('street')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('city')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('state')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('country')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('postal_code')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('building_number')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('apartment_number')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('floor_number')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('phone_number')
//                     ->tel()
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('location_url')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\Toggle::make('is_default')
//                     ->required(),
//                 Forms\Components\TextInput::make('latitude')
//                     ->numeric()
//                     ->default(null),
//                 Forms\Components\TextInput::make('longitude')
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
//                 Tables\Columns\TextColumn::make('street')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('city')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('state')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('country')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('postal_code')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('building_number')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('apartment_number')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('floor_number')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('phone_number')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('location_url')
//                     ->searchable(),
//                 Tables\Columns\IconColumn::make('is_default')
//                     ->boolean(),
//                 Tables\Columns\TextColumn::make('latitude')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('longitude')
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
//             'index' => Pages\ListAddresses::route('/'),
//             'create' => Pages\CreateAddress::route('/create'),
//             'edit' => Pages\EditAddress::route('/{record}/edit'),
//         ];
//     }
// }
