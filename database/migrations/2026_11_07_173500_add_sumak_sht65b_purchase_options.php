<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;

return new class extends Migration
{
    private const HUB_SLUG = 'sumak-sht-65-b-dusey-milli-kademeli-hidrofor';

    private const HUB_SKU = 'SHT65B';

    private const GROUP = 'sumak-sht65b';

    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   sku: string,
     *   slug: string,
     *   price: float,
     *   power_kw: string,
     *   power_hp: string,
     *   weight_kg: ?int,
     *   flow: string,
     *   head: string,
     *   pressure: string,
     *   sort: int,
     *   is_hub: bool
     * }>
     */
    private function options(): array
    {
        return [
            [
                'key' => 'sht65-2b-30kw',
                'label' => 'SHT 65/2B 30KW',
                'sku' => 'SHT65B',
                'slug' => self::HUB_SLUG,
                'price' => 641190.00,
                'power_kw' => '2 x 30 kW',
                'power_hp' => '2 x 40 HP',
                'weight_kg' => null,
                'flow' => '80 m³/h',
                'head' => '150 - 90 mss',
                'pressure' => '6 - 7.5 bar',
                'sort' => 1,
                'is_hub' => true,
            ],
            [
                'key' => 'sht65-2b-37kw',
                'label' => 'SHT 65/2B 37KW',
                'sku' => 'SHT65-2B-37',
                'slug' => 'sumak-sht-65-2b-37kw-dusey-milli-kademeli-hidrofor',
                'price' => 652755.00,
                'power_kw' => '2 x 37 kW',
                'power_hp' => '2 x 50 HP',
                'weight_kg' => null,
                'flow' => '100 m³/h',
                'head' => '180 - 60 mss',
                'pressure' => '7 - 9.5 bar',
                'sort' => 2,
                'is_hub' => false,
            ],
            [
                'key' => 'sht65-3b-45kw',
                'label' => 'SHT 65/3B 45KW',
                'sku' => 'SHT65-3B-45',
                'slug' => 'sumak-sht-65-3b-45kw-dusey-milli-kademeli-hidrofor',
                'price' => 918850.00,
                'power_kw' => '2 x 45 kW',
                'power_hp' => '2 x 60 HP',
                'weight_kg' => null,
                'flow' => '140 m³/h',
                'head' => '160 - 100 mss',
                'pressure' => '12 - 13 bar',
                'sort' => 3,
                'is_hub' => false,
            ],
            [
                'key' => 'sht65-3b-55kw',
                'label' => 'SHT 65/3B 55KW',
                'sku' => 'SHT65-3B-55',
                'slug' => 'sumak-sht-65-3b-55kw-dusey-milli-kademeli-hidrofor',
                'price' => 1054805.00,
                'power_kw' => '2 x 55 kW',
                'power_hp' => '2 x 75 HP',
                'weight_kg' => null,
                'flow' => '160 m³/h',
                'head' => '180 - 100 mss',
                'pressure' => '13.5 - 15 bar',
                'sort' => 4,
                'is_hub' => false,
            ],
        ];
    }

    public function up(): void
    {
        $hub = Product::query()
            ->where('slug', self::HUB_SLUG)
            ->orWhere('sku', self::HUB_SKU)
            ->first();

        if ($hub === null) {
            return;
        }

        Product::withoutEvents(function () use ($hub): void {
            foreach ($this->options() as $option) {
                if ($option['is_hub']) {
                    $this->syncHub($hub, $option);

                    continue;
                }

                $this->syncChild($hub, $option);
            }
        });
    }

    public function down(): void
    {
        $childSlugs = collect($this->options())
            ->reject(fn (array $option) => $option['is_hub'])
            ->pluck('slug')
            ->all();

        Product::withoutEvents(function () use ($childSlugs): void {
            Product::query()->whereIn('slug', $childSlugs)->delete();

            $hub = Product::query()->where('slug', self::HUB_SLUG)->orWhere('sku', self::HUB_SKU)->first();
            if ($hub === null) {
                return;
            }

            $specs = is_array($hub->specs) ? $hub->specs : [];
            unset(
                $specs['purchase_group'],
                $specs['purchase_option_role'],
                $specs['purchase_option_key'],
                $specs['purchase_option_label'],
                $specs['purchase_option_sort'],
                $specs['purchase_hub_slug'],
            );
            $hub->forceFill(['specs' => $specs])->save();
        });
    }

    /**
     * @param  array{
     *   key: string,
     *   label: string,
     *   sku: string,
     *   slug: string,
     *   price: float,
     *   power_kw: string,
     *   power_hp: string,
     *   weight_kg: ?int,
     *   flow: string,
     *   head: string,
     *   pressure: string,
     *   sort: int,
     *   is_hub: bool
     * }  $option
     */
    private function syncHub(Product $hub, array $option): void
    {
        $specs = $this->mergeOptionSpecs(is_array($hub->specs) ? $hub->specs : [], $option, true);

        $hub->fill([
            'sku' => $option['sku'],
            'price' => $option['price'],
            'compare_at_price' => null,
            'stock' => max(1, (int) $hub->stock),
            'is_active' => true,
            'specs' => $specs,
            'weight_kg' => $option['weight_kg'] ?? $hub->weight_kg,
        ])->save();
    }

    /**
     * @param  array{
     *   key: string,
     *   label: string,
     *   sku: string,
     *   slug: string,
     *   price: float,
     *   power_kw: string,
     *   power_hp: string,
     *   weight_kg: ?int,
     *   flow: string,
     *   head: string,
     *   pressure: string,
     *   sort: int,
     *   is_hub: bool
     * }  $option
     */
    private function syncChild(Product $hub, array $option): void
    {
        $child = Product::query()
            ->where('slug', $option['slug'])
            ->orWhere('sku', $option['sku'])
            ->first();

        $optionName = trim($hub->name.' '.$option['label']);

        $payload = [
            'slug' => $option['slug'],
            'sku' => $option['sku'],
            'name' => $optionName,
            'short_description' => $hub->short_description,
            'description' => $hub->description,
            'translations' => $hub->translations,
            'brand_id' => $hub->brand_id,
            'price' => $option['price'],
            'compare_at_price' => null,
            'stock' => max(1, (int) ($child?->stock ?? $hub->stock)),
            'rating' => $hub->rating,
            'review_count' => 0,
            'badges' => $hub->badges,
            'specs' => $this->mergeOptionSpecs(is_array($hub->specs) ? $hub->specs : [], $option, false),
            'tags' => $hub->tags,
            'featured' => false,
            'is_active' => true,
            'image' => $hub->image,
            'image_alt' => $hub->image_alt ?: $hub->name,
            'meta_title' => $hub->meta_title,
            'meta_description' => $hub->meta_description,
            'barcode' => null,
            'weight_kg' => $option['weight_kg'] ?? $hub->weight_kg,
            'width_cm' => $hub->width_cm,
            'height_cm' => $hub->height_cm,
            'depth_cm' => $hub->depth_cm,
            'units_per_carton' => $hub->units_per_carton,
            'vat_rate' => $hub->vat_rate,
            'marketplace_enabled' => false,
        ];

        if ($child === null) {
            $child = Product::query()->create($payload);
        } else {
            $child->fill($payload)->save();
        }

        $categoryIds = $hub->categories()->pluck('categories.id')->all();
        if ($categoryIds !== []) {
            $child->categories()->sync($categoryIds);
        }
    }

    /**
     * @param  array<string, mixed>  $baseSpecs
     * @param  array{
     *   key: string,
     *   label: string,
     *   power_kw: string,
     *   power_hp: string,
     *   flow: string,
     *   head: string,
     *   pressure: string,
     *   sort: int
     * }  $option
     * @return array<string, mixed>
     */
    private function mergeOptionSpecs(array $baseSpecs, array $option, bool $isHub): array
    {
        $specs = Arr::except($baseSpecs, [
            'purchase_group',
            'purchase_option_role',
            'purchase_option_key',
            'purchase_option_label',
            'purchase_option_sort',
            'purchase_hub_slug',
        ]);

        $specs['Motor Gücü (kW)'] = $option['power_kw'];
        $specs['Motor Gücü (HP)'] = $option['power_hp'];
        $specs['Voltaj'] = '380 Volt (Trifaze)';
        $specs['Devir (d/d)'] = '2900 d/d';
        $specs['Giriş Ölçüsü'] = '6" - 150 mm';
        $specs['Çıkış Ölçüsü'] = '5" - 125 mm';
        $specs['Maks. Debi'] = $option['flow'];
        $specs['Basma Yüksekliği'] = $option['head'];
        $specs['Basınç'] = $option['pressure'];
        $specs['purchase_group'] = self::GROUP;
        $specs['purchase_option_role'] = $isHub ? 'hub' : 'child';
        $specs['purchase_option_key'] = $option['key'];
        $specs['purchase_option_label'] = $option['label'];
        $specs['purchase_option_sort'] = $option['sort'];
        $specs['purchase_hub_slug'] = self::HUB_SLUG;

        return $specs;
    }
};
