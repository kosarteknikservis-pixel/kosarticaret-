<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Observers\BlogPostObserver;
use App\Observers\ProductObserver;
use App\Support\Seo;
use App\Models\NavigationItem;
use App\Models\ProductReview;
use App\Services\CartService;
use App\Services\FavoriteService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            $maxAttempts = app()->environment('local') ? 30 : 5;

            return Limit::perMinute($maxAttempts)->by($request->ip());
        });

        if ($this->app->environment('production')) {
            $root = rtrim((string) config('app.url'), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
            }
            URL::forceScheme('https');
        }

        Route::bind('category', function (string $value): Category {
            $category = Category::resolveFromStorefrontPath($value);

            if ($category === null && ! str_contains($value, '/')) {
                $category = Category::query()
                    ->where('slug', $value)
                    ->where('active', true)
                    ->first();
            }

            if ($category === null) {
                abort(404);
            }

            return $category;
        });

        View::composer('errors::404', function ($view): void {
            $view->with(Seo::noIndexMeta());
        });

        View::composer('layouts.shop', function ($view) {
            $view->with([
                'menuCategories' => Category::forStorefrontMenu()->get(),
                'headerNavItems' => NavigationItem::active('header')->get(),
                'footerNavItems' => NavigationItem::active('footer')->get(),
                'cartCount' => app(CartService::class)->count(),
                'favoriteCount' => app(FavoriteService::class)->count(),
            ]);
        });

        View::composer(['layouts.admin', 'admin.partials.sidebar-nav'], function ($view) {
            $view->with([
                'pendingReviews' => ProductReview::query()->where('approved', false)->count(),
                'unreadContactMessages' => ContactMessage::query()->whereNull('read_at')->count(),
            ]);
        });

        BlogPost::observe(BlogPostObserver::class);
        Product::observe(ProductObserver::class);
    }
}
