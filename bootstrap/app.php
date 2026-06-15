<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('marketplace:import-trendyol-orders --sync')
            ->everyTenMinutes()
            ->withoutOverlapping();

        $schedule->command('orders:send-payment-reminders')
            ->hourly()
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'odeme/paytr/callback',
            'odeme/iyzico/callback',
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\LegacyRedirect::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ShopMaintenanceMode::class,
            \App\Http\Middleware\CachePublicPages::class,
            \App\Http\Middleware\TrackStorefrontVisit::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin', 'admin/*', 'yonetim', 'yonetim/*')
                ? route('admin.login')
                : route('login');
        });
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'shop.customer' => \App\Http\Middleware\EnsureShopCustomer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
