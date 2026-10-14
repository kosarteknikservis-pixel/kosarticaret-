<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\PumpSelection\PumpRecommendationService;
use App\Services\SupportAssistant\SupportAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PumpDutyRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private Category $well;

    protected function setUp(): void
    {
        parent::setUp();

        $this->well = Category::query()->create([
            'slug' => 'derin-kuyu-dalgic-pompa',
            'name' => 'Derin Kuyu Dalgıç Pompa',
            'active' => true,
        ]);

        $this->pump('winpo-curve', 'Winpo WNP 2/15 Dalgıç Pompa', 'Winpo', [
            'Maksimum Basma Yüksekliği' => '103 metre',
            'Maksimum Su Basma Kapasitesi' => '3 ton/saat',
            'Elektrik' => '220 Volt Monofaze',
            '0.5 TON' => '103 m',
            '1 TON' => '96 m',
            '1.5 TON' => '89 m',
            '2 TON' => '80 m',
            '2.5 TON' => '66 m',
            '3 TON' => '52 m',
        ]);

        $this->pump('mono-90', 'Pedrollo Dalgıç Pompa 90 m', 'Pedrollo', [
            'Maksimum Basma Yüksekliği' => '90 metre',
            'Debi' => '4 m³/h',
            'Elektrik' => '220 Volt Monofaze',
        ]);

        $this->pump('tri-120', 'Sumak Trifaze 380V Dalgıç Pompa', 'Sumak', [
            'Maksimum Basma Yüksekliği' => '120 metre',
            'Debi' => '6 m³/h',
        ]);

        $this->pump('float-90', 'Sumak Şamandıralı Dalgıç Pompa', 'Sumak', [
            'Maksimum Basma Yüksekliği' => '90 metre',
            'Debi' => '6 m³/h',
            'Elektrik' => '220 Volt Monofaze',
        ]);
    }

    #[Test]
    public function household_well_uses_curve_head_and_skips_three_phase(): void
    {
        $names = array_column($this->recommend(), 'name');

        $this->assertNotContains('Winpo WNP 2/15 Dalgıç Pompa', $names);
        $this->assertNotContains('Sumak Trifaze 380V Dalgıç Pompa', $names);
        $this->assertContains('Pedrollo Dalgıç Pompa 90 m', $names);
    }

    #[Test]
    public function float_request_does_not_return_pumps_without_a_float(): void
    {
        $result = app(SupportAssistantTools::class)->execute('recommend_pump', [
            'application' => 'submersible_well',
            'depth' => 41,
            'usage' => 'household',
            'samandira' => true,
        ]);

        $names = array_column($result['onerilen_urunler'], 'ad');

        $this->assertSame(['Sumak Şamandıralı Dalgıç Pompa'], $names);
        $this->assertStringContainsString('şamandıra', $result['not']);
    }

    #[Test]
    public function float_request_with_no_match_does_not_reuse_the_other_pumps(): void
    {
        Product::query()->where('slug', 'float-90')->delete();

        $result = app(SupportAssistantTools::class)->execute('recommend_pump', [
            'application' => 'submersible_well',
            'depth' => 41,
            'usage' => 'household',
            'samandira' => true,
        ]);

        $this->assertSame([], $result['onerilen_urunler']);
        $this->assertStringContainsString('şamandıralı model yok', $result['not']);
    }

    /**
     * @param  array<string, string>  $specs
     */
    private function pump(string $slug, string $name, string $brand, array $specs): void
    {
        $brandModel = Brand::query()->firstOrCreate(
            ['slug' => strtolower($brand)],
            ['name' => $brand, 'active' => true],
        );

        $product = Product::query()->create([
            'slug' => $slug,
            'name' => $name,
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
            'brand_id' => $brandModel->id,
            'specs' => $specs,
        ]);
        $product->categories()->attach($this->well->id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recommend(): array
    {
        return app(PumpRecommendationService::class)->recommend('submersible_well', [
            'depth' => 41,
            'usage' => 'household',
        ])['products'];
    }
}
