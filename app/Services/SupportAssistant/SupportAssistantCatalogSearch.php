<?php

namespace App\Services\SupportAssistant;

use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SupportAssistantCatalogSearch
{
    private const INDEX_KEY = 'support_assistant.catalog_index.v1';

    private const CATEGORY_KEY = 'support_assistant.category_index.v2';

    private const VOCABULARY_KEY = 'support_assistant.catalog_vocabulary.v1';

    private const TTL = 600;

    private const SUFFIXES = ['lari', 'leri', 'sini', 'sunu', 'lar', 'ler', 'si', 'su', 'yi', 'yu', 'i', 'u'];

    private const STOPWORDS = ['fiyat', 'fiyatlar', 'ucuz', 'uygun', 'en', 'iyi', 'model', 'modeller', 'urun', 'urunler', 'satin', 'al', 'almak', 'var', 'mi', 'mu', 'ne', 'kac', 'tl', 'lira', 'tavsiye', 'oneri', 'onerir', 'misin', 'icin', 've', 'ile', 'bir'];

    public static function fold(string $value): string
    {
        $value = mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $value), 'UTF-8');
        $value = strtr($value, ['ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'û' => 'u', 'i̇' => 'i']);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /** @var array<string, string> */
    private array $corrections = [];

    /** @return list<string> */
    public function terms(string $query): array
    {
        $brands = array_values(array_unique(array_filter(array_column($this->index(), 'brand'))));
        $terms = [];
        $this->corrections = [];
        foreach (explode(' ', self::fold($query)) as $token) {
            if ($token === '' || (strlen($token) < 2 && ! ctype_digit($token)) || in_array($token, self::STOPWORDS, true)) {
                continue;
            }
            $isBrand = array_filter($brands, fn (string $b) => str_contains($b, $token)) !== [];
            $stem = $isBrand ? $token : self::stem($token);
            if (in_array($stem, self::STOPWORDS, true)) {
                continue;
            }
            if (! $isBrand && ($corrected = $this->correct($stem)) !== null) {
                $this->corrections[$token] = $corrected;
                $stem = $corrected;
            }
            $terms[] = $stem;
        }

        return array_slice(array_values(array_unique($terms)), 0, 6);
    }

    /**
     * Son terms() çağrısında yazım hatası düzeltilen kelimeler (yazılan => katalogdaki).
     *
     * @return array<string, string>
     */
    public function corrections(): array
    {
        return $this->corrections;
    }

    /**
     * Katalogda hiç geçmeyen kelimeyi, ilk harfi aynı ve 1-2 harf farklı en yaygın katalog kelimesine çevirir.
     */
    private function correct(string $term): ?string
    {
        $length = strlen($term);
        if ($length < 5 || ctype_digit($term) || preg_match('/\d/', $term)) {
            return null;
        }

        $vocabulary = $this->vocabulary();
        foreach ($vocabulary as $word => $count) {
            if (str_contains((string) $word, $term)) {
                return null;
            }
        }

        $maxDistance = $length >= 8 ? 2 : 1;
        $best = null;
        foreach ($vocabulary as $word => $count) {
            $word = (string) $word;
            if ($word[0] !== $term[0] || strlen($word) < $length - $maxDistance) {
                continue;
            }
            $candidates = [$word];
            if (strlen($word) > $length) {
                $candidates[] = substr($word, 0, $length);
            }
            foreach ($candidates as $candidate) {
                $distance = levenshtein($term, $candidate);
                if ($distance === 0 || $distance > $maxDistance) {
                    continue;
                }
                $score = [$distance, -$count, strlen($candidate)];
                if ($best === null || $score < $best['score']) {
                    $best = ['word' => $candidate, 'score' => $score];
                }
            }
        }

        return $best['word'] ?? null;
    }

    /** @return array<string, int> */
    private function vocabulary(): array
    {
        return Cache::remember(self::VOCABULARY_KEY, self::TTL, function () {
            $counts = [];
            foreach ($this->index() as $row) {
                foreach (array_unique(explode(' ', $row['text'])) as $word) {
                    if (strlen($word) >= 3 && ! preg_match('/\d/', $word)) {
                        $counts[$word] = ($counts[$word] ?? 0) + 1;
                    }
                }
            }
            arsort($counts);

            return $counts;
        });
    }

    private static function stem(string $term): string
    {
        if (ctype_digit($term) || strlen($term) < 4) {
            return $term;
        }
        foreach (self::SUFFIXES as $suffix) {
            if (str_ends_with($term, $suffix) && strlen($term) - strlen($suffix) >= 3) {
                return substr($term, 0, -strlen($suffix));
            }
        }

        return $term;
    }

    private static function contains(string $haystack, string $term): bool
    {
        if (ctype_digit($term)) {
            return (bool) preg_match('/(?<!\d)'.$term.'(?!\d)/', $haystack);
        }
        if (strlen($term) < 3) {
            return (bool) preg_match('/(?:^| )'.$term.'/', $haystack);
        }

        return str_contains($haystack, $term);
    }

    /**
     * @param  array{min_price?: float|null, max_price?: float|null, only_in_stock?: bool, brand?: string|null}  $filters
     * @return array{products: Collection<int, Product>, approximate: bool, brands: list<array{marka: string, urun_sayisi: int}>, brand_found: bool, corrections: array<string, string>}
     */
    public function search(string $query, array $filters = [], int $limit = 6): array
    {
        $terms = $this->terms($query);
        $phrase = self::fold($query);
        $brand = self::fold((string) ($filters['brand'] ?? ''));
        $index = $this->index();

        if ($brand !== '') {
            $brandRows = array_filter($index, fn (array $row) => $row['brand'] !== '' && str_contains($row['brand'], $brand));
            if ($brandRows === []) {
                return ['products' => collect(), 'approximate' => false, 'brands' => [], 'brand_found' => false, 'corrections' => $this->corrections];
            }
            $index = $brandRows;
        }

        $scored = [];
        foreach ($index as $row) {
            $matched = 0;
            $nameHits = 0;
            foreach ($terms as $term) {
                if (self::contains($row['text'], $term)) {
                    $matched++;
                    $nameHits += self::contains($row['name'], $term) ? 1 : 0;
                }
            }
            if ($matched > 0) {
                $scored[$row['id']] = [
                    'matched' => $matched,
                    'name_hits' => $nameHits,
                    'phrase' => $phrase !== '' && str_contains($row['name'], $phrase) ? 1 : 0,
                    'brand' => $row['brand_name'],
                    'name' => $row['name'],
                ];
            }
        }

        $total = count($terms);
        $strict = array_filter($scored, fn (array $s) => $s['matched'] === $total);
        $approximate = $strict === [] && $total > 1;
        $candidates = $strict !== [] ? $strict : ($approximate ? array_filter($scored, fn (array $s) => $s['matched'] > 0) : []);

        if ($candidates === []) {
            return ['products' => collect(), 'approximate' => false, 'brands' => [], 'brand_found' => true, 'corrections' => $this->corrections];
        }

        $rows = CatalogQuery::products()
            ->whereIn('id', array_keys($candidates))
            ->when(isset($filters['min_price']), fn ($q) => $q->where('price', '>=', (float) $filters['min_price']))
            ->when(! empty($filters['max_price']), fn ($q) => $q->where('price', '<=', (float) $filters['max_price']))
            ->when(! empty($filters['only_in_stock']), fn ($q) => $q->where('stock', '>', 0))
            ->get(['id', 'stock', 'featured']);

        $ranked = $rows->map(fn ($p) => $candidates[$p->id] + [
            'id' => (int) $p->id,
            'in_stock' => (int) $p->stock > 0 ? 1 : 0,
            'featured' => $p->featured ? 1 : 0,
        ])->sort(fn (array $a, array $b) => [
            $b['matched'], $b['name_hits'], $b['phrase'], $b['in_stock'], $b['featured'], $a['name'],
        ] <=> [
            $a['matched'], $a['name_hits'], $a['phrase'], $a['in_stock'], $a['featured'], $b['name'],
        ])->values();

        $brands = $ranked->filter(fn (array $r) => $r['brand'] !== '')
            ->countBy('brand')
            ->sortDesc()
            ->take(8)
            ->map(fn (int $count, string $name) => ['marka' => $name, 'urun_sayisi' => $count])
            ->values()
            ->all();

        $picked = $brand === '' && count($brands) > 1 ? $this->diversify($ranked, $limit) : $ranked->take($limit)->pluck('id')->all();

        $models = CatalogQuery::products()->with('brand:id,name')->whereIn('id', $picked)->get()->keyBy('id');
        $products = collect($picked)->map(fn (int $id) => $models->get($id))->filter()->values();

        return ['products' => $products, 'approximate' => $approximate, 'brands' => $brands, 'brand_found' => true, 'corrections' => $this->corrections];
    }

    /**
     * Keeps relevance order but, within the same relevance tier, shows one product per brand before repeating a brand.
     *
     * @param  Collection<int, array<string, mixed>>  $ranked
     * @return list<int>
     */
    private function diversify(Collection $ranked, int $limit): array
    {
        $picked = [];
        foreach ($ranked->groupBy(fn (array $r) => $r['matched'].'-'.$r['name_hits'].'-'.$r['in_stock']) as $tier) {
            $seen = [];
            $rest = [];
            foreach ($tier as $row) {
                if (isset($seen[$row['brand']])) {
                    $rest[] = $row['id'];

                    continue;
                }
                $seen[$row['brand']] = true;
                $picked[] = $row['id'];
            }
            array_push($picked, ...$rest);
            if (count($picked) >= $limit) {
                break;
            }
        }

        return array_slice($picked, 0, $limit);
    }

    /** @return Collection<int, Category> */
    public function categories(string $query, int $limit = 3): Collection
    {
        $terms = array_values(array_filter($this->terms($query), fn (string $t) => strlen($t) >= 3 && ! ctype_digit($t)));
        if ($terms === []) {
            return collect();
        }

        $matches = array_filter($this->categoryIndex(), function (array $row) use ($terms) {
            foreach ($terms as $term) {
                if (! str_contains($row['name'], $term)) {
                    return false;
                }
            }

            return true;
        });

        if ($matches === [] && count($terms) > 1) {
            $matches = array_filter($this->categoryIndex(), fn (array $row) => str_contains($row['name'], $terms[0]));
        }

        usort($matches, fn (array $a, array $b) => [
            str_starts_with($a['name'], $terms[0]) ? 0 : 1, $a['depth'], strlen($a['name']), $a['sort'],
        ] <=> [
            str_starts_with($b['name'], $terms[0]) ? 0 : 1, $b['depth'], strlen($b['name']), $b['sort'],
        ]);

        $ids = array_slice(array_column($matches, 'id'), 0, $limit);
        $models = Category::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn (int $id) => $models->get($id))->filter()->values();
    }

    /** @return list<array{id: int, name: string, text: string, brand: string, brand_name: string}> */
    private function index(): array
    {
        return Cache::remember(self::INDEX_KEY, self::TTL, function () {
            return CatalogQuery::products()
                ->with(['brand:id,name', 'categories:id,name'])
                ->get(['id', 'name', 'sku', 'brand_id'])
                ->map(function (Product $p) {
                    $brandName = (string) ($p->brand?->name ?? '');
                    $name = self::fold((string) $p->name);

                    return [
                        'id' => (int) $p->id,
                        'name' => $name,
                        'text' => implode(' ', array_filter([
                            $name,
                            self::fold((string) $p->sku),
                            self::fold($brandName),
                            self::fold($p->categories->pluck('name')->implode(' ')),
                        ])),
                        'brand' => self::fold($brandName),
                        'brand_name' => $brandName,
                    ];
                })
                ->all();
        });
    }

    /** @return list<array{id: int, name: string, sort: int, depth: int}> */
    private function categoryIndex(): array
    {
        return Cache::remember(self::CATEGORY_KEY, self::TTL, function () {
            $categories = Category::query()->where('active', true)->get(['id', 'name', 'sort_order', 'parent_id']);
            $parents = $categories->pluck('parent_id', 'id');

            return $categories->map(function (Category $c) use ($parents) {
                $depth = 0;
                $parent = $c->parent_id;
                while ($parent !== null && $depth < 6) {
                    $depth++;
                    $parent = $parents->get($parent);
                }

                return ['id' => (int) $c->id, 'name' => self::fold((string) $c->name), 'sort' => (int) $c->sort_order, 'depth' => $depth];
            })->all();
        });
    }
}
