<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Chart';

    protected function getData(): array
{
    $salesData = Trend::model(Order::class)
        ->between(
            start: now()->startOfMonth(),
            end: now()->endOfMonth(),
        )
        ->perDay()
        ->sum('total');

    return [
        'datasets' => [
            [
                'label' => 'المبيعات اليومية',
                'data' => $salesData->map(fn (TrendValue $value) => $value->aggregate),
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
            ],
        ],
        'labels' => $salesData->map(fn (TrendValue $value) => $value->date),
    ];
}

protected function getType(): string
{
    return 'line';
}
}
