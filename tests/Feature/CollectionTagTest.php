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
