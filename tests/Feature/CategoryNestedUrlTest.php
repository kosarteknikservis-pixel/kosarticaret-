<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryNestedUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_category_uses_nested_url_and_shows_only_direct_children(): void
    {
        $root = Category::query()->create([
            'slug' => 'su-pompaları',
            'name' => 'Su Pompaları',
            'active' => true,
        ]);

        $child = Category::query()->create([
            'slug' => 'jet-pompa',
            'name' => 'Jet Pompalar',
            'parent_id' => $root->id,
            'active' => true,
        ]);

        $sibling = Category::query()->create([
            'slug' => 'dalgic-pompalar',
            'name' => 'Dalgıç Pompalar',
            'parent_id' => $root->id,
            'active' => true,
        ]);

        Product::query()->create([
            'slug' => 'jet-urun',
            'sku' => 'J-1',
            'name' => 'Jet Ürün',
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
        ])->categories()->sync([$child->id]);

        Product::query()->create([
            'slug' => 'dalgic-urun',
            'sku' => 'D-1',
            'name' => 'Dalgıç Ürün',
            'price' => 200,
            'stock' => 1,
            'is_active' => true,
        ])->categories()->sync([$sibling->id]);

        $this->get($root->storefrontUrl())
            ->assertOk()
            ->assertDontSee('Alt kategoriler', false)
            ->assertDontSee('Jet Ürün', false)
            ->assertDontSee('Dalgıç Ürün', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('shop-mega-panel__child', false)
            ->assertSee($child->storefrontUrl(), false);

        $this->get($child->storefrontUrl())
            ->assertOk()
            ->assertSee('Jet Ürün', false)
            ->assertDontSee('Dalgıç Ürün', false);

        $this->get('/kategoriler/jet-pompa')
            ->assertRedirect($child->storefrontUrl());
    }
}
