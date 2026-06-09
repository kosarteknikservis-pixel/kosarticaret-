<?php

namespace App\Support;

use App\Models\SiteSetting;

final class ThemeSettings
{
    public const CUSTOM_CSS_KEY = 'theme_custom_css';
    public const CUSTOM_CSS_BACKUP_KEY = 'theme_custom_css_backup';
    public const CUSTOM_CSS_MAX_LENGTH = 12000;
    public const BACKUPS_KEY = 'theme_backups_json';
    public const BACKUP_LIMIT = 12;

    public const KEYS = [
        'theme_palette',
        'theme_radius',
        'theme_shadow',
        'theme_home_layout',
        'theme_home_banner',
        'theme_home_products',
        'theme_card_density',
        'theme_product_card_style',
        'theme_catalog_grid',
        'theme_pdp_layout',
        'theme_pdp_gallery',
        'theme_header_density',
        'theme_header_style',
        'theme_header_search',
        'theme_header_icons',
        'theme_nav_style',
        'theme_footer_style',
        'theme_footer_columns',
        'theme_footer_trust',
        'theme_footer_newsletter',
        'theme_motion',
    ];

    public const DEFAULTS = [
        'theme_palette' => 'navy',
        'theme_radius' => 'balanced',
        'theme_shadow' => 'soft',
        'theme_home_layout' => 'standard',
        'theme_home_banner' => 'balanced',
        'theme_home_products' => 'standard',
        'theme_card_density' => 'comfortable',
        'theme_product_card_style' => 'premium',
        'theme_catalog_grid' => 'standard',
        'theme_pdp_layout' => 'balanced',
        'theme_pdp_gallery' => 'standard',
        'theme_header_density' => 'standard',
        'theme_header_style' => 'premium',
        'theme_header_search' => 'filled',
        'theme_header_icons' => 'soft',
        'theme_nav_style' => 'standard',
        'theme_footer_style' => 'standard',
        'theme_footer_columns' => 'standard',
        'theme_footer_trust' => 'full',
        'theme_footer_newsletter' => 'standard',
        'theme_motion' => 'standard',
    ];

    public const OPTIONS = [
        'theme_palette' => [
            'navy' => 'KOŞAR Lacivert',
            'technical' => 'Teknik Mavi',
            'graphite' => 'Kurumsal Grafit',
        ],
        'theme_radius' => [
            'balanced' => 'Dengeli',
            'soft' => 'Daha yumuşak',
            'sharp' => 'Daha keskin',
        ],
        'theme_shadow' => [
            'soft' => 'Yumuşak',
            'standard' => 'Standart',
            'lifted' => 'Belirgin',
        ],
        'theme_home_layout' => [
            'standard' => 'Standart ana sayfa',
            'spacious' => 'Ferahlık odaklı',
            'compact' => 'Kompakt vitrin',
        ],
        'theme_home_banner' => [
            'balanced' => 'Dengeli banner',
            'image_focus' => 'Görsel odaklı',
            'clean' => 'Temiz banner',
        ],
        'theme_home_products' => [
            'standard' => 'Standart ürün vitrini',
            'showcase' => 'Vitrin odaklı',
            'compact' => 'Kompakt ürün şeridi',
        ],
        'theme_card_density' => [
            'comfortable' => 'Rahat',
            'compact' => 'Kompakt',
        ],
        'theme_product_card_style' => [
            'premium' => 'Premium kart',
            'technical' => 'Teknik kart',
            'minimal' => 'Sade kart',
        ],
        'theme_catalog_grid' => [
            'standard' => 'Standart grid',
            'dense' => 'Daha sıkı grid',
            'spacious' => 'Ferahlık odaklı',
        ],
        'theme_pdp_layout' => [
            'balanced' => 'Dengeli',
            'gallery_focus' => 'Görsel odaklı',
            'compact' => 'Kompakt satış',
        ],
        'theme_pdp_gallery' => [
            'standard' => 'Standart galeri',
            'clean' => 'Temiz beyaz',
            'framed' => 'Çerçeveli teknik',
        ],
        'theme_header_density' => [
            'standard' => 'Standart',
            'compact' => 'Kompakt',
        ],
        'theme_header_style' => [
            'premium' => 'Premium cam',
            'clean' => 'Temiz beyaz',
            'technical' => 'Teknik çizgili',
        ],
        'theme_header_search' => [
            'filled' => 'Dolgulu arama',
            'outline' => 'Çizgili arama',
            'minimal' => 'Minimal arama',
        ],
        'theme_header_icons' => [
            'soft' => 'Yumuşak ikonlar',
            'solid' => 'Belirgin ikonlar',
            'minimal' => 'Minimal ikonlar',
        ],
        'theme_nav_style' => [
            'standard' => 'Standart menü',
            'pill' => 'Pill menü',
            'underline' => 'Alt çizgili menü',
        ],
        'theme_footer_style' => [
            'standard' => 'Standart',
            'compact' => 'Kompakt',
        ],
        'theme_footer_columns' => [
            'standard' => 'Standart kolonlar',
            'balanced' => 'Dengeli kolonlar',
            'compact' => 'Kompakt kolonlar',
        ],
        'theme_footer_trust' => [
            'full' => 'Tam güven şeridi',
            'compact' => 'Kompakt güven',
            'minimal' => 'Minimal güven',
        ],
        'theme_footer_newsletter' => [
            'standard' => 'Standart bülten',
            'card' => 'Kart bülten',
            'minimal' => 'Minimal bülten',
        ],
        'theme_motion' => [
            'standard' => 'Standart',
            'reduced' => 'Azaltılmış',
        ],
    ];

