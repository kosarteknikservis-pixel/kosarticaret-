<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveCategoryRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_child_redirects_to_active_parent(): void
    {
        $parent = Category::query()->create([
            'slug' => 'su-pompalari',
            'name' => 'Su Pompaları',
            'active' => true,
        ]);

        $child = Category::query()->create([
            'slug' => 'jet-pompalar',
            'name' => 'Jet Pompalar',
            'parent_id' => $parent->id,
            'active' => false,
        ]);

        $this->get($child->storefrontUrl())
            ->assertRedirect($parent->storefrontUrl())
            ->assertStatus(301);
    }

    public function test_inactive_leaf_skips_inactive_middle_and_redirects_to_active_root(): void
    {
        $root = Category::query()->create([
            'slug' => 'su-pompalari',
            'name' => 'Su Pompaları',
            'active' => true,
        ]);

        $middle = Category::query()->create([
            'slug' => 'dalgic-pompalar',
            'name' => 'Dalgıç Pompalar',
            'parent_id' => $root->id,
            'active' => false,
        ]);

        $leaf = Category::query()->create([
            'slug' => 'derin-kuyu',
            'name' => 'Derin Kuyu',
            'parent_id' => $middle->id,
            'active' => false,
        ]);

        $this->get($leaf->storefrontUrl())
            ->assertRedirect($root->storefrontUrl())
            ->assertStatus(301);
    }

    public function test_inactive_root_without_active_ancestor_returns_404(): void
    {
        $root = Category::query()->create([
            'slug' => 'pasif-kok',
            'name' => 'Pasif Kök',
            'active' => false,
        ]);

        $this->get($root->storefrontUrl())->assertNotFound();
    }

    public function test_active_category_still_returns_200(): void
    {
        $category = Category::query()->create([
            'slug' => 'aktif-kategori',
            'name' => 'Aktif Kategori',
            'active' => true,
        ]);

        $this->get($category->storefrontUrl())->assertOk();
    }

    public function test_inactive_child_flat_slug_redirects_to_active_parent(): void
    {
        $parent = Category::query()->create([
            'slug' => 'su-pompalari',
            'name' => 'Su Pompaları',
            'active' => true,
        ]);

        Category::query()->create([
            'slug' => 'jet-pompalar',
            'name' => 'Jet Pompalar',
            'parent_id' => $parent->id,
            'active' => false,
        ]);

        $this->get('/kategoriler/jet-pompalar')
            ->assertRedirect($parent->storefrontUrl())
            ->assertStatus(301);
    }
}
