<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\MetaSuggestion;
use App\Support\SlugHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function slug_helper_makes_unique_slug(): void
    {
        Product::query()->create([
            'slug' => 'test-urun',
            'name' => 'Test',
            'price' => 100,
            'stock' => 1,
        ]);

        $slug = SlugHelper::assign('products', null, 'Test Ürün', null);

        $this->assertSame('test-urun-2', $slug);
    }

    #[Test]
    public function meta_suggestion_returns_title_and_description(): void
    {
        $result = MetaSuggestion::suggest('product', [
            'name' => 'Dalgıç Pompa 3 İnç',
            'short_description' => 'Yüksek debili dalgıç pompa modeli.',
        ]);

        $this->assertNotEmpty($result['meta_title']);
        $this->assertGreaterThan(40, mb_strlen($result['meta_description']));
    }

    #[Test]
    public function admin_can_request_slug_via_api(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin)
            ->postJson(route('admin.ai.slug'), [
                'text' => 'Seaflo 24 Volt Hidrofor',
                'entity' => 'products',
            ])
            ->assertOk()
            ->assertJsonPath('slug', 'seaflo-24-volt-hidrofor');
    }

    #[Test]
    public function admin_can_request_local_meta_suggestion(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.ai.meta'), [
                'type' => 'category',
                'use_ai' => false,
                'context' => ['name' => 'Hidroforlar', 'description' => ''],
            ])
            ->assertOk()
            ->assertJsonStructure(['meta_title', 'meta_description']);
    }

    #[Test]
    public function generate_requires_openai_key(): void
    {
        $admin = User::query()->where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.ai.generate'), [
                'type' => 'product',
                'field' => 'description',
                'context' => ['name' => 'Test'],
            ])
            ->assertStatus(422);
    }
}
