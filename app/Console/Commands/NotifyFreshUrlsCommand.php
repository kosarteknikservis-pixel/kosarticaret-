<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Console\Command;

class NotifyFreshUrlsCommand extends Command
{
    protected $signature = 'seo:notify-fresh
                            {--hours=24 : Kaç saat içindeki güncellemeler bildirilsin}
                            {--sync : Kuyruk kullanmadan hemen gönder}';

    protected $description = 'Son güncellenen ürün, kategori, marka, sayfa ve blog URL’lerini IndexNow ile bildirir';

    public function handle(UrlIndexingNotifier $notifier): int
    {
        if (! $notifier->isActive()) {
            $this->warn('IndexNow veya Google Indexing etkin değil.');

            return self::SUCCESS;
        }

        $hours = max(1, (int) $this->option('hours'));
        $since = now()->subHours($hours);
        $urls = collect();

        Product::query()->active()->where('updated_at', '>=', $since)->orderByDesc('updated_at')->limit(80)
            ->each(fn (Product $product) => $urls->push(route('products.show', $product, absolute: true)));

        Category::query()->where('active', true)->where('updated_at', '>=', $since)->orderByDesc('updated_at')->limit(40)
            ->each(fn (Category $category) => $urls->push($category->storefrontUrl()));

        Brand::query()->where('active', true)->where('updated_at', '>=', $since)->orderByDesc('updated_at')->limit(20)
            ->each(fn (Brand $brand) => $urls->push(route('brands.show', $brand, absolute: true)));

        BlogPost::published()->where('updated_at', '>=', $since)->orderByDesc('updated_at')->limit(20)
            ->each(fn (BlogPost $post) => $urls->push(route('blog.show', $post, absolute: true)));

        Page::query()->where('published', true)->where('updated_at', '>=', $since)->orderByDesc('updated_at')->limit(20)
            ->each(fn (Page $page) => $urls->push(route('pages.show', $page, absolute: true)));

        $urls = $urls->filter()->unique()->take(100)->values();

        if ($urls->isEmpty()) {
            $this->info('Bildirilecek yeni URL yok.');

            return self::SUCCESS;
        }

        $notifier->submit($urls->all(), queue: ! $this->option('sync'));
        $this->info($urls->count().' URL IndexNow kuyruğuna alındı.');

        return self::SUCCESS;
    }
}
