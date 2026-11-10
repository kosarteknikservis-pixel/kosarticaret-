<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchSuggestEscapeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function percent_in_query_is_treated_literally_not_as_like_wildcard(): void
    {
        Product::query()->create([
            'slug' => 'indirim-50-yuzde',
            'sku' => 'P-50PCT',
            'name' => 'Pompa Indirim 50% Ozel',
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::query()->create([
            'slug' => 'diger-pompa',
            'sku' => 'P-OTHER',
            'name' => 'Diger Pompa Model',
            'price' => 200,
            'stock' => 5,
            'is_active' => true,
        ]);

        $withPercent = $this->getJson('/ara/oneri?q='.rawurlencode('50%'));
        $withPercent->assertOk()->assertJsonPath('ok', true);

        $names = collect($withPercent->json('results'))
            ->where('type', 'product')
            ->pluck('name')
            ->all();

        $this->assertContains('Pompa Indirim 50% Ozel', $names);
        $this->assertNotContains('Diger Pompa Model', $names);

        $onlyPercent = $this->getJson('/ara/oneri?q='.rawurlencode('%'));
        $onlyPercent->assertOk()->assertJsonPath('ok', true);
        $this->assertSame([], $onlyPercent->json('results'));
    }
}
