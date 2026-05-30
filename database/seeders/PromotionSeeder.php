<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        Promotion::query()->updateOrCreate(
            ['name' => '1000 TL üzeri ücretsiz kargo'],
            [
                'type' => Promotion::TYPE_FREE_SHIPPING,
                'min_cart' => 1000,
                'auto_apply' => true,
                'active' => true,
                'priority' => 10,
            ],
        );

        Promotion::query()->updateOrCreate(
            ['name' => '2 al 1 öde (en ucuz bedava)'],
            [
                'type' => Promotion::TYPE_BUY_X_GET_Y,
                'buy_qty' => 2,
                'free_qty' => 1,
                'auto_apply' => true,
                'active' => true,
                'priority' => 5,
            ],
        );
    }
}
