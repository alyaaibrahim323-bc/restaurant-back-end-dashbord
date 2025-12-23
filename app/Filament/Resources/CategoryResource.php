<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Imports\CategoriesImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'MENU';
    protected static ?string $modelLabel = 'Category';
    protected static ?string $pluralModelLabel = 'Categories';
    protected static ?string $navigationLabel = 'Categories';
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
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('parent_id')
                            ->label(__('Parent Category'))
                            ->options(Category::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Image'))
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->directory('categories')
                            ->image()
                            ->imageEditor()
                    ])
                    ->collapsible(),

                Forms\Components\RichEditor::make('description')
                    ->label(__('Description'))
                    ->columnSpanFull(),
                    Forms\Components\RichEditor::make('description_ar')
                    ->label(__('Description ar'))
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label(__('Export Categories'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('categories.export'))
                    ->color('success'),

                Tables\Actions\Action::make('import')
                    ->label(__('Import Categories'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label(__('Excel File'))
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv'
                            ])
                            ->rules(['file', 'mimes:xlsx,xls,csv'])
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new CategoriesImport();
                            Excel::import($import, $data['file']);

                            $errors = $import->errors();
                            $failures = $import->failures();

                            if (count($errors) > 0 || count($failures) > 0) {
                                $errorMessages = [];

                                foreach ($errors as $error) {
                                    $errorMessages[] = $error->getMessage();
                                }

                                foreach ($failures as $failure) {
                                    $errorMessages[] = "سطر {$failure->row()}: " . implode(', ', $failure->errors());
                                }

                                Notification::make()
                                    ->title(__('Imported with some errors'))
                                    ->body(implode('\n', array_slice($errorMessages, 0, 5)) . (count($errorMessages) > 5 ? '\n... ' . __('and more') : ''))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title(__('Categories imported successfully'))
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Import failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->color('primary')
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Image'))
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('Parent Category'))
                    ->placeholder(__('Main'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('Products'))
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label(__('Parent Category'))
                    ->options(Category::whereNull('parent_id')->pluck('name', 'id'))
                    ->placeholder(__('All')),

                Tables\Filters\Filter::make('created_at')
                    ->label(__('Creation Date'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('From')),
                        Forms\Components\DatePicker::make('to')
                            ->label(__('To')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],
                                fn ($query) => $query->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when($data['to'],
                                fn ($query) => $query->whereDate('created_at', '<=', $data['to'])
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('export')
                        ->label(__('Export Selected'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            return Excel::download(
                                new \App\Exports\CategoriesExport($records->pluck('id')),
                                'categories-'.now()->format('Y-m-d').'.xlsx'
                            );
                        })
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
