<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class BestSellingProducts extends ChartWidget
{
    protected static ?string $heading = 'أفضل المنتجات مبيعاً';
    protected static ?string $maxHeight = '245px';
    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        try {
            $topProducts = OrderItem::query()
                ->selectRaw('product_id, SUM(quantity) as total_quantity')
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->with('product') // العلاقة بها withTrashed
                ->limit(15) // نجلب أكثر من 5 لتفادي المنتجات المحذوفة
                ->get()
                ->filter(fn($item) => $item->product !== null)
                ->take(4);

            if ($topProducts->isEmpty()) {
    Log::info('لم يتم العثور على منتجات لها مبيعات.');
    return $this->getEmptyData();
}


            Log::info('Top Products:', $topProducts->pluck('product.name')->toArray());

            return [
                'datasets' => [[
                    'data' => $topProducts->pluck('total_quantity')->toArray(),
                    'backgroundColor' => $this->getChartColors($topProducts->count()),
                ]],
                'labels' => $topProducts->pluck('product.name')->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('خطأ في Widget أفضل المنتجات: ' . $e->getMessage());
            return $this->getEmptyData();
        }
    }

    protected function getEmptyData(): array
    {
        return [
            'datasets' => [[
                'data' => [1],
                'backgroundColor' => ['#f39c12'],
                'label' => 'لا توجد مبيعات'
            ]],
            'labels' => ['لا توجد مبيعات'],
        ];
    }

    protected function getChartColors($count): array
    {
        $colors = [
            '#e91e63',
            '#ff9800',
            '#4caf50',
            '#3f51b5',
            '#00bcd4',
            '#f44336',
            '#8e44ad',
        ];

        return array_slice($colors, 0, $count);
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'rtl' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => \Js::from("function(context) {
                            return context.label + ': ' + context.raw + ' وحدة';
                        }")
                    ],
                ],
            ],
            'cutout' => '70%',
            'maintainAspectRatio' => false,
        ];
    }

    public function getDescription(): ?string
    {
        return 'أكثر المنتجات مبيعاً بناءً على الكمية المباعة';
    }
}