    public const GROUPS = [
        'Temel Görünüm' => [
            'theme_palette',
            'theme_radius',
            'theme_shadow',
            'theme_motion',
        ],
        'Ana Sayfa' => [
            'theme_home_layout',
            'theme_home_banner',
            'theme_home_products',
        ],
        'Listeleme ve Ürün Kartları' => [
            'theme_card_density',
            'theme_product_card_style',
            'theme_catalog_grid',
        ],
        'Ürün Detay Sayfası' => [
            'theme_pdp_layout',
            'theme_pdp_gallery',
        ],
        'Header ve Footer' => [
            'theme_header_density',
            'theme_header_style',
            'theme_header_search',
            'theme_header_icons',
            'theme_nav_style',
            'theme_footer_style',
            'theme_footer_columns',
            'theme_footer_trust',
            'theme_footer_newsletter',
        ],
    ];

    public const LABELS = [
        'theme_palette' => 'Renk paleti',
        'theme_radius' => 'Köşe yuvarlaklığı',
        'theme_shadow' => 'Gölge seviyesi',
        'theme_home_layout' => 'Ana sayfa yerleşimi',
        'theme_home_banner' => 'Ana sayfa banner stili',
        'theme_home_products' => 'Ana sayfa ürün vitrini',
        'theme_card_density' => 'Ürün kartı yoğunluğu',
        'theme_product_card_style' => 'Ürün kartı stili',
        'theme_catalog_grid' => 'Kategori grid yapısı',
        'theme_pdp_layout' => 'Ürün detay yerleşimi',
        'theme_pdp_gallery' => 'Ürün detay galeri görünümü',
        'theme_header_density' => 'Header yoğunluğu',
        'theme_header_style' => 'Header görünümü',
        'theme_header_search' => 'Header arama stili',
        'theme_header_icons' => 'Header ikon stili',
        'theme_nav_style' => 'Ana menü stili',
        'theme_footer_style' => 'Footer görünümü',
        'theme_footer_columns' => 'Footer kolon yapısı',
        'theme_footer_trust' => 'Footer güven alanı',
        'theme_footer_newsletter' => 'Footer bülten alanı',
        'theme_motion' => 'Animasyon seviyesi',
    ];

