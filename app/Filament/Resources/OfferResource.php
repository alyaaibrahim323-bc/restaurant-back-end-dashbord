<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferResource\Pages;
use App\Models\Offer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'offers';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Offer';
    protected static ?string $pluralModelLabel = 'Offers';
    protected static ?string $navigationLabel = 'Offers';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Offer Title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                if (empty($state)) return;
                                // إنشاء برومو كود تلقائي من العنوان
                                $promoCode = strtoupper(Str::slug($state, ''));
                                if (strlen($promoCode) > 8) {
                                    $promoCode = substr($promoCode, 0, 8);
                                }
                                $set('promo_code', $promoCode);
                            }),
                        Forms\Components\TextInput::make('title_ar')
                            ->label(__('Offer ar'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Offer Description'))
                            ->columnSpanFull(),
                            Forms\Components\Textarea::make('description_ar')
                            ->label(__('ar Description'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('promo_code')
                            ->label(__('Promo Code'))
                            ->required()
                            ->unique(ignorable: $form->getRecord())
                            ->maxLength(50)
                            ->helperText('كود فريد يستخدمه العملاء للحصول على الخصم'),

                        Forms\Components\FileUpload::make('image')
                            ->label(__('Offer Image'))
                            ->image()
                            ->directory('offers')
                            ->preserveFilenames()
                            ->maxSize(2048)
                            ->helperText('الصورة التي تظهر في تطبيق العميل'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Discount Settings')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label(__('Discount Type'))
                            ->required()
                            ->options([
                                'percentage' => 'نسبة مئوية',
                                'fixed' => 'مبلغ ثابت',
                                'free_delivery' => 'توصيل مجاني',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state === 'free_delivery') {
                                    $set('discount_value', 0);
                                } else {
                                    // ⭐ التأكد من وجود قيمة عند تغيير النوع
                                    $currentValue = $get('discount_value');
                                    if (empty($currentValue)) {
                                        $set('discount_value', 10); // قيمة افتراضية
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('discount_value')
                            ->label(__('Discount Value'))
                            ->numeric()
                            ->minValue(0)
                            ->required(function ($get) {
                                return $get('discount_type') !== 'free_delivery';
                            })
                            ->disabled(function ($get) {
                                return $get('discount_type') === 'free_delivery';
                            })
                            ->default(10) // ⭐ قيمة افتراضية
                            ->helperText(function ($get) {
                                $type = $get('discount_type');
                                if ($type === 'percentage') {
                                    return 'قيمة النسبة المئوية (مثال: 20 لخصم 20%)';
                                } elseif ($type === 'fixed') {
                                    return 'المبلغ الثابت (مثال: 50 لخصم 50 جنيه)';
                                } else {
                                    return 'التوصيل المجاني لا يتطلب قيمة';
                                }
                            }),

                        Forms\Components\ColorPicker::make('color')
                            ->label(__('Background Color'))
                            ->default('#3B82F6')
                            ->helperText('لون خلفية العرض في التطبيق'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Settings & Limits')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active Offer'))
                            ->default(true)
                            ->helperText('تفعيل/إلغاء تفعيل العرض'),

                        Forms\Components\DatePicker::make('valid_until')
                            ->label(__('Valid Until'))
                            ->minDate(now())
                            ->helperText('تاريخ انتهاء الصلاحية - اتركه فارغاً ليبقى دائماً فعال'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->label(__('Usage Limit'))
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('الحد الأقصى لعدد مرات الاستخدام - اتركه فارغاً لاستخدام غير محدود'),

                        Forms\Components\TextInput::make('used_count')
                            ->label(__('Used Count'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->helperText('عدد مرات استخدام هذا العرض (تلقائي)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Image'))
                    ->circular()
                    ->defaultImageUrl(asset('images/default-offer.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Offer $record) => $record->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('promo_code')
                    ->label(__('Promo Code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label(__('Discount Type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'percentage' => 'نسبة مئوية',
                        'fixed' => 'مبلغ ثابت',
                        'free_delivery' => 'توصيل مجاني',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'warning',
                        'free_delivery' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label(__('Discount Value'))
                    ->formatStateUsing(function (Offer $record) {
                        if ($record->discount_type === 'percentage') {
                            return ($record->discount_value ?? 0) . '%';
                        } elseif ($record->discount_type === 'fixed') {
                            return number_format(($record->discount_value ?? 0), 2) . ' جنيه';
                        } else {
                            return 'مجاني';
                        }
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('used_count')
                    ->label(__('Used'))
                    ->formatStateUsing(fn (Offer $record) => $record->used_count . ' / ' . ($record->usage_limit ?? '∞'))
                    ->sortable()
                    ->color(function (Offer $record) {
                        if ($record->usage_limit && $record->used_count >= $record->usage_limit) {
                            return 'danger';
                        }
                        return 'success';
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('Valid Until'))
                    ->date()
                    ->sortable()
                    ->color(function (Offer $record) {
                        if ($record->valid_until && $record->valid_until->isPast()) {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->description(function (Offer $record) {
                        if ($record->valid_until && $record->valid_until->isPast()) {
                            return 'منتهي';
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label(__('Discount Type'))
                    ->options([
                        'percentage' => 'نسبة مئوية',
                        'fixed' => 'مبلغ ثابت',
                        'free_delivery' => 'توصيل مجاني',
                    ]),

                Tables\Filters\Filter::make('is_active')
                    ->label(__('Active Offers Only'))
                    ->query(fn ($query) => $query->where('is_active', true)),

                Tables\Filters\Filter::make('expired')
                    ->label(__('Expired Offers'))
                    ->query(fn ($query) => $query->where('valid_until', '<', now())),

                Tables\Filters\Filter::make('reached_limit')
                    ->label(__('Reached Usage Limit'))
                    ->query(fn ($query) => $query->whereColumn('used_count', '>=', 'usage_limit')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // يمكنك إضافة علاقات هنا مثل استخدامات العملاء
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffers::route('/'),
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
