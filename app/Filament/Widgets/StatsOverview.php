<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;


class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
{
    return [
        Stat::make('Today\'s Sales', Order::whereDate('created_at', Carbon::today())->sum('total') . ' EGP')
    ->icon('heroicon-o-currency-dollar')
    ->color('success'),

        Stat::make('New order', Order::whereDate('created_at', today())->count())
            ->description($this->getOrderIncrease())
            ->icon('heroicon-o-shopping-cart')
            ->color('info'),

        Stat::make(' New Customers', User::whereDate('created_at', today())->count())
            ->icon('heroicon-o-user-group')
            ->color('primary'),


    ];
}
protected function getOrderIncrease(): string
{
    $today = Order::whereDate('created_at', today())->count();
    $yesterday = Order::whereDate('created_at', today()->subDay())->count();

    return $this->calculateIncrease($today, $yesterday);
}
protected function calculateIncrease(int $today, int $yesterday): string
{
    if ($yesterday === 0) return 'لا يوجد بيانات سابقة';

    $increase = (($today - $yesterday) / $yesterday) * 100;
    $trend = $increase >= 0 ? 'زيادة' : 'انخفاض';

    return sprintf('%s %.1f%% عن الأمس', $trend, abs($increase));
}
}