    public const PRESETS = [
        'premium_industrial' => [
            'name' => 'Premium Endüstriyel',
            'description' => 'Lacivert marka tonu, premium kartlar, dengeli ürün detay ve güçlü kurumsal footer.',
            'values' => [
                'theme_palette' => 'navy',
                'theme_radius' => 'balanced',
                'theme_shadow' => 'soft',
                'theme_home_layout' => 'standard',
                'theme_home_banner' => 'balanced',
                'theme_home_products' => 'standard',
                'theme_card_density' => 'comfortable',
                'theme_product_card_style' => 'premium',
                'theme_catalog_grid' => 'standard',
                'theme_pdp_layout' => 'balanced',
                'theme_pdp_gallery' => 'standard',
                'theme_header_density' => 'standard',
                'theme_header_style' => 'premium',
                'theme_header_search' => 'filled',
                'theme_header_icons' => 'soft',
                'theme_nav_style' => 'standard',
                'theme_footer_style' => 'standard',
                'theme_footer_columns' => 'standard',
                'theme_footer_trust' => 'full',
                'theme_footer_newsletter' => 'standard',
                'theme_motion' => 'standard',
            ],
        ],
        'minimal_white' => [
            'name' => 'Minimal Beyaz',
            'description' => 'Daha sade, ferah ve beyaz ağırlıklı görünüm. Ürünleri sakin bir sunumla öne çıkarır.',
            'values' => [
                'theme_palette' => 'graphite',
                'theme_radius' => 'soft',
                'theme_shadow' => 'soft',
                'theme_home_layout' => 'spacious',
                'theme_home_banner' => 'clean',
                'theme_home_products' => 'showcase',
                'theme_card_density' => 'comfortable',
                'theme_product_card_style' => 'minimal',
                'theme_catalog_grid' => 'spacious',
                'theme_pdp_layout' => 'gallery_focus',
                'theme_pdp_gallery' => 'clean',
                'theme_header_density' => 'standard',
                'theme_header_style' => 'clean',
                'theme_header_search' => 'minimal',
                'theme_header_icons' => 'minimal',
                'theme_nav_style' => 'underline',
                'theme_footer_style' => 'standard',
                'theme_footer_columns' => 'balanced',
                'theme_footer_trust' => 'minimal',
                'theme_footer_newsletter' => 'minimal',
                'theme_motion' => 'reduced',
            ],
        ],
        'technical_catalog' => [
            'name' => 'Teknik Katalog',
            'description' => 'Daha sıkı grid, teknik kart yapısı ve ürünleri katalog gibi hızlı taramaya uygun yapı.',
            'values' => [
                'theme_palette' => 'technical',
                'theme_radius' => 'sharp',
                'theme_shadow' => 'standard',
                'theme_home_layout' => 'compact',
                'theme_home_banner' => 'image_focus',
                'theme_home_products' => 'compact',
                'theme_card_density' => 'compact',
                'theme_product_card_style' => 'technical',
                'theme_catalog_grid' => 'dense',
                'theme_pdp_layout' => 'compact',
                'theme_pdp_gallery' => 'framed',
                'theme_header_density' => 'compact',
                'theme_header_style' => 'technical',
                'theme_header_search' => 'outline',
                'theme_header_icons' => 'solid',
                'theme_nav_style' => 'pill',
                'theme_footer_style' => 'compact',
                'theme_footer_columns' => 'compact',
                'theme_footer_trust' => 'compact',
                'theme_footer_newsletter' => 'card',
                'theme_motion' => 'standard',
            ],
        ],
        'corporate_navy' => [
            'name' => 'Kurumsal Lacivert',
            'description' => 'Daha belirgin gölge, güçlü header/footer ve kurumsal güven vurgusu.',
            'values' => [
                'theme_palette' => 'navy',
                'theme_radius' => 'balanced',
                'theme_shadow' => 'lifted',
                'theme_home_layout' => 'standard',
                'theme_home_banner' => 'image_focus',
                'theme_home_products' => 'showcase',
                'theme_card_density' => 'comfortable',
                'theme_product_card_style' => 'technical',
                'theme_catalog_grid' => 'standard',
                'theme_pdp_layout' => 'balanced',
                'theme_pdp_gallery' => 'framed',
                'theme_header_density' => 'standard',
                'theme_header_style' => 'premium',
                'theme_header_search' => 'outline',
                'theme_header_icons' => 'solid',
                'theme_nav_style' => 'pill',
                'theme_footer_style' => 'standard',
                'theme_footer_columns' => 'balanced',
                'theme_footer_trust' => 'full',
                'theme_footer_newsletter' => 'card',
                'theme_motion' => 'standard',
            ],
        ],
    ];

