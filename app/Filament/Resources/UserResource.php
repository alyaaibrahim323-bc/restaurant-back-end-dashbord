<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Client details';
    protected static ?string $modelLabel = 'Users';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('user.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label(__('user.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: User::class,
                        ignoreRecord: true,
                        // إزالة modifyRuleUsing لأننا لا نستخدم SoftDeletes
                    )
                    ->validationMessages([
                        'unique' => 'البريد الإلكتروني مسجل مسبقاً. الرجاء استخدام بريد إلكتروني آخر.',
                    ])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // التحقق بدون deleted_at
                        if (User::where('email', $state)->exists()) {
                            $set('email_error', 'هذا البريد الإلكتروني مستخدم بالفعل!');
                        } else {
                            $set('email_error', null);
                        }
                    })
                    ->helperText(function (Forms\Get $get) {
                        if ($get('email_error')) {
                            return new HtmlString(
                                '<span class="text-danger-600 font-medium">⚠️ ' . $get('email_error') . '</span>'
                            );
                        }
                        return null;
                    }),

                Forms\Components\TextInput::make('phone')
                    ->label(__('user.phone'))
                    ->tel()
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label(__('user.email_verified_at')),

                Forms\Components\TextInput::make('password')
                    ->label(__('user.password'))
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->dehydrated(fn ($state) => filled($state))
                    ->rule('min:6'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('user.name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('user.email'))
                    ->searchable()
                    ->description(fn ($record) => $record->email_verified_at ? 'موثق' : 'غير موثق')
                    ->color(fn ($record) => $record->email_verified_at ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('user.phone'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('user.email_verified_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('verified_email')
                    ->label('البريد الموثق')
                    ->query(fn ($query) => $query->whereNotNull('email_verified_at')),

                Tables\Filters\Filter::make('unverified_email')
                    ->label('البريد غير الموثق')
                    ->query(fn ($query) => $query->whereNull('email_verified_at')),
            ])
            ->actions([

                Tables\Actions\Action::make('verify_email')
                    ->label('تأكيد البريد')
                    ->icon('heroicon-o-check-badge')
                    ->action(function (User $record) {
                        $record->update(['email_verified_at' => now()]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => is_null($record->email_verified_at)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('general.delete')),

                    Tables\Actions\BulkAction::make('verify_emails')
                        ->label('تأكيد البريد المحدد')
                        ->icon('heroicon-o-check-badge')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['email_verified_at' => now()]);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
        ];
    }

    // -------- الصلاحيات --------
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }
}
