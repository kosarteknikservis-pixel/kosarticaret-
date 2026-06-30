<?php

namespace App\Support;

/**
 * Eski / kaldırılmış ürün slug'larından en uygun kategori yolunu tahmin eder.
 * Marka sayfası yerine ilgili kategori tercih edilir (SEO link equity).
 */
final class LegacySlugCategoryGuesser
{
    /** @var list<array{needles: list<string>, path: string}> */
    private const RULES = [
        ['needles' => ['hidrofor', 'hf-ko', 'pfk-ko', 'paket-hidrofor', 'genlesme-tanki'], 'path' => 'hidrofor-sistemleri/hidroforlar'],
        ['needles' => ['frekans-kontrollu', 'hf-ko-st'], 'path' => 'hidrofor-sistemleri/frekans-kontrollu-hidroforlar'],
        ['needles' => ['drenaj-dalgic', 'drenaj-dalgic-pompa', 'kirli-su-dalgic', 'atik-su'], 'path' => 'su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa'],
        ['needles' => ['foseptik', 'bicakli-dalgic', 'bicakli-foseptik'], 'path' => 'su-pompalari/dalgic-pompalar/bicakli-dalgic-pompa'],
        ['needles' => ['keson-kuyu', 'derin-kuyu-dalgic'], 'path' => 'su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa'],
        ['needles' => ['dalgic', 'dalgic-pompa', 'smac', 'smkt'], 'path' => 'su-pompalari/dalgic-pompalar'],
        ['needles' => ['kademeli', 'cmi-', 'cok-kademeli'], 'path' => 'su-pompalari/kademeli-pompalar'],
        ['needles' => ['sirkulasyon'], 'path' => 'su-pompalari/sirkulasyon-pompalari'],
        ['needles' => ['havuz-pompa', 'havuz-pompasi', 'smh200'], 'path' => 'su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi'],
        ['needles' => ['jet-pompa', 'jet-hidrofor', 'smjk', 'smj-'], 'path' => 'su-pompalari/santrifuj-pompalar'],
        ['needles' => ['santrifuj', 'salyangoz'], 'path' => 'su-pompalari/santrifuj-pompalar'],
        ['needles' => ['vantilator', 'aspirator', 'fan', 'ksv-'], 'path' => 'vantilatorler/sanayi-tipi-vantilator'],
        ['needles' => ['petek-temizleme'], 'path' => 'vantilatorler'],
        ['needles' => ['rezistans', 'termostat', 'anahtar', 'sinyal-lamba', 'musluk', 'komutator'], 'path' => 'elektrik-ve-aydinlatma'],
        ['needles' => ['pompa', 'wnp-', '2cp-', 'vxm-', 'pedrollo', 'sumak', 'winpo', 'etna', 'ebara', 'kaysu'], 'path' => 'su-pompalari'],
    ];

    public static function pathForSlug(string $slug): ?string
    {
        $normalized = LegacyProductSlugMatcher::normalizeSlug($slug);
        if ($normalized === '') {
            return null;
        }

        foreach (self::RULES as $rule) {
            foreach ($rule['needles'] as $needle) {
                if (str_contains($normalized, $needle)) {
                    return '/kategoriler/'.$rule['path'];
                }
            }
        }

        return null;
    }
}
