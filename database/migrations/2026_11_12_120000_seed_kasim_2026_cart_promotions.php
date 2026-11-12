<?php

use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $endsAt = PromotionService::kasim2026EndsAt()->utc();
        $startsAt = now('Europe/Istanbul')->startOfDay()->utc();

        foreach (PromotionService::kasim2026Tiers() as $index => $tier) {
            $name = PromotionService::KASIM_2026_NAME_PREFIX.$tier['label'];

            Promotion::query()->updateOrCreate(
                ['name' => $name],
                [
                    'type' => Promotion::TYPE_FIXED,
                    'value' => $tier['discount'],
                    'min_cart' => $tier['min'],
                    'auto_apply' => true,
                    'active' => true,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'priority' => 100 + $index,
                ],
            );
        }

        Cache::forget('campaign.kasim_2026.active');
    }

    public function down(): void
    {
        Promotion::query()
            ->where('name', 'like', PromotionService::KASIM_2026_NAME_PREFIX.'%')
            ->delete();

        Cache::forget('campaign.kasim_2026.active');
    }
};
