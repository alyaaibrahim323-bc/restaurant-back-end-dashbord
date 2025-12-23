<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\PointResource\Pages;
// use App\Models\Point;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;

// class PointResource extends Resource
// {
//     protected static ?string $model = Point::class;

//     protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
//     protected static ?string $navigationGroup = 'offers&points';
//     protected static ?int $navigationSort = 1;

//     protected static ?string $navigationLabel = 'Points';
//     protected static ?string $modelLabel = 'Point';
//     protected static ?string $pluralModelLabel = 'Points';
//  public static function getNavigationGroup(): ?string
//     {
//         return __(static::$navigationGroup);
//     }
//     public static function getNavigationLabel(): string
//     {
//         return __(static::$navigationLabel);
//     }

//     public static function getModelLabel(): string
//     {
//         return __(static::$modelLabel);
//     }

//     public static function getPluralModelLabel(): string
//     {
//         return __(static::$pluralModelLabel);
//     }

//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\Select::make('user_id')
//                     ->label(__('User'))
//                     ->relationship('user', 'name')
//                     ->searchable()
//                     ->preload()
//                     ->required(),

//                 Forms\Components\TextInput::make('points')
//                     ->label(__('Points'))
//                     ->numeric()
//                     ->required(),

//                 Forms\Components\Select::make('source')
//                     ->label(__('Source'))
//                     ->options([
//                         'purchase' => __('Purchase'),
//                         'referral' => __('Referral'),
//                         'bonus' => __('Bonus'),
//                         'other' => __('Other'),
//                     ])
//                     ->required(),

//                 Forms\Components\Textarea::make('description')
//                     ->label(__('Description'))
//                     ->columnSpanFull(),
//             ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 Tables\Columns\TextColumn::make('user.name')
//                     ->label(__('User'))
//                     ->sortable()
//                     ->searchable(),

//                 Tables\Columns\TextColumn::make('points')
//                     ->label(__('Points'))
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('source')
//                     ->label(__('Source'))
//                     ->formatStateUsing(fn ($state) => [
//                         'purchase' => __('Purchase'),
//                         'referral' => __('Referral'),
//                         'bonus' => __('Bonus'),
//                         'other' => __('Other'),
//                     ][$state] ?? $state),

//                 Tables\Columns\TextColumn::make('created_at')
//                     ->label(__('Created At'))
//                     ->dateTime()
//                     ->sortable(),
//             ])
//             ->filters([
//                 //
//             ])
//             ->actions([
//                 Tables\Actions\EditAction::make()->label(__('Edit')),
//                 Tables\Actions\DeleteAction::make()->label(__('Delete')),
//             ])
//             ->bulkActions([
//                 Tables\Actions\BulkActionGroup::make([
//                     Tables\Actions\DeleteBulkAction::make()->label(__('Delete Selected')),
//                 ]),
//             ]);
//     }

//     public static function getRelations(): array
//     {
//         return [];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListPoints::route('/'),
//             'create' => Pages\CreatePoint::route('/create'),
//             'edit' => Pages\EditPoint::route('/{record}/edit'),
//         ];
//     }

//     public static function canViewAny(): bool
//     {
//         return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
//     }
// }
