<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use App\Services\CollectionMatcher;
use App\Support\ProductSpecs;
use App\Support\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_does_not_change_stored_specs_or_the_visible_table(): void
    {
        [$category, $collection] = $this->hpCollection();
        $product = $this->product($category, ['Motor Gücü' => '1 HP', 'Debi' => '3 m3/h'], 'eslesen');
        $before = $product->specs;
        $rows = ProductSpecs::rows($before)->all();

        app(CollectionMatcher::class)->syncProduct($product->fresh(['categories']));

        $fresh = $product->fresh();
        $this->assertSame($before, $fresh->specs);
        $this->assertEquals($rows, ProductSpecs::rows($fresh->specs)->all());
        $this->assertDatabaseHas('collection_products', [
            'collection_id' => $collection->id,
            'product_id' => $product->id,
            'source' => 'rule',
        ]);
    }

    public function test_kw_map_includes_only_the_approved_pairs(): void
    {
        [$category, $collection] = $this->hpCollection();
        $mapped = $this->product($category, ['Güç' => '0,75 kW'], 'kw-075');
        $outside = $this->product($category, ['Güç (kW)' => '1,6 kW'], 'kw-16');
        $input = $this->product($category, ['Çekilen Güç' => '0,75 kW'], 'p1');
        $otherHp = $this->product($category, ['Motor Gücü' => '2.2 HP (1.5 kW)'], 'hp-22');
        $namedOnly = $this->product($category, ['Motor Gücü' => '2 HP'], 'ad-1-hp');
        $namedOnly->update(['name' => '1 HP görünen ama 2 HP olan pompa']);

        $matcher = app(CollectionMatcher::class);
        foreach ([$mapped, $outside, $input, $otherHp, $namedOnly] as $product) {
            $matcher->syncProduct($product->fresh(['categories']));
        }

        $this->assertDatabaseHas('collection_products', ['product_id' => $mapped->id, 'source' => 'rule']);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $outside->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $input->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $otherHp->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $namedOnly->id]);
    }

    public function test_single_mains_voltage_counts_as_monofaze_and_split_voltage_does_not(): void
    {
        $category = Category::query()->create([
            'name' => 'Dalgıç test',
            'slug' => 'dalgic-test',
            'active' => true,
            'sort_order' => 0,
        ]);
        $collection = Collection::query()->create([
            'name' => 'Monofaze Dalgıç',
            'slug' => 'monofaze-dalgic-test',
            'status' => Collection::STATUS_DRAFT,
            'rules' => ['phase' => 'monofaze'],
            'category_id' => $category->id,
        ]);
        $mono = $this->product($category, ['Voltaj' => '220 V'], 'mono-220');
        $split = $this->product($category, ['Voltaj' => '220/380'], 'split');
        $three = $this->product($category, ['Voltaj' => '380 V'], 'tri-380');

        $matcher = app(CollectionMatcher::class);
        foreach ([$mono, $split, $three] as $product) {
            $matcher->syncProduct($product->fresh(['categories']));
        }

        $this->assertDatabaseHas('collection_products', [
            'collection_id' => $collection->id,
            'product_id' => $mono->id,
            'source' => 'rule',
        ]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $split->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $three->id]);
    }

    public function test_draft_page_is_closed_and_absent_from_the_sitemap(): void
    {
        [$category, $collection] = $this->hpCollection();
        $this->product($category, ['Motor Gücü' => '1 HP'], 'taslak-urun');

        $this->get('/koleksiyon/'.$collection->slug)->assertNotFound();

        $locs = array_column(SitemapGenerator::indexEntries(), 'loc');
        $this->assertFalse(collect($locs)->contains(fn ($loc) => str_contains((string) $loc, 'collections')));
        $this->assertSame([], SitemapGenerator::chunkUrls('collections')->all());
    }

    public function test_index_requires_six_products_and_page_two_is_noindex(): void
    {
        [$category, $collection] = $this->hpCollection();
        $matcher = app(CollectionMatcher::class);
        for ($i = 1; $i <= 13; $i++) {
            $product = $this->product($category, ['Motor Gücü' => '1 HP'], 'hp-'.$i);
            $matcher->syncProduct($product->fresh(['categories']));
        }

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->put(route('admin.collections.update', $collection), [
                'name' => $collection->name,
                'slug' => $collection->slug,
                'status' => Collection::STATUS_INDEX,
                'category_id' => $category->id,
                'motor_hp' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(Collection::STATUS_INDEX, $collection->fresh()->status);

        $page = $this->get('/koleksiyon/'.$collection->slug.'?page=2');
        $page->assertOk();
        $page->assertSee('noindex, follow', false);
        $page->assertSee('page=2', false);

        $urls = SitemapGenerator::chunkUrls('collections')->all();
        $this->assertTrue(collect($urls)->contains(
            fn ($row) => str_contains($row['loc'], $collection->slug) && ! str_contains($row['loc'], 'page=')
        ));
    }

    public function test_index_is_rejected_below_six_products(): void
    {
        [$category, $collection] = $this->hpCollection();
        $product = $this->product($category, ['Motor Gücü' => '1 HP'], 'tek');
        app(CollectionMatcher::class)->syncProduct($product->fresh(['categories']));
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.collections.edit', $collection))
            ->put(route('admin.collections.update', $collection), [
                'name' => $collection->name,
                'slug' => $collection->slug,
                'status' => Collection::STATUS_INDEX,
                'category_id' => $category->id,
                'motor_hp' => 1,
            ])
            ->assertRedirect(route('admin.collections.edit', $collection))
            ->assertSessionHasErrors('status');

        $this->assertSame(Collection::STATUS_DRAFT, $collection->fresh()->status);
        $this->get('/koleksiyon/'.$collection->slug)->assertNotFound();
    }

    public function test_preview_lists_every_match_when_names_sort_ahead_of_lower_ids(): void
    {
        [$category, $collection] = $this->hpCollection();
        $lateName = $this->product($category, ['Motor Gücü' => '1 HP'], 'late');
        $lateName->update(['name' => 'C sirali hidrofor']);
        $this->product($category, ['Motor Gücü' => '1 HP'], 'a')->update(['name' => 'A sirali hidrofor']);
        $this->product($category, ['Motor Gücü' => '1 HP'], 'b')->update(['name' => 'B sirali hidrofor']);

        $matcher = app(CollectionMatcher::class);
        $matcher->syncAll();
        $preview = $matcher->preview($collection->fresh(), 2);

        $this->assertCount(3, $preview['matched']);
        $this->assertSame(
            ['A sirali hidrofor', 'B sirali hidrofor', 'C sirali hidrofor'],
            array_column($preview['matched'], 'name')
        );
    }

    public function test_hidrofor_hp_tag_keeps_only_a_single_pump(): void
    {
        [$category, $collection] = $this->hpCollection();
        $single = $this->product($category, ['Motor Gücü' => '1 HP'], 'tek-pompa');
        $twin = $this->product($category, ['Motor Gücü' => '1 HP'], 'cift');
        $twin->update(['name' => 'Sumak SHM6 B Çift Pompalı Hidrofor']);
        $set = $this->product($category, ['Motor Gücü' => '2 × 1 HP'], 'iki-motor');
        $set->update(['name' => 'Sumak SMINOX12B Hidrofor']);
        $unverified = $this->product($category, ['Motor Gücü' => '1 HP'], 'harf');
        $unverified->update(['name' => 'Sumak SHM6 B 100/6 Hidrofor', 'description' => 'Su basıncı sağlar.']);
        $three = Collection::query()->create([
            'name' => '3 HP test',
            'slug' => '3-hp-test-'.substr(md5($category->slug), 0, 8),
            'status' => Collection::STATUS_DRAFT,
            'rules' => ['motor_hp' => 3],
            'category_id' => $category->id,
        ]);
        $paired = $this->product($category, ['Güç' => '3 HP (2.2 kW)'], 'uyumlu');
        $paired->update(['name' => 'Tek pompalı 3 HP hidrofor']);
        $conflict = $this->product($category, ['Güç' => '3 HP (3 kW)'], 'celiski');
        $conflict->update(['name' => 'Winpo WNP2 VM 2-7M Hidrofor']);

        $matcher = app(CollectionMatcher::class);
        foreach ([$single, $twin, $set, $unverified, $paired, $conflict] as $product) {
            $matcher->syncProduct($product->fresh(['categories']));
        }

        $this->assertDatabaseHas('collection_products', ['product_id' => $single->id, 'collection_id' => $collection->id, 'source' => 'rule']);
        $this->assertDatabaseHas('collection_products', ['product_id' => $unverified->id, 'source' => 'rule']);
        $this->assertDatabaseHas('collection_products', ['product_id' => $paired->id, 'collection_id' => $three->id, 'source' => 'rule']);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $twin->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $set->id]);
        $this->assertDatabaseMissing('collection_products', ['product_id' => $conflict->id]);
    }

    public function test_dalgic_tags_skip_bare_motors_and_keep_pump_sets(): void
    {
        $category = Category::query()->create([
            'name' => 'Dalgıç test',
            'slug' => 'dalgic-test-motor',
            'active' => true,
            'sort_order' => 0,
        ]);
        $collection = Collection::query()->create([
            'name' => '1 HP dalgıç',
            'slug' => '1-hp-dalgic-motor-test',
            'status' => Collection::STATUS_DRAFT,
            'rules' => ['motor_hp' => 1],
            'category_id' => $category->id,
        ]);
        $mono = Collection::query()->create([
            'name' => 'Monofaze dalgıç',
            'slug' => 'monofaze-motor-test',
            'status' => Collection::STATUS_DRAFT,
            'rules' => ['phase' => 'monofaze'],
            'category_id' => $category->id,
        ]);
        $motor = $this->product($category, ['Motor Gücü' => '1 HP', 'Voltaj' => '220 V'], 'sm10');
        $motor->update(['name' => 'Sumak 4SM10 Dalgıç Pompa Motoru']);
        $pedrollo = $this->product($category, ['Motor Gücü' => '1 HP', 'Voltaj' => '220 V'], 'pdm');
        $pedrollo->update(['name' => 'Pedrollo 4 PDm Derin Kuyu Dalgıç Motoru']);
        $withMotor = $this->product($category, ['Motor Gücü' => '1 HP', 'Voltaj' => '220 V'], 'sr');
        $withMotor->update(['name' => 'Pedrollo 4 SR Dalgıç Pompa Motorlu']);
        $keson = $this->product($category, ['Motor Gücü' => '1 HP', 'Voltaj' => '220 V'], 'skm');
        $keson->update(['name' => 'Winpo 4SKM Keson Kuyu Dalgıç Pompa']);
        $blade = $this->product($category, ['Motor Gücü' => '1 HP', 'Voltaj' => '220 V'], 'trm');
        $blade->update(['name' => 'Pedrollo TRm Foseptik Dalgıç Pompa']);

        $matcher = app(CollectionMatcher::class);
        foreach ([$motor, $pedrollo, $withMotor, $keson, $blade] as $product) {
            $matcher->syncProduct($product->fresh(['categories']));
        }

        foreach ([$collection, $mono] as $tag) {
            $this->assertDatabaseMissing('collection_products', [
                'collection_id' => $tag->id,
                'product_id' => $motor->id,
            ]);
            $this->assertDatabaseMissing('collection_products', [
                'collection_id' => $tag->id,
                'product_id' => $pedrollo->id,
            ]);
            $this->assertDatabaseHas('collection_products', [
                'collection_id' => $tag->id,
                'product_id' => $withMotor->id,
                'source' => 'rule',
            ]);
            $this->assertDatabaseHas('collection_products', [
                'collection_id' => $tag->id,
                'product_id' => $keson->id,
                'source' => 'rule',
            ]);
            $this->assertDatabaseHas('collection_products', [
                'collection_id' => $tag->id,
                'product_id' => $blade->id,
                'source' => 'rule',
            ]);
        }
    }

    public function test_monofaze_page_groups_by_real_category_and_page_two_stays_noindex(): void
    {
        $parent = Category::query()->create([
            'name' => 'Dalgıç test',
            'slug' => 'dalgic-grup-test',
            'active' => true,
            'sort_order' => 0,
        ]);
        $types = [
            'derin' => ['Derin Kuyu Dalgıç Pompa', 'derin-kuyu-dalgic-pompa'],
            'keson' => ['Keson Kuyu Pompa', 'keson-kuyu-pompa'],
            'foseptik' => ['Foseptik Dalgıç Pompa', 'foseptik-dalgic-pompa'],
            'bicak' => ['Bıçaklı Dalgıç Pompa', 'bicakli-dalgic-pompa'],
            'drenaj' => ['Drenaj Dalgıç Pompa', 'drenaj-dalgic-pompa'],
        ];
        $categories = [];
        foreach ($types as $key => [$name, $slug]) {
            $categories[$key] = Category::query()->create([
                'name' => $name,
                'slug' => $slug,
                'parent_id' => $parent->id,
                'active' => true,
                'sort_order' => 0,
            ]);
        }

        $collection = Collection::query()->create([
            'name' => 'Monofaze dalgıç',
            'slug' => 'monofaze-grup-test',
            'status' => Collection::STATUS_INDEX,
            'rules' => ['phase' => 'monofaze'],
            'category_id' => $parent->id,
        ]);

        $keson = $this->product($categories['derin'], ['Voltaj' => '220 V'], 'keson');
        $keson->categories()->attach($categories['keson']->id);
        $keson->update(['name' => 'Keson pompa']);
        $well = $this->product($categories['derin'], ['Voltaj' => '230 V'], 'derin');
        $well->update(['name' => 'Derin pompa']);
        $blade = $this->product($categories['foseptik'], ['Voltaj' => '220 V'], 'bicak');
        $blade->categories()->attach($categories['bicak']->id);
        $blade->update(['name' => 'Bicakli pompa']);
        for ($i = 1; $i <= 10; $i++) {
            $row = $this->product($categories['drenaj'], ['Voltaj' => '220 V'], 'drenaj-'.$i);
            $row->update(['name' => 'Drenaj pompa '.$i]);
        }

        $matcher = app(CollectionMatcher::class);
        $matcher->syncAll();

        $page = $this->get('/koleksiyon/monofaze-grup-test');
        $page->assertOk();
        $html = $page->getContent();
        $catalog = substr($html, (int) strpos($html, 'shop-catalog-products'));
        $derinAt = strpos($catalog, '<h2 class="shop-catalog-group">Derin Kuyu Dalgıç Pompa</h2>');
        $kesonAt = strpos($catalog, '<h2 class="shop-catalog-group">Keson Kuyu Pompa</h2>');
        $bladeAt = strpos($catalog, '<h2 class="shop-catalog-group">Bıçaklı Dalgıç Pompa</h2>');
        $drainAt = strpos($catalog, '<h2 class="shop-catalog-group">Drenaj Dalgıç Pompa</h2>');
        $this->assertNotFalse($derinAt);
        $this->assertNotFalse($kesonAt);
        $this->assertNotFalse($bladeAt);
        $this->assertTrue($derinAt < $kesonAt && $kesonAt < $bladeAt && $bladeAt < $drainAt);
        $kesonUrl = strpos($catalog, '/urun/koleksiyon-keson');
        $this->assertNotFalse($kesonUrl);
        $this->assertGreaterThan($kesonAt, $kesonUrl);
        $this->assertLessThan($bladeAt, $kesonUrl);
        $this->assertSame(0, substr_count($catalog, 'Foseptik Dalgıç Pompa'));

        $next = $this->get('/koleksiyon/monofaze-grup-test?page=2');
        $next->assertOk();
        $next->assertSee('noindex, follow', false);
        $next->assertSee('page=2', false);
        $next->assertSee('Drenaj Dalgıç Pompa', false);
    }

    /** @param  array<string, string>  $specs */
    private function product(Category $category, array $specs, string $sku): Product
    {
        $product = Product::query()->create([
            'slug' => 'koleksiyon-'.$sku,
            'sku' => 'KOL-'.$sku,
            'name' => 'Koleksiyon '.$sku,
            'price' => 1000 + (crc32($sku) % 500),
            'stock' => 2,
            'is_active' => true,
            'specs' => $specs,
        ]);
        $product->categories()->attach($category->id);

        return $product;
    }

    /** @return array{0: Category, 1: Collection} */
    private function hpCollection(): array
    {
        $category = Category::query()->create([
            'name' => 'Hidrofor test',
            'slug' => 'hidrofor-test-'.uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);
        $collection = Collection::query()->create([
            'name' => '1 HP test',
            'slug' => '1-hp-test-'.substr(md5($category->slug), 0, 8),
            'status' => Collection::STATUS_DRAFT,
            'rules' => ['motor_hp' => 1],
            'category_id' => $category->id,
        ]);

        return [$category, $collection];
    }
}
