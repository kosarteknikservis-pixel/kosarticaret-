<?php

namespace App\Services\Seo;

use App\Models\Product;
use App\Support\RichContent;
use App\Support\Seo;
use Illuminate\Support\Str;

class ProductSchemaEnricher
{
    /**
     * @return array{meta_description: ?string, specs: ?array<string, string>, meta_changed: bool, specs_changed: bool}
     */
    public function enrich(Product $product, bool $updateMeta = true, bool $extractSpecs = true, bool $forceSpecs = false): array
    {
        $meta = $product->meta_description;
        $metaChanged = false;

        if ($updateMeta && Seo::isGenericProductDescription($meta)) {
            $built = $this->buildMetaDescription($product);
            if ($built !== null && $built !== $meta) {
                $meta = $built;
                $metaChanged = true;
            }
        }

        $specs = is_array($product->specs) ? $product->specs : null;
        $specsChanged = false;

        if ($extractSpecs && ($forceSpecs || $specs === null || $specs === [])) {
            $extracted = $this->extractSpecsFromDescription((string) $product->description);
            if ($extracted !== []) {
                $specs = $extracted;
                $specsChanged = true;
            }
        }

        return [
            'meta_description' => $meta,
            'specs' => $specs,
            'meta_changed' => $metaChanged,
            'specs_changed' => $specsChanged,
        ];
    }

    public function buildMetaDescription(Product $product): ?string
    {
        foreach ([$product->short_description, $product->description] as $candidate) {
            $plain = RichContent::plainText($candidate);
            if (mb_strlen($plain) < 60) {
                continue;
            }

            return Str::limit($plain, 160, '');
        }

        $name = trim((string) $product->name);
        if ($name === '') {
            return null;
        }

        $brand = trim((string) ($product->brand?->name ?? ''));
        $extra = $brand !== '' ? " {$brand} markasıyla" : '';

        return Str::limit("{$name}{$extra} — ".config('kosar.name', 'Koşar').' online mağazasında uygun fiyat, hızlı kargo ve güvenli ödeme.', 160, '');
    }

    /**
     * @return array<string, string>
     */
    public function extractSpecsFromDescription(?string $html): array
    {
        if ($html === null || trim($html) === '' || ! str_contains($html, '<tr')) {
            return [];
        }

        if (! preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $rows)) {
            return [];
        }

        $specs = [];

        foreach ($rows[1] as $rowHtml) {
            if (! preg_match_all('/<(?:th|td)[^>]*>(.*?)<\/(?:th|td)>/is', $rowHtml, $cells)) {
                continue;
            }

            $values = array_map(
                fn (string $cell): string => RichContent::plainText($cell),
                $cells[1]
            );
            $values = array_values(array_filter($values, fn (string $v): bool => $v !== ''));

            if (count($values) < 2) {
                continue;
            }

            $name = $values[0];
            $value = $values[1];

            if ($this->isHeaderPair($name, $value)) {
                continue;
            }

            if (mb_strlen($name) > 80 || mb_strlen($value) > 160) {
                continue;
            }

            if (isset($specs[$name])) {
                continue;
            }

            $specs[$name] = $value;
        }

        return $specs;
    }

    private function isHeaderPair(string $name, string $value): bool
    {
        $headers = ['özellik', 'ozellik', 'değer', 'deger', 'property', 'value', 'specification', 'specs'];
        $n = mb_strtolower($name, 'UTF-8');
        $v = mb_strtolower($value, 'UTF-8');

        return in_array($n, $headers, true) || in_array($v, $headers, true);
    }
}
