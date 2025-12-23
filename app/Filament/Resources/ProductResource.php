<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'MENU';

    protected static ?string $navigationLabel = 'Products';
    protected static ?string $modelLabel = 'Product';
    protected static ?string $pluralModelLabel = 'Products';
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
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make(__('Basic Information'))
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                               Forms\Components\TextInput::make('name')
                                                ->label(__('Name'))
                                                ->required()
                                                ->maxLength(255)
                                                ->live(onBlur: true),

                                               Forms\Components\TextInput::make('slug')
                                                    ->label(__('name en '))
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true),

                                                Forms\Components\Select::make('category_id')
                                                    ->label(__('Category'))
                                                    ->relationship('category', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),

                                                Forms\Components\TextInput::make('price')
                                                    ->label(__('Base Price'))
                                                    ->numeric()
                                                    ->prefix('EGP')
                                                    ->required(),


                                            ])
                                            ->columns(4),
                                    ]),

                                Forms\Components\Section::make(__('Product Images'))
                                    ->schema([
                                        Forms\Components\FileUpload::make('images')
                                            ->label('')
                                            ->image()
                                            ->multiple()
                                            ->directory('products'),
                                    ]),
                                Forms\Components\Section::make(__(' Description'))
                                    ->schema([
                                        Forms\Components\RichEditor::make('description')
                                            ->label(''),
                                    ]),
                                     Forms\Components\Section::make(__(' Description ar'))
                                    ->schema([

                                        Forms\Components\RichEditor::make('description_ar')
                                            ->label(''),
                                    ]),

                            ])
                            ->columnSpan(2),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make(__('Product Options'))
                                    ->schema([
                                        static::getOptionsRepeater(),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function getOptionsRepeater(): Forms\Components\Repeater
    {
       return Forms\Components\Repeater::make('options')
    ->relationship('options')
    ->label('')
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label(__('Option Type ( combo, test)'))
            ->required()
            ->reactive(),
        Forms\Components\TextInput::make('name_ar')
            ->label(__('name ar'))
            ->required()
            ->reactive(),

        Forms\Components\Repeater::make('values')
            ->relationship('values')
            ->label(__('Option Values'))
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->label(__('Value ( combo, test)'))
                    ->required(),
                  Forms\Components\TextInput::make('value_ar')
            ->label(__('Value (Arabic)'))
            ->required()
            ->columnSpan(1),

                Forms\Components\ColorPicker::make('color_code')
                    ->label(__('Color Code'))
                    ->hidden(fn ($get) => !in_array(strtolower($get('../../name')), ['color', 'لون'])),

                Forms\Components\FileUpload::make('image')
                    ->label(__('Value Image'))
                    ->directory('product-option-values')
                    ->image()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp','image/svg'])
                    ->visibility('public')
                    ->preserveFilenames()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('100')
                    ->imageResizeTargetHeight('100')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->panelAspectRatio('1:1')
                    ->extraAttributes(['class' => 'border rounded-lg p-2']),

                Forms\Components\TextInput::make('price_modifier')
                    ->label(__('Price Modifier'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),
            ])
            ->grid(2)
            ->addActionLabel(__('+ Add Value')),
    ])
    ->addActionLabel(__('+ Add Option'))
    ->collapsible()
    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->columns([
                Tables\Columns\ImageColumn::make('images.0')
                    ->label(__('Images'))
                    ->getStateUsing(fn ($record) => $record->images[0] ?? null)
                    ->url(fn ($state) => $state ? asset('storage/' . $state) : null)
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('State'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('Status'))
                    ->options([
                        '1' => __('Active'),
                        '0' => __('Inactive'),
                    ]),

                SelectFilter::make('category')
                    ->label(__('Category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('price_range')
                    ->label(__('Price Range'))
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label(__('Min Price'))
                            ->numeric(),
                        Forms\Components\TextInput::make('max_price')
                            ->label(__('Max Price'))
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_price'],
                                fn (Builder $query, $minPrice): Builder => $query->where('price', '>=', $minPrice))
                            ->when($data['max_price'],
                                fn (Builder $query, $maxPrice): Builder => $query->where('price', '<=', $maxPrice));
                    }),
            ], )
            ->actions([
                Tables\Actions\EditAction::make()->label(__('Edit')),
                Tables\Actions\DeleteAction::make()->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('Delete Selected')),
                    Tables\Actions\ExportBulkAction::make()->label(__('Export')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label(__('Export Data'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('products.export'))
                    ->color('success'),

                Tables\Actions\Action::make('import')
                    ->label(__('Import Data'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label(__('ملف Excel'))
                            ->required()
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->helperText('
                                تنسيق الملف المطلوب:
                                - name: اسم المنتج (مطلوب)
                                - price: السعر (مطلوب، يجب أن يكون رقماً)
                                - category: الفئة (مطلوب)
                                - description: الوصف (اختياري)
                                - stock_quantity: الكمية في المخزون (اختياري)
                                - is_available: متاح للبيع (اختياري - yes/no)
                            ')
                    ])
                    ->action(function (array $data) {
                        try {
                    $import = new ProductsImport;
                    Excel::import($import, $data['file']);

                    $stats = $import->getImportStats();
                    $errors = $import->getErrors();

                    $successMessage = "✅ تم استيراد {$stats['imported']} منتج بنجاح";

                    if ($stats['skipped'] > 0) {
                        $successMessage .= "، ❌ تم تخطي {$stats['skipped']} منتج بسبب الأخطاء";
                    }

                    Notification::make()
                        ->title('نتيجة الاستيراد')
                        ->body($successMessage)
                        ->success()
                        ->send();

                        if (!empty($errors)) {
                        $errorDetails = "تفاصيل الأخطاء:\n\n" . implode("\n", array_slice($errors, 0, 8));

                        if (count($errors) > 8) {
                            $errorDetails .= "\n\n...و " . (count($errors) - 8) . " خطأ آخر";
                        }

                        Notification::make()
                            ->title('🔄 ملاحظات حول الاستيراد')
                            ->body($errorDetails)
                            ->warning()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view_all_errors')
                                    ->label('عرض كل الأخطاء')
                                    ->button()
                                    ->close()
                            ])
                            ->send();
                    }

                } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                    $errorMessages = [];
                    foreach ($e->failures() as $failure) {
                        $errorMessages[] = "📝 السطر {$failure->row()}: " . implode('، ', $failure->errors());
                    }

                    $errorText = "تم اكتشاف الأخطاء التالية:\n\n" . implode("\n", array_slice($errorMessages, 0, 6));

                    if (count($errorMessages) > 6) {
                        $errorText .= "\n\n...و " . (count($errorMessages) - 6) . " أخطاء أخرى";
                    }

                    Notification::make()
                        ->title('❌ فشل في استيراد البيانات')
                        ->body($errorText)
                        ->danger()
                        ->persistent()
                        ->send();

                } catch (\Exception $e) {
                    \Log::error('Import failed: ' . $e->getMessage());

                    Notification::make()
                        ->title('🚨 حدث خطأ غير متوقع')
                        ->body('حدث خطأ أثناء عملية الاستيراد. يرجى التحقق من تنسيق الملف وحاول مرة أخرى.')
                        ->danger()
                        ->send();
                }
            })->color('primary')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
