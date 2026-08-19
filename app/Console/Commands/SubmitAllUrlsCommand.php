<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Console\Command;

class SubmitAllUrlsCommand extends Command
{
    protected $signature = 'seo:submit-all
                            {--type=all : Gönderilecek tip: all, categories, products, brands, blog, pages}
                            {--limit=100 : Maksimum URL sayısı}
                            {--sync : Kuyruk kullanmadan hemen gönder}';

    protected $description = 'Tüm aktif URL\'leri IndexNow + Google Indexing API ile toplu bildirir';

    public function handle(UrlIndexingNotifier $notifier): int
    {
        if (! $notifier->isActive()) {
            $this->warn('IndexNow veya Google Indexing etkin değil. Panelden IndexNow ayarlarını kontrol edin.');

            return self::FAILURE;
        }

        $type = $this->option('type');
        $limit = max(1, (int) $this->option('limit'));
        $urls = collect();

        $urls->push(url('/'));

        if (in_array($type, ['all', 'categories'])) {
            Category::where('active', true)->orderBy('sort_order')->get()
                ->each(fn (Category $c) => $urls->push($c->storefrontUrl()));
            $this->info('Kategoriler eklendi: ' . Category::where('active', true)->count());
        }

        if (in_array($type, ['all', 'products'])) {
            Product::query()->active()->orderByDesc('updated_at')->limit($limit)
                ->each(fn (Product $p) => $urls->push(route('products.show', $p, absolute: true)));
            $this->info('Ürünler eklendi (limit: ' . $limit . ')');
        }

        if (in_array($type, ['all', 'brands'])) {
            Brand::where('active', true)->get()
                ->each(fn (Brand $b) => $urls->push(route('brands.show', $b, absolute: true)));
            $this->info('Markalar eklendi.');
        }

        if (in_array($type, ['all', 'blog'])) {
            BlogPost::published()->get()
                ->each(fn (BlogPost $p) => $urls->push(route('blog.show', $p, absolute: true)));
            $this->info('Blog yazıları eklendi.');
        }

        if (in_array($type, ['all', 'pages'])) {
            Page::where('published', true)->get()
                ->each(fn (Page $p) => $urls->push(route('pages.show', $p, absolute: true)));
            $this->info('Sayfalar eklendi.');
        }

        $urls = $urls->filter()->unique()->values();
        $this->info("Toplam {$urls->count()} URL gönderilecek.");

        if ($urls->isEmpty()) {
            return self::SUCCESS;
        }

        $batches = $urls->chunk(100);
        foreach ($batches as $i => $batch) {
            $notifier->submit($batch->values()->all(), queue: ! $this->option('sync'));
            $this->info('Batch ' . ($i + 1) . ': ' . $batch->count() . ' URL gönderildi.');
        }

        $this->info('Tamamlandı!');

        return self::SUCCESS;
    }
}
