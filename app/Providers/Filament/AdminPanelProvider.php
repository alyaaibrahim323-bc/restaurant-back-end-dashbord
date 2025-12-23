<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\BestSellingProducts;
use App\Filament\Pages\Auth\Login;
use Filament\Navigation\MenuItem;
use App\Http\Middleware\SetLocale;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('BondoK')
            ->brandLogo(asset('images/bondok36.png'))
            ->darkModeBrandLogo(asset('images/bondik.png'))
            ->favicon(asset('images/favicon.ico'))
            ->colors([
                'primary' => [
                    '50'  => '#000000ff',
                    '100' => '#000000ff',
                    '500' => '#FFC107',
                    '600' => '#ffa600ff',
                ],
            ])
            ->sidebarCollapsibleOnDesktop()
            ->topNavigation(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
                       ->userMenuItems([
    MenuItem::make()
        ->label(fn () => app()->getLocale() === 'ar' ? 'English' : 'العربية')
        ->url(fn () => route('language.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar']))
        ->icon('heroicon-o-language')
])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                SalesChart::class,
                LatestOrders::class,
                BestSellingProducts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
