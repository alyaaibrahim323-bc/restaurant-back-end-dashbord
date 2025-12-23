<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Order::with('user')->latest()->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('#'),
            TextColumn::make('user.name')->label('coustmer'),
            TextColumn::make('total')
                ->money('EGP')
                ->sortable()
                ->label('total'),
            BadgeColumn::make('status')
                ->colors([
                    'warning' => 'pending',
                    'primary' => 'processing',
                    'success' => 'delivered',
                ])
                ->label('status'),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->label('created_at'),
        ];
    }
}
