<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$map = [
    1 => 'sumak-pompa-marka-rehberi',
    2 => 'sanayi-tipi-vantilator-secimi-rehberi',
    3 => 'dalgic-pompa-nedir-ne-ise-yarar-nasil-secilir',
    4 => 'sicak-su-sirkulasyon-pompasi-secimi',
    5 => 'hidrofor-fiyatlari-2026-ev-apartman',
];

foreach ($map as $brief => $slug) {
    $p = App\Models\BlogPost::query()->where('slug', $slug)->first();
    if (! $p) {
        echo "Brief {$brief}: MISSING slug={$slug}\n";
        continue;
    }
    echo "Brief {$brief}: {$slug}\n";
    echo "  title: {$p->title}\n";
    echo '  published: '.((int) $p->published)."\n";
    echo '  published_at: '.($p->published_at?->format('Y-m-d') ?? '-')."\n";
    echo '  updated_at: '.$p->updated_at->format('Y-m-d')."\n";
    echo '  url: /blog/'.$p->slug."\n\n";
}
