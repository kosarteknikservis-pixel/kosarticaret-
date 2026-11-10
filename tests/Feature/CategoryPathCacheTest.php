<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryPathCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function nested_storefront_url_uses_full_slug_path(): void
    {
        $root = Category::query()->create([
            'slug' => 'ust',
            'name' => 'Üst',
            'active' => true,
        ]);

        $middle = Category::query()->create([
            'slug' => 'orta',
            'name' => 'Orta',
            'parent_id' => $root->id,
            'active' => true,
        ]);

        $leaf = Category::query()->create([
            'slug' => 'alt',
            'name' => 'Alt',
            'parent_id' => $middle->id,
            'active' => true,
        ]);

        $this->assertSame(
            url('/kategoriler/ust/orta/alt'),
            $leaf->storefrontUrl()
        );
    }

    #[Test]
    public function saving_middle_slug_updates_child_storefront_path(): void
    {
        $root = Category::query()->create([
            'slug' => 'ust',
            'name' => 'Üst',
            'active' => true,
        ]);

        $middle = Category::query()->create([
            'slug' => 'orta',
            'name' => 'Orta',
            'parent_id' => $root->id,
            'active' => true,
        ]);

        $leaf = Category::query()->create([
            'slug' => 'alt',
            'name' => 'Alt',
            'parent_id' => $middle->id,
            'active' => true,
        ]);

        $this->assertSame(url('/kategoriler/ust/orta/alt'), $leaf->storefrontUrl());

        $middle->slug = 'orta-yeni';
        $middle->save();

        $leaf->refresh();

        $this->assertSame(
            url('/kategoriler/ust/orta-yeni/alt'),
            $leaf->storefrontUrl()
        );
    }

    #[Test]
    public function slug_path_map_skips_parent_cycles_without_error(): void
    {
        $a = Category::query()->create([
            'slug' => 'dongu-a',
            'name' => 'Döngü A',
            'active' => true,
        ]);

        $b = Category::query()->create([
            'slug' => 'dongu-b',
            'name' => 'Döngü B',
            'parent_id' => $a->id,
            'active' => true,
        ]);

        Category::query()->whereKey($a->id)->update(['parent_id' => $b->id]);
        Cache::forget('category.slug_path_map');

        $map = Category::slugPathMap();

        $this->assertIsArray($map);
        $this->assertArrayNotHasKey($a->id, $map);
        $this->assertArrayNotHasKey($b->id, $map);
    }
}
