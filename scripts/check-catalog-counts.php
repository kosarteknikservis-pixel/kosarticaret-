<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

$perPage = 12;
$total = Product::count();
$sumak = Brand::query()->where('slug', 'sumak')->first();
$pedrollo = Brand::query()->where('slug', 'pedrollo')->first();

echo "Toplam ürün: {$total}\n";
echo "Markasız: ".Product::query()->whereNull('brand_id')->count()."\n";

foreach ([$sumak, $pedrollo] as $brand) {
    if (! $brand) {
        continue;
    }
    $n = Product::query()->where('brand_id', $brand->id)->count();
    $pages = (int) ceil($n / $perPage);
    echo "{$brand->name} ({$brand->slug}): {$n} ürün, {$pages} sayfa (sayfa başı {$perPage})\n";
}

$topCats = Category::query()
    ->withCount('products')
    ->orderByDesc('products_count')
    ->limit(5)
    ->get(['id', 'name', 'slug']);

echo "\nEn çok ürünlü kategoriler:\n";
foreach ($topCats as $cat) {
    $pages = (int) ceil($cat->products_count / $perPage);
    echo "  {$cat->name}: {$cat->products_count} ürün, {$pages} sayfa\n";
}
