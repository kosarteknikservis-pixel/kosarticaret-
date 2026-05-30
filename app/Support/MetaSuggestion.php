<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

class MetaSuggestion
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{meta_title: string, meta_description: string}
     */
    public static function suggest(string $type, array $context): array
    {
        $site = (string) SiteSetting::get('site_name', config('kosar.name'));
        $name = self::primaryName($type, $context);

        return [
            'meta_title' => self::metaTitle($name, $site, $type),
            'meta_description' => self::metaDescription($type, $context, $name, $site),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function primaryName(string $type, array $context): string
    {
        return match ($type) {
            'blog', 'page' => trim((string) ($context['title'] ?? '')),
            default => trim((string) ($context['name'] ?? $context['title'] ?? '')),
        };
    }

    private static function metaTitle(string $name, string $site, string $type): string
    {
        if ($name === '') {
            return Str::limit($site, 60, '');
        }

        $suffix = ' | '.$site;
        $maxName = max(20, 60 - mb_strlen($suffix));
        $title = Str::limit($name, $maxName, '');

        if (mb_strlen($title.' | '.$site) <= 65) {
            return $title.' | '.$site;
        }

        return Str::limit($title, 60, '');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function metaDescription(string $type, array $context, string $name, string $site): string
    {
        foreach (self::descriptionCandidates($type, $context) as $candidate) {
            $plain = RichContent::plainText($candidate);
            if (mb_strlen($plain) >= 80) {
                return Str::limit($plain, 160, '');
            }
        }

        $label = match ($type) {
            'product' => 'ürün',
            'category' => 'kategori',
            'brand' => 'marka',
            'blog' => 'blog yazısı',
            'page' => 'sayfa',
            'settings' => 'mağaza',
            default => 'içerik',
        };

        $brand = trim((string) ($context['brand_name'] ?? ''));
        $extra = $brand !== '' ? " {$brand} markasıyla" : '';

        $sentence = match ($type) {
            'product' => "{$name}{$extra} — {$site} online mağazasında uygun fiyat, hızlı kargo ve güvenli ödeme.",
            'category' => "{$name} kategorisinde{$extra} geniş ürün seçeneği. {$site} ile güvenle alışveriş yapın.",
            'brand' => "{$name} markasının tüm ürünleri {$site} üzerinde. Orijinal ürün, hızlı teslimat.",
            'blog' => "{$name} — {$site} blogunda pompa ve hidrofor dünyasından ipuçları ve rehberler.",
            'page' => "{$name} hakkında bilgi — {$site}. Sipariş, kargo ve müşteri hizmetleri detayları.",
            'settings' => "{$site} — pompa, hidrofor ve endüstriyel ekipmanlarda güvenilir online alışveriş.",
            default => "{$name} — {$site} {$label} sayfası.",
        };

        return Str::limit(RichContent::plainText($sentence), 160, '');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string|null>
     */
    private static function descriptionCandidates(string $type, array $context): array
    {
        return match ($type) {
            'product' => [
                $context['short_description'] ?? null,
                $context['description'] ?? null,
                $context['excerpt'] ?? null,
            ],
            'blog' => [
                $context['excerpt'] ?? null,
                $context['content'] ?? null,
            ],
            'page', 'category', 'brand', 'settings' => [
                $context['description'] ?? null,
                $context['content'] ?? null,
                $context['site_description'] ?? null,
            ],
            default => [$context['description'] ?? null],
        };
    }
}
