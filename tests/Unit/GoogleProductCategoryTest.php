<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Support\GoogleProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function child_category_wins_over_parent(): void
    {
        $parent = $this->category('vantilatorler', 'Vantilatörler');
        $child = $this->category('dikey-ev-tipi-isiticilar', 'Ev ısıtıcı', $parent->id);

        $this->assertSame(611, GoogleProductCategory::forCategory($child));
        $this->assertSame(608, GoogleProductCategory::forCategory($parent));
        $this->assertNotSame(
            GoogleProductCategory::forCategory($parent),
            GoogleProductCategory::forCategory($child)
        );
    }

    #[Test]
    public function mapped_slugs_resolve_and_banned_ids_are_absent(): void
    {
        $cases = [
            'derin-kuyu-dalgic-pompa' => 500100,
            'foseptik-dalgic-pompa' => 500102,
            'on-filtreli-havuz-pompasi' => 500098,
            'hidroforlar' => 500097,
            'jet-pompalar-derinden-emisli' => 500101,
            'paslanmaz-pompalar-kimyasal' => 500096,
            'sanayi-tipi-vantilator' => 608,
            'hidromat' => 499932,
            'duvar-tipi-dis-mekan-isiticilar' => 2649,
            'dikey-ev-tipi-isiticilar' => 611,
        ];

        foreach ($cases as $slug => $id) {
            $category = $this->category($slug, $slug);
            $resolved = GoogleProductCategory::forCategory($category);
            $this->assertSame($id, $resolved, $slug);
            $this->assertNotContains($resolved, [1869, 1795, 127]);
        }
    }

    #[Test]
    public function product_rules_override_the_category(): void
    {
        $fans = $this->category('sanayi-tipi-vantilator', 'Sanayi');
        $kademeli = $this->category('dikey-kademeli-pompalar', 'Kademeli');
        $horizontal = $this->category('norm-tipi-yatay-kademeli', 'Yatay');
        $temiz = $this->category('temiz-su-dalgic-pompasi', 'Temiz su');
        $keson = $this->category('keson-kuyu-pompa', 'Keson');
        $elektrik = $this->category('elektrik-ve-aydinlatma', 'Elektrik');
        $pumps = $this->category('su-pompalari', 'Su pompaları');

        $this->assertSame(2535, $this->idFor('KSV-600', 'Koşar KSV-600 Ayaklı', $fans));
        $this->assertSame(8090, $this->idFor('KSV-D600', 'Koşar KSV-D600 Duvar Tipi', $fans));
        $this->assertSame(8090, $this->idFor('KSV-DK750', 'Koşar KSV-DK750 Uzaktan Kumandalı', $fans));
        $this->assertSame(608, $this->idFor('KSV-X', 'Koşar sanayi vantilatör', $fans));

        $this->assertSame(500097, $this->idFor('SHT244', 'Sumak SHT 24/4 Dik Milli Kademeli Pompa', $kademeli));
        $this->assertSame(500097, $this->idFor('SYMTP', 'Sumak SYMTP 5 Bar Yatay Kademeli', $horizontal));
        $this->assertSame(500097, $this->idFor('4CRM80N', 'Pedrollo 4CRm 80 - N Kademeli Pompa 52 mss', $horizontal));
        $this->assertSame(500101, $this->idFor('SYT32', 'Sumak SYT 32/2 Akuple Yatay Milli Kademeli Pompa', $horizontal));

        $this->assertSame(500100, $this->idFor('WELL4', '4 inç derin kuyu dalgıç', $temiz));
        $this->assertSame(500101, $this->idFor('TOP2', 'Pedrollo TOP 2 Drenaj Dalgıç', $temiz));
        $this->assertNull($this->idFor('DIGER', 'Sumak başka temiz su dalgıç', $temiz));
        foreach ([
            'SDF123' => 'Sumak SDF12/3 Temiz Su Dalgıç Pompa',
            'SDF83' => 'Sumak SDF 8/3 Temiz Su Dalgıç Pompa',
            'SDF52' => 'Sumak SDF 5/2 Temiz Su Dalgıç Pompa',
            'SDF151' => 'Sumak SDF 15/1 Temiz Su Dalgıç Pompa',
            'SDF252-M' => 'Sumak SDF 25/2 Temiz Su Dalgıç Pompa Monofaze',
            'SDT252' => 'Sumak SDT 25/2 Temiz Su Dalgıç Pompa Trifaze',
        ] as $sku => $name) {
            $this->assertSame(500102, $this->idFor($sku, $name, $temiz), $sku);
        }

        $this->assertSame(500102, $this->idFor('TOPMULTI1', 'Pedrollo TOP MULTI 1 Drenaj', $keson));
        $this->assertSame(500100, $this->idFor('UP46', 'Pedrollo UP 4/6 Keson Kuyu Pompası', $keson));

        $this->assertSame(4485, $this->idFor('3012', 'Horoz Plastik Aspiratör', $elektrik));
        $this->assertSame(3006, $this->idFor('3025', 'Horoz 3 Watt Led Slim Panel', $elektrik));
        $this->assertSame(499932, $this->idFor('1562', 'Kaysu Hidrofor Basınç Şalteri', $pumps));
        $this->assertNull(GoogleProductCategory::forCategory($elektrik));
        $this->assertNull(GoogleProductCategory::forCategory($pumps));
    }

    private function category(string $slug, string $name, ?int $parentId = null): Category
    {
        return Category::query()->create([
            'slug' => $slug,
            'name' => $name,
            'parent_id' => $parentId,
            'active' => true,
        ]);
    }

    private function idFor(string $sku, string $name, Category $category): ?int
    {
        $product = Product::query()->create([
            'slug' => 'p-'.$sku,
            'sku' => $sku,
            'name' => $name,
            'price' => 100,
            'stock' => 2,
            'is_active' => true,
            'image' => 'products/test.jpg',
        ]);
        $product->categories()->attach($category->id);

        return GoogleProductCategory::forProduct($product->fresh('categories'));
    }
}
