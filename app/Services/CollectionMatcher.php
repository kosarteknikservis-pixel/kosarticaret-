<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\Product;
use App\Support\CollectionSpecReader;
use Illuminate\Support\Collection as SupportCollection;

class CollectionMatcher
{
    /** @var array<int, list<int>>|null */
    private ?array $descendants = null;

    public function __construct(private CollectionSpecReader $reader) {}

    public function syncAll(): void
    {
        $collections = Collection::query()->get();
        Product::query()
            ->select(['id', 'name', 'specs', 'short_description', 'description', 'is_active', 'brand_id'])
            ->with('categories:id')
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($collections): void {
                foreach ($products as $product) {
                    $this->syncProduct($product, $collections);
                }
            });
    }

    /** @param  SupportCollection<int, Collection>|null  $collections */
    public function syncProduct(Product $product, ?SupportCollection $collections = null): void
    {
        $collections ??= Collection::query()->get();
        $product->loadMissing('categories:id');

        $locked = CollectionProduct::query()
            ->where('product_id', $product->id)
            ->whereIn('source', ['include', 'exclude'])
            ->pluck('collection_id')
            ->all();

        foreach ($collections as $collection) {
            if (in_array($collection->id, $locked, true)) {
                continue;
            }

            $evidence = $product->is_active ? $this->evidence($product, $collection) : null;
            $membership = CollectionProduct::query()
                ->where('collection_id', $collection->id)
                ->where('product_id', $product->id)
                ->where('source', 'rule');

            if ($evidence === null) {
                $membership->delete();

                continue;
            }

            CollectionProduct::query()->updateOrCreate(
                [
                    'collection_id' => $collection->id,
                    'product_id' => $product->id,
                ],
                [
                    'source' => 'rule',
                    'evidence' => $evidence,
                ],
            );
        }
    }

    public function setOverride(Collection $collection, Product $product, string $source): void
    {
        CollectionProduct::query()->updateOrCreate(
            [
                'collection_id' => $collection->id,
                'product_id' => $product->id,
            ],
            [
                'source' => $source,
                'evidence' => $source === 'include' ? 'Elle eklendi' : 'Elle çıkarıldı',
            ],
        );
    }

    public function clearOverride(Collection $collection, Product $product): void
    {
        CollectionProduct::query()
            ->where('collection_id', $collection->id)
            ->where('product_id', $product->id)
            ->whereIn('source', ['include', 'exclude'])
            ->delete();

        $this->syncProduct($product->fresh(['categories:id']) ?? $product);
    }

    public function evidence(Product $product, Collection $collection): ?string
    {
        $spec = $this->matchesSpecs($product, $collection);
        if ($spec === null || $this->blockReason($product, $collection) !== null) {
            return null;
        }

        return $spec;
    }

    private function matchesSpecs(Product $product, Collection $collection): ?string
    {
        $rules = $collection->rules ?? [];
        if ($collection->category_id) {
            $allowed = $this->descendants()[$collection->category_id] ?? [$collection->category_id];
            $owns = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($owns, $allowed) === []) {
                return null;
            }
        }

        $parts = [];
        if (isset($rules['motor_hp'])) {
            $motor = $this->reader->motorHp(is_array($product->specs) ? $product->specs : null);
            if ($motor['status'] !== 'clear' || $motor['hp'] === null) {
                return null;
            }
            if (abs($motor['hp'] - (float) $rules['motor_hp']) > 0.051) {
                return null;
            }
            $parts[] = $motor['evidence'];
        }

        if (isset($rules['phase'])) {
            $phase = $this->reader->phase(is_array($product->specs) ? $product->specs : null);
            $expected = match ($rules['phase']) {
                'monofaze' => 'mono',
                'trifaze' => 'tri',
                default => (string) $rules['phase'],
            };
            if ($phase['status'] !== $expected) {
                return null;
            }
            $parts[] = $phase['evidence'];
        }

        if ($parts === []) {
            return null;
        }

        return implode(' · ', array_filter($parts));
    }

    public function blockReason(Product $product, Collection $collection): ?string
    {
        $rules = $collection->rules ?? [];
        $kind = $this->collectionKind($collection);
        $specs = is_array($product->specs) ? $product->specs : null;
        $description = trim((string) $product->short_description.' '.(string) $product->description);

        if ($kind === 'hidrofor' && isset($rules['motor_hp'])) {
            $pump = $this->reader->multiPump($product->name, $specs, $description);
            if ($pump['status'] === 'multi') {
                return $pump['reason'];
            }
        }

        if ($kind === 'dalgic' && (isset($rules['motor_hp']) || isset($rules['phase']))) {
            if ($this->reader->bareMotor($product->name)) {
                return 'Yalnız motor, dalgıç pompa değil';
            }
        }

        return null;
    }

    private function collectionKind(Collection $collection): ?string
    {
        $collection->loadMissing('category');
        $slug = (string) ($collection->category?->slug ?? '');
        if (str_contains($slug, 'hidrofor')) {
            return 'hidrofor';
        }
        if (str_contains($slug, 'dalgic')) {
            return 'dalgic';
        }

        return null;
    }

    /**
     * @return array{matched: list<array<string, mixed>>, undecided: list<array<string, mixed>>, uncertain: list<array<string, mixed>>, name_only: list<array<string, mixed>>, excluded: list<array<string, mixed>>}
     */
    public function preview(Collection $collection, int $chunk = 200): array
    {
        $matched = [];
        $undecided = [];
        $uncertain = [];
        $nameOnly = [];
        $excluded = [];
        $rules = $collection->rules ?? [];
        $allowed = $collection->category_id
            ? ($this->descendants()[$collection->category_id] ?? [$collection->category_id])
            : null;

        $members = CollectionProduct::query()
            ->where('collection_id', $collection->id)
            ->get()
            ->keyBy('product_id');

        Product::query()
            ->active()
            ->select(['id', 'name', 'specs', 'short_description', 'description'])
            ->with('categories:id')
            ->chunkById($chunk, function ($products) use ($collection, $rules, $allowed, $members, &$matched, &$undecided, &$uncertain, &$nameOnly, &$excluded): void {
                foreach ($products as $product) {
                    if ($allowed !== null) {
                        $owns = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
                        if (array_intersect($owns, $allowed) === []) {
                            continue;
                        }
                    }

                    $row = $members->get($product->id);
                    if ($row && $row->source === 'exclude') {
                        continue;
                    }
                    if ($row && $row->source === 'include') {
                        $matched[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'evidence' => $row->evidence,
                            'source' => $row->source,
                        ];

                        continue;
                    }

                    $blocked = $this->blockReason($product, $collection);
                    if ($blocked !== null && $this->matchesSpecs($product, $collection) !== null) {
                        $excluded[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'reason' => $blocked,
                        ];

                        continue;
                    }
                    if ($row && $row->source === 'rule') {
                        $matched[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'evidence' => $row->evidence,
                            'source' => $row->source,
                        ];

                        continue;
                    }

                    $specs = is_array($product->specs) ? $product->specs : null;
                    if (isset($rules['motor_hp'])) {
                        $motor = $this->reader->motorHp($specs);
                        if ($motor['status'] === 'empty') {
                            $undecided[] = ['id' => $product->id, 'name' => $product->name, 'reason' => 'Güç alanı yok'];
                        } elseif ($motor['status'] === 'uncertain') {
                            $uncertain[] = ['id' => $product->id, 'name' => $product->name, 'reason' => $motor['reason']];
                        }
                    }
                    if (isset($rules['phase'])) {
                        $phase = $this->reader->phase($specs);
                        if ($phase['status'] === 'unknown') {
                            $undecided[] = ['id' => $product->id, 'name' => $product->name, 'reason' => 'Faz alanı yok veya tek değer değil'];
                        }
                    }
                    if ($this->nameLooksLike($product->name, $rules)) {
                        $nameOnly[] = ['id' => $product->id, 'name' => $product->name];
                    }
                }
            });

        $byName = fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']);
        usort($matched, $byName);
        usort($undecided, $byName);
        usort($uncertain, $byName);
        usort($nameOnly, $byName);
        usort($excluded, $byName);

        return compact('matched', 'undecided', 'uncertain', 'nameOnly', 'excluded');
    }

    /** @param  array<string, mixed>  $rules */
    private function nameLooksLike(string $name, array $rules): bool
    {
        $folded = $this->reader->fold($name);
        if (isset($rules['phase']) && $rules['phase'] === 'monofaze' && str_contains($folded, 'monofaze')) {
            return true;
        }
        if (! isset($rules['motor_hp'])) {
            return false;
        }
        $hp = (float) $rules['motor_hp'];
        $token = floor($hp) == $hp
            ? (string) (int) $hp
            : str_replace('.', '[.,]', number_format($hp, 1, '.', ''));

        return preg_match('/(?<![\d.,])'.$token.'(?:[.,]0+)?\s*hp\b/', $folded) === 1;
    }

    /** @return array<int, list<int>> */
    private function descendants(): array
    {
        if ($this->descendants !== null) {
            return $this->descendants;
        }

        $rows = Category::query()->get(['id', 'parent_id']);
        $children = [];
        foreach ($rows as $row) {
            $children[(int) ($row->parent_id ?? 0)][] = (int) $row->id;
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = $this->walk((int) $row->id, $children);
        }

        return $this->descendants = $map;
    }

    /** @param  array<int, list<int>>  $children
     * @return list<int>
     */
    private function walk(int $id, array $children): array
    {
        $ids = [$id];
        foreach ($children[$id] ?? [] as $child) {
            $ids = array_merge($ids, $this->walk($child, $children));
        }

        return $ids;
    }
}
