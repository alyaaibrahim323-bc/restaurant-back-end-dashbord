<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'orders';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return __('Order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Order Information'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([

                                Forms\Components\Select::make('status')
                                    ->label(__('Order Status'))
                                    ->options([
                                        'pending' => __('Pending'),
                                        'processing' => __('Processing'),
                                        'shipped' => __('Shipped'),
                                        'delivered' => __('Delivered'),
                                        'cancelled' => __('Cancelled'),
                                    ])
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan(2),

                Forms\Components\Section::make(__('Timestamps'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label(__('Created At'))
                            ->displayFormat('M d, Y H:i:s')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label(__('Updated At'))
                            ->displayFormat('M d, Y H:i:s')
                            ->disabled(),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('Total'))
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                        'info' => 'shipped',
                    ])
                    ->formatStateUsing(fn($state) => __($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Order Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'processing' => __('Processing'),
                        'shipped' => __('Shipped'),
                        'delivered' => __('Delivered'),
                        'cancelled' => __('Cancelled'),
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->label(__('Order Date'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('From Date')),
                        Forms\Components\DatePicker::make('to')
                            ->label(__('To Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],
                                fn($query) => $query->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when($data['to'],
                                fn($query) => $query->whereDate('created_at', '<=', $data['to'])
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),

                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('printSelected')
                        ->label(__('Print Selected'))
                        ->icon('heroicon-o-printer')
                        ->action(function ($records) {
                            $records->load(['user', 'address', 'items.product']);

                            $pdf = Pdf::loadView('orders.print-multiple', [
                                'orders' => $records
                            ])->setOption([
                                'defaultFont' => 'Tajawal',
                                'isHtml5ParserEnabled' => true,
                                'isRemoteEnabled' => true,
                                'dpi' => 150,
                                'fontHeightRatio' => 0.9,
                                'isPhpEnabled' => true,
                                'chroot' => realpath(base_path()),
                            ]);

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->stream();
                            }, 'orders-' . now()->format('Y-m-d') . '.pdf');
                        })
                        ->color('primary'),

                    Tables\Actions\BulkAction::make('markAsProcessing')
                        ->label(__('Mark as Processing'))
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn($records) => $records->each->update(['status' => 'processing']))
                        ->color('primary'),

                    Tables\Actions\BulkAction::make('markAsShipped')
                        ->label(__('Mark as Shipped'))
                        ->icon('heroicon-o-truck')
                        ->action(fn($records) => $records->each->update(['status' => 'shipped']))
                        ->color('info'),
                ]),
            ]);

    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
    public static function canCreate(): bool
{
    return false;
}

}