    public const SECTION_PRESETS = [
        'home' => [
            'label' => 'Ana Sayfa',
            'templates' => [
                'balanced' => [
                    'name' => 'Ana Sayfa Tip 1',
                    'description' => 'Dengeli banner, standart blok aralıkları ve klasik ürün vitrini.',
                    'values' => [
                        'theme_home_layout' => 'standard',
                        'theme_home_banner' => 'balanced',
                        'theme_home_products' => 'standard',
                    ],
                ],
                'showcase' => [
                    'name' => 'Ana Sayfa Tip 2',
                    'description' => 'Daha ferah bloklar, temiz banner ve vitrin odaklı ürün şeridi.',
                    'values' => [
                        'theme_home_layout' => 'spacious',
                        'theme_home_banner' => 'clean',
                        'theme_home_products' => 'showcase',
                    ],
                ],
                'catalog' => [
                    'name' => 'Ana Sayfa Tip 3',
                    'description' => 'Kompakt ana sayfa, görsel odaklı banner ve sıkı ürün şeridi.',
                    'values' => [
                        'theme_home_layout' => 'compact',
                        'theme_home_banner' => 'image_focus',
                        'theme_home_products' => 'compact',
                    ],
                ],
            ],
        ],
        'header' => [
            'label' => 'Header',
            'templates' => [
                'premium' => [
                    'name' => 'Header Tip 1',
                    'description' => 'Cam efektli premium header, dolgulu arama ve yumuşak ikonlar.',
                    'values' => [
                        'theme_header_density' => 'standard',
                        'theme_header_style' => 'premium',
                        'theme_header_search' => 'filled',
                        'theme_header_icons' => 'soft',
                        'theme_nav_style' => 'standard',
                    ],
                ],
                'clean' => [
                    'name' => 'Header Tip 2',
                    'description' => 'Beyaz, sade, minimal arama ve alt çizgili menü.',
                    'values' => [
                        'theme_header_density' => 'standard',
                        'theme_header_style' => 'clean',
                        'theme_header_search' => 'minimal',
                        'theme_header_icons' => 'minimal',
                        'theme_nav_style' => 'underline',
                    ],
                ],
                'technical' => [
                    'name' => 'Header Tip 3',
                    'description' => 'Kompakt, teknik çizgili header, belirgin ikonlar ve pill menü.',
                    'values' => [
                        'theme_header_density' => 'compact',
                        'theme_header_style' => 'technical',
                        'theme_header_search' => 'outline',
                        'theme_header_icons' => 'solid',
                        'theme_nav_style' => 'pill',
                    ],
                ],
            ],
        ],
        'product_card' => [
            'label' => 'Ürün Kartı',
            'templates' => [
                'premium' => [
                    'name' => 'Kart Tip 1',
                    'description' => 'Rahat boşluklu premium ürün kartı.',
                    'values' => [
                        'theme_card_density' => 'comfortable',
                        'theme_product_card_style' => 'premium',
                        'theme_catalog_grid' => 'standard',
                    ],
                ],
                'minimal' => [
                    'name' => 'Kart Tip 2',
                    'description' => 'Daha sade, ferah ve minimal ürün listesi.',
                    'values' => [
                        'theme_card_density' => 'comfortable',
                        'theme_product_card_style' => 'minimal',
                        'theme_catalog_grid' => 'spacious',
                    ],
                ],
                'catalog' => [
                    'name' => 'Kart Tip 3',
                    'description' => 'Kompakt katalog görünümü, daha fazla ürünü aynı ekranda gösterir.',
                    'values' => [
                        'theme_card_density' => 'compact',
                        'theme_product_card_style' => 'technical',
                        'theme_catalog_grid' => 'dense',
                    ],
                ],
            ],
        ],
        'pdp' => [
            'label' => 'Ürün Detay',
            'templates' => [
                'balanced' => [
                    'name' => 'Detay Tip 1',
                    'description' => 'Dengeli ürün detay görünümü.',
                    'values' => [
                        'theme_pdp_layout' => 'balanced',
                        'theme_pdp_gallery' => 'standard',
                    ],
                ],
                'gallery' => [
                    'name' => 'Detay Tip 2',
                    'description' => 'Görsel odaklı, temiz beyaz ürün sunumu.',
                    'values' => [
                        'theme_pdp_layout' => 'gallery_focus',
                        'theme_pdp_gallery' => 'clean',
                    ],
                ],
                'sales' => [
                    'name' => 'Detay Tip 3',
                    'description' => 'Kompakt satış odaklı detay ekranı.',
                    'values' => [
                        'theme_pdp_layout' => 'compact',
                        'theme_pdp_gallery' => 'framed',
                    ],
                ],
            ],
        ],
        'footer' => [
            'label' => 'Footer',
            'templates' => [
                'corporate' => [
                    'name' => 'Footer Tip 1',
                    'description' => 'Tam güven şeridi ve standart kurumsal footer.',
                    'values' => [
                        'theme_footer_style' => 'standard',
                        'theme_footer_columns' => 'standard',
                        'theme_footer_trust' => 'full',
                        'theme_footer_newsletter' => 'standard',
                    ],
                ],
                'clean' => [
                    'name' => 'Footer Tip 2',
                    'description' => 'Dengeli kolonlar, minimal güven alanı ve sade bülten.',
                    'values' => [
                        'theme_footer_style' => 'standard',
                        'theme_footer_columns' => 'balanced',
                        'theme_footer_trust' => 'minimal',
                        'theme_footer_newsletter' => 'minimal',
                    ],
                ],
                'compact' => [
                    'name' => 'Footer Tip 3',
                    'description' => 'Kompakt footer, kart bülten ve sıkı güven alanı.',
                    'values' => [
                        'theme_footer_style' => 'compact',
                        'theme_footer_columns' => 'compact',
                        'theme_footer_trust' => 'compact',
                        'theme_footer_newsletter' => 'card',
                    ],
                ],
            ],
        ],
    ];

