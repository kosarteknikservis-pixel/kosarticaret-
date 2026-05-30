<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['label' => 'Kampanyalar', 'url' => '/urunler', 'location' => 'header', 'sort_order' => 10],
            ['label' => 'SSS', 'url' => '/sayfa/sss', 'location' => 'footer', 'sort_order' => 20],
            ['label' => 'Ön Bilgilendirme', 'url' => '/sayfa/on-bilgilendirme', 'location' => 'footer', 'sort_order' => 21],
        ];

        foreach ($items as $item) {
            NavigationItem::query()->updateOrCreate(
                ['label' => $item['label'], 'location' => $item['location']],
                $item,
            );
        }
    }
}
