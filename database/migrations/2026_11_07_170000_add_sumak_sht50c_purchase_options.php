<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;

return new class extends Migration
{
    private const HUB_SLUG = 'sumak-sht-50-c-dusey-milli-kademeli-hidrofor';

    private const HUB_SKU = 'SHT50C';

    private const GROUP = 'sumak-sht50c';

    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   sku: string,
     *   slug: string,
     *   price: float,
     *   power_kw: string,
     *   power_hp: string,
     *   weight_kg: int,
     *   flow: string,
     *   head: string,
     *   sort: int,
     *   is_hub: bool
     * }>
     */
    private function options(): array
    {
        return [
            [
                'key' => 'sht50-2c-11kw',
                'label' => 'SHT 50/2C 11KW',
                'sku' => 'SHT50C',
                'slug' => self::HUB_SLUG,
                'price' => 568870.00,
                'power_kw' => '3 x 11 kW',
                'power_hp' => '3 x 15 HP',
                'weight_kg' => 62,
                'flow' => '144 - 81 m³/h',
                'head' => '55 - 65 mss',
                'sort' => 1,
                'is_hub' => true,
            ],
            [
                'key' => 'sht50-2c-15kw',
                'label' => 'SHT 50/2C 15KW',
                'sku' => 'SHT50-2C-15',
                'slug' => 'sumak-sht-50-2c-15kw-dusey-milli-kademeli-hidrofor',
                'price' => 605895.00,
                'power_kw' => '3 x 15 kW',
                'power_hp' => '3 x 20 HP',
                'weight_kg' => 81,
                'flow' => '150 - 84 m³/h',
                'head' => '70 - 80 mss',
                'sort' => 2,
                'is_hub' => false,
            ],
            [
                'key' => 'sht50-3c-185kw',
                'label' => 'SHT 50/3C 18.5KW',
                'sku' => 'SHT50-3C-185',
                'slug' => 'sumak-sht-50-3c-185kw-dusey-milli-kademeli-hidrofor',
                'price' => 690930.00,
                'power_kw' => '3 x 18.5 kW',
                'power_hp' => '3 x 25 HP',
                'weight_kg' => 110,
                'flow' => '165 - 81 m³/h',
                'head' => '80 - 100 mss',
                'sort' => 3,
                'is_hub' => false,
            ],
            [
                'key' => 'sht50-3c-22kw',
                'label' => 'SHT 50/3C 22KW',
                'sku' => 'SHT50-3C-22',
                'slug' => 'sumak-sht-50-3c-22kw-dusey-milli-kademeli-hidrofor',
                'price' => 780900.00,
                'power_kw' => '3 x 22 kW',
                'power_hp' => '3 x 30 HP',
                'weight_kg' => 125,
                'flow' => '156 - 60 m³/h',
                'head' => '95 - 120 mss',
                'sort' => 4,
                'is_hub' => false,
            ],
            [
                'key' => 'sht50-4c-22kw',
                'label' => 'SHT 50/4C 22KW',
                'sku' => 'SHT50-4C-22',
                'slug' => 'sumak-sht-50-4c-22kw-dusey-milli-kademeli-hidrofor',
                'price' => 810585.00,
                'power_kw' => '3 x 22 kW',
                'power_hp' => '3 x 30 HP',
                'weight_kg' => 130,
                'flow' => '150 - 60 m³/h',
                'head' => '105 - 130 mss',
                'sort' => 5,
                'is_hub' => false,
            ],
            [
                'key' => 'sht50-4c-30kw',
                'label' => 'SHT 50/4C 30KW',
                'sku' => 'SHT50-4C-30',
                'slug' => 'sumak-sht-50-4c-30kw-dusey-milli-kademeli-hidrofor',
                'price' => 979840.00,
                'power_kw' => '3 x 30 kW',
                'power_hp' => '3 x 40 HP',
                'weight_kg' => 168,
                'flow' => '150 - 75 m³/h',
                'head' => '135 - 160 mss',
                'sort' => 6,
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
     *   weight_kg: int,
     *   flow: string,
     *   head: string,
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
            'weight_kg' => $option['weight_kg'],
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
     *   weight_kg: int,
     *   flow: string,
     *   head: string,
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
            'weight_kg' => $option['weight_kg'],
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
        $specs['purchase_group'] = self::GROUP;
        $specs['purchase_option_role'] = $isHub ? 'hub' : 'child';
        $specs['purchase_option_key'] = $option['key'];
        $specs['purchase_option_label'] = $option['label'];
        $specs['purchase_option_sort'] = $option['sort'];
        $specs['purchase_hub_slug'] = self::HUB_SLUG;

        return $specs;
    }
};