    /** @return array<string, string> */
    public static function values(): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $value = SiteSetting::get($key, self::DEFAULTS[$key]);
            $values[$key] = self::normalize($key, $value);
        }

        return $values;
    }

    /** @param array<string, mixed> $input */
    public static function sanitize(array $input): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = self::normalize($key, $input[$key] ?? self::DEFAULTS[$key]);
        }

        return $values;
    }

    /** @return array<string, string> */
    public static function presetValues(string $preset): array
    {
        $values = self::PRESETS[$preset]['values'] ?? null;

        return is_array($values)
            ? self::sanitize($values)
            : self::DEFAULTS;
    }

    public static function presetName(string $preset): string
    {
        return (string) (self::PRESETS[$preset]['name'] ?? 'Tema');
    }

    /** @return array<string, string> */
    public static function sectionPresetValues(string $section, string $template): array
    {
        $values = self::SECTION_PRESETS[$section]['templates'][$template]['values'] ?? [];

        return self::sanitize(array_merge(self::values(), is_array($values) ? $values : []));
    }

    public static function sectionPresetName(string $section, string $template): string
    {
        return (string) (self::SECTION_PRESETS[$section]['templates'][$template]['name'] ?? 'Bölüm şablonu');
    }

    /** @return array<string, string> */
    public static function cssVariables(?array $values = null): array
    {
        $values ??= self::values();

        $palettes = [
            'navy' => [
                '--kosar-primary' => '#1e3a5f',
                '--kosar-primary-dark' => '#152a47',
                '--kosar-accent' => '#2d4a73',
                '--kosar-surface' => '#f4f6f9',
            ],
            'technical' => [
                '--kosar-primary' => '#164e75',
                '--kosar-primary-dark' => '#0f314c',
                '--kosar-accent' => '#256b9a',
                '--kosar-surface' => '#f2f7fb',
            ],
            'graphite' => [
                '--kosar-primary' => '#243244',
                '--kosar-primary-dark' => '#111827',
                '--kosar-accent' => '#475569',
                '--kosar-surface' => '#f5f6f8',
            ],
        ];

        $radius = [
            'balanced' => ['--kosar-radius' => '0.75rem', '--kosar-radius-lg' => '1rem', '--kosar-radius-xl' => '1.25rem'],
            'soft' => ['--kosar-radius' => '0.95rem', '--kosar-radius-lg' => '1.25rem', '--kosar-radius-xl' => '1.55rem'],
            'sharp' => ['--kosar-radius' => '0.45rem', '--kosar-radius-lg' => '0.65rem', '--kosar-radius-xl' => '0.85rem'],
        ];

        $shadow = [
            'soft' => ['--kosar-shadow' => '0 1px 3px rgb(15 23 42 / 0.06), 0 8px 24px rgb(15 23 42 / 0.05)'],
            'standard' => ['--kosar-shadow' => '0 2px 8px rgb(15 23 42 / 0.06), 0 14px 34px rgb(15 23 42 / 0.07)'],
            'lifted' => ['--kosar-shadow' => '0 4px 12px rgb(15 23 42 / 0.08), 0 22px 48px rgb(15 23 42 / 0.1)'],
        ];

        return array_merge(
            $palettes[$values['theme_palette']] ?? $palettes['navy'],
            $radius[$values['theme_radius']] ?? $radius['balanced'],
            $shadow[$values['theme_shadow']] ?? $shadow['soft'],
        );
    }

    /** @return list<string> */
    public static function bodyClasses(?array $values = null): array
    {
        $values ??= self::values();

        return [
            'theme-palette-'.$values['theme_palette'],
            'theme-radius-'.$values['theme_radius'],
            'theme-shadow-'.$values['theme_shadow'],
            'theme-home-'.$values['theme_home_layout'],
            'theme-home-banner-'.$values['theme_home_banner'],
            'theme-home-products-'.$values['theme_home_products'],
            'theme-card-'.$values['theme_card_density'],
            'theme-product-card-'.$values['theme_product_card_style'],
            'theme-catalog-'.$values['theme_catalog_grid'],
            'theme-pdp-'.$values['theme_pdp_layout'],
            'theme-pdp-gallery-'.$values['theme_pdp_gallery'],
            'theme-header-'.$values['theme_header_density'],
            'theme-header-style-'.$values['theme_header_style'],
            'theme-header-search-'.$values['theme_header_search'],
            'theme-header-icons-'.$values['theme_header_icons'],
            'theme-nav-'.$values['theme_nav_style'],
            'theme-footer-'.$values['theme_footer_style'],
            'theme-footer-columns-'.$values['theme_footer_columns'],
            'theme-footer-trust-'.$values['theme_footer_trust'],
            'theme-footer-newsletter-'.$values['theme_footer_newsletter'],
            'theme-motion-'.$values['theme_motion'],
        ];
    }

    public static function inlineStyle(?array $values = null): string
    {
        return collect(self::cssVariables($values))
            ->map(fn (string $value, string $key) => $key.': '.$value)
            ->implode('; ');
    }

    public static function customCss(): string
    {
        return self::sanitizeCustomCss(SiteSetting::get(self::CUSTOM_CSS_KEY, '') ?? '');
    }

    /** @return list<array{id: string, name: string, created_at: string, values: array<string, string>}> */
    public static function backups(): array
    {
        $decoded = json_decode(SiteSetting::get(self::BACKUPS_KEY, '[]') ?? '[]', true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($backup) => is_array($backup) && isset($backup['id'], $backup['values']) && is_array($backup['values']))
            ->map(function (array $backup) {
                return [
                    'id' => (string) $backup['id'],
                    'name' => (string) ($backup['name'] ?? 'Tema yedeği'),
                    'created_at' => (string) ($backup['created_at'] ?? now()->toDateTimeString()),
                    'values' => self::sanitizeBackupValues($backup['values']),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{id: string, name: string, created_at: string, values: array<string, string>} */
    public static function createBackup(?string $name = null): array
    {
        $backup = [
            'id' => str_replace('.', '', uniqid('theme_', true)),
            'name' => self::backupName($name),
            'created_at' => now()->toDateTimeString(),
            'values' => self::currentBackupValues(),
        ];

        $backups = array_slice([$backup, ...self::backups()], 0, self::BACKUP_LIMIT);
        self::saveBackups($backups);

        return $backup;
    }

    public static function restoreBackup(string $id): bool
    {
        $backup = collect(self::backups())->firstWhere('id', $id);
        if (! is_array($backup)) {
            return false;
        }

        self::createBackup('Geri yükleme öncesi otomatik yedek');

        $values = self::sanitizeBackupValues($backup['values'] ?? []);
        foreach (self::KEYS as $key) {
            SiteSetting::set($key, $values[$key] ?? self::DEFAULTS[$key]);
        }
        SiteSetting::set(self::CUSTOM_CSS_KEY, $values[self::CUSTOM_CSS_KEY] ?? '');

        return true;
    }

    public static function deleteBackup(string $id): bool
    {
        $backups = self::backups();
        $remaining = array_values(array_filter($backups, fn (array $backup) => $backup['id'] !== $id));

        if (count($remaining) === count($backups)) {
            return false;
        }

        self::saveBackups($remaining);

        return true;
    }

    public static function sanitizeCustomCss(?string $css): string
    {
        $css = trim((string) $css);
        $css = mb_substr($css, 0, self::CUSTOM_CSS_MAX_LENGTH);

        $blocked = [
            '#</?\s*style[^>]*>#i',
            '#</?\s*script[^>]*>#i',
            '#@import\b[^;]*;?#i',
            '#expression\s*\([^)]*\)#i',
            '#javascript\s*:#i',
            '#behavior\s*:#i',
            '#-moz-binding\s*:#i',
        ];

        return trim(preg_replace($blocked, '', $css) ?? '');
    }

    /** @return array<string, string> */
    private static function currentBackupValues(): array
    {
        return array_merge(self::values(), [
            self::CUSTOM_CSS_KEY => self::customCss(),
        ]);
    }

    /** @param array<string, mixed> $values */
    private static function sanitizeBackupValues(array $values): array
    {
        $sanitized = self::sanitize($values);
        $sanitized[self::CUSTOM_CSS_KEY] = self::sanitizeCustomCss($values[self::CUSTOM_CSS_KEY] ?? '');

        return $sanitized;
    }

    /** @param list<array{id: string, name: string, created_at: string, values: array<string, string>}> $backups */
    private static function saveBackups(array $backups): void
    {
        SiteSetting::set(self::BACKUPS_KEY, json_encode($backups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function backupName(?string $name): string
    {
        $name = trim((string) $name);

        return mb_substr($name !== '' ? $name : 'Tema yedeği', 0, 80);
    }

    private static function normalize(string $key, mixed $value): string
    {
        $value = is_string($value) ? $value : (string) $value;

        return array_key_exists($value, self::OPTIONS[$key] ?? [])
            ? $value
            : self::DEFAULTS[$key];
    }
}
