<?php

namespace App\Support;

class SeoScore
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{score: int, grade: string, grade_class: string, checks: list<array{id: string, label: string, status: string, message: string, points: int, max: int}>}
     */
    public static function analyze(string $type, array $data): array
    {
        $checks = match ($type) {
            'product' => self::productChecks($data),
            'category' => self::categoryChecks($data),
            'brand' => self::brandChecks($data),
            'blog' => self::blogChecks($data),
            'page' => self::pageChecks($data),
            default => [],
        };

        $earned = (int) collect($checks)->sum('points');
        $max = (int) collect($checks)->sum('max');
        $score = $max > 0 ? (int) round(($earned / $max) * 100) : 0;

        return [
            'score' => min(100, max(0, $score)),
            'grade' => self::gradeLabel($score),
            'grade_class' => self::gradeClass($score),
            'checks' => $checks,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{id: string, label: string, status: string, message: string, points: int, max: int}>
     */
    private static function productChecks(array $data): array
    {
        $name = (string) ($data['name'] ?? '');
        $metaTitle = (string) ($data['meta_title'] ?? '');
        $metaDesc = (string) ($data['meta_description'] ?? '');
        $short = (string) ($data['short_description'] ?? '');
        $body = (string) ($data['description'] ?? '');
        $tags = $data['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        return [
            self::check('name', 'Ürün adı', $name !== '', $name !== '' ? 10 : 0, 10, 'Ürün adı zorunlu.'),
            self::check('slug', 'URL (slug)', ($data['slug'] ?? '') !== '', ($data['slug'] ?? '') !== '' ? 5 : 0, 5, 'SEO dostu URL için slug girin.'),
            self::checkLength('meta_title', 'SEO başlık', $metaTitle, 45, 65, 15, true),
            self::checkLength('meta_description', 'SEO açıklama', $metaDesc, 120, 165, 15, true),
            self::checkLength('short_description', 'Kısa açıklama', $short, 60, 200, 10, true),
            self::checkBody('description', 'Ürün açıklaması', $body, 200, 15),
            self::checkStructure('description_structure', 'Başlık yapısı (H2/H3)', $body, 10),
            self::checkTags($tags, 10),
            self::check('image', 'Kapak görseli', (bool) ($data['has_image'] ?? false), (bool) ($data['has_image'] ?? false) ? 10 : 0, 10, 'Görsel zengin sonuç ve tıklanma oranı için önemli.'),
            self::check('sku', 'SKU / stok kodu', ($data['sku'] ?? '') !== '', ($data['sku'] ?? '') !== '' ? 5 : 0, 5, 'Benzersiz SKU ürün şeması için önerilir.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{id: string, label: string, status: string, message: string, points: int, max: int}>
     */
    private static function categoryChecks(array $data): array
    {
        $name = (string) ($data['name'] ?? '');
        $body = (string) ($data['description'] ?? '');

        return [
            self::check('name', 'Kategori adı', $name !== '', $name !== '' ? 15 : 0, 15, 'Kategori adı zorunlu.'),
            self::check('slug', 'URL (slug)', ($data['slug'] ?? '') !== '', ($data['slug'] ?? '') !== '' ? 10 : 0, 10, 'Slug girin veya otomatik üretin.'),
            self::checkLength('meta_title', 'SEO başlık', (string) ($data['meta_title'] ?? ''), 45, 65, 20, true),
            self::checkLength('meta_description', 'SEO açıklama', (string) ($data['meta_description'] ?? ''), 120, 165, 20, true),
            self::checkBody('description', 'Kategori açıklaması', $body, 120, 20),
            self::checkStructure('description_structure', 'Başlık yapısı', $body, 10),
            self::check('image', 'Kategori görseli', (bool) ($data['has_image'] ?? false), (bool) ($data['has_image'] ?? false) ? 5 : 0, 5, 'Görsel kategori kartlarında güven verir.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{id: string, label: string, status: string, message: string, points: int, max: int}>
     */
    private static function blogChecks(array $data): array
    {
        $title = (string) ($data['title'] ?? '');
        $body = (string) ($data['content'] ?? '');

        return [
            self::check('title', 'Başlık', $title !== '', $title !== '' ? 15 : 0, 15, 'Blog başlığı zorunlu.'),
            self::check('slug', 'URL (slug)', ($data['slug'] ?? '') !== '', ($data['slug'] ?? '') !== '' ? 10 : 0, 10, 'SEO dostu slug.'),
            self::checkLength('meta_title', 'SEO başlık', (string) ($data['meta_title'] ?? ''), 45, 65, 20, true),
            self::checkLength('meta_description', 'SEO açıklama', (string) ($data['meta_description'] ?? ''), 120, 165, 20, true),
            self::checkLength('excerpt', 'Özet', (string) ($data['excerpt'] ?? ''), 80, 200, 15, true),
            self::checkBody('content', 'İçerik', $body, 300, 25),
            self::checkStructure('content_structure', 'Başlık yapısı', $body, 10),
            self::check('image', 'Kapak görseli', (bool) ($data['has_image'] ?? false), (bool) ($data['has_image'] ?? false) ? 5 : 0, 5, 'OG ve Google Discover için önerilir.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{id: string, label: string, status: string, message: string, points: int, max: int}>
     */
    private static function pageChecks(array $data): array
    {
        $title = (string) ($data['title'] ?? '');
        $body = (string) ($data['content'] ?? '');

        return [
            self::check('title', 'Sayfa başlığı', $title !== '', $title !== '' ? 15 : 0, 15, 'Başlık zorunlu.'),
            self::check('slug', 'URL (slug)', ($data['slug'] ?? '') !== '', ($data['slug'] ?? '') !== '' ? 10 : 0, 10, 'Slug girin.'),
            self::checkLength('meta_title', 'SEO başlık', (string) ($data['meta_title'] ?? ''), 45, 65, 25, true),
            self::checkLength('meta_description', 'SEO açıklama', (string) ($data['meta_description'] ?? ''), 120, 165, 25, true),
            self::checkBody('content', 'Sayfa içeriği', $body, 150, 25),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{id: string, label: string, status: string, message: string, points: int, max: int}>
     */
    private static function brandChecks(array $data): array
    {
        $name = (string) ($data['name'] ?? '');
        $body = (string) ($data['description'] ?? '');

        return [
            self::check('name', 'Marka adı', $name !== '', $name !== '' ? 15 : 0, 15, 'Marka adı zorunlu.'),
            self::check('slug', 'URL (slug)', ($data['slug'] ?? '') !== '', ($data['slug'] ?? '') !== '' ? 10 : 0, 10, 'Marka sayfası URL’si.'),
            self::checkLength('meta_title', 'SEO başlık', (string) ($data['meta_title'] ?? ''), 45, 65, 20, true),
            self::checkLength('meta_description', 'SEO açıklama', (string) ($data['meta_description'] ?? ''), 120, 165, 20, true),
            self::checkBody('description', 'Marka açıklaması', $body, 100, 20),
            self::checkStructure('description_structure', 'Başlık yapısı', $body, 10),
            self::check('logo', 'Marka logosu', (bool) ($data['has_image'] ?? false), (bool) ($data['has_image'] ?? false) ? 5 : 0, 5, 'Logo marka şeması ve güven için.'),
        ];
    }

    /**
     * @param  list<string>  $tags
     * @return array{id: string, label: string, status: string, message: string, points: int, max: int}
     */
    private static function checkTags(array $tags, int $max): array
    {
        $count = count($tags);
        if ($count >= 3 && $count <= 10) {
            return self::check('tags', 'Anahtar kelimeler', true, $max, $max, "{$count} kelime — ideal aralık.");
        }
        if ($count > 0) {
            return self::check('tags', 'Anahtar kelimeler', true, (int) round($max * 0.5), $max, "{$count} kelime; 3–10 önerilir.");
        }

        return self::check('tags', 'Anahtar kelimeler', false, 0, $max, 'En az 3 anahtar kelime ekleyin.');
    }

    private static function checkBody(string $id, string $label, string $body, int $minWords, int $max): array
    {
        $words = RichContent::wordCount($body);
        if ($words >= $minWords) {
            return self::check($id, $label, true, $max, $max, "{$words} kelime — yeterli içerik.");
        }
        if ($words >= (int) ($minWords * 0.4)) {
            return self::check($id, $label, true, (int) round($max * 0.55), $max, "{$words} kelime; hedef {$minWords}+.");
        }

        return self::check($id, $label, false, 0, $max, "En az {$minWords} kelime önerilir (şu an {$words}).");
    }

    private static function checkStructure(string $id, string $label, string $body, int $max): array
    {
        if (RichContent::hasHeading($body)) {
            return self::check($id, $label, true, $max, $max, 'H2/H3 başlıkları mevcut.');
        }
        $words = RichContent::wordCount($body);
        if ($words >= 80 && ! RichContent::isHtml($body)) {
            $paragraphs = count(array_filter(preg_split('/\n\s*\n/', trim($body)) ?: []));

            if ($paragraphs >= 2) {
                return self::check($id, $label, true, (int) round($max * 0.7), $max, 'Paragraflar ayrılmış; HTML’de H2 ekleyebilirsiniz.');
            }
        }

        return self::check($id, $label, false, 0, $max, 'HTML modunda H2/H3 veya düz metinde paragraflar kullanın.');
    }

    private static function checkLength(
        string $id,
        string $label,
        string $value,
        int $min,
        int $max,
        int $points,
        bool $optionalFallback = false,
    ): array {
        $len = mb_strlen($value);
        if ($len === 0 && $optionalFallback) {
            return self::check($id, $label, true, (int) round($points * 0.35), $points, 'Boş — vitrin otomatik üretecek; özelleştirmeniz önerilir.');
        }
        if ($len >= $min && $len <= $max) {
            return self::check($id, $label, true, $points, $points, "{$len} karakter — ideal.");
        }
        if ($len > 0 && $len < $min) {
            return self::check($id, $label, true, (int) round($points * 0.6), $points, "{$len} karakter; hedef {$min}–{$max}.");
        }
        if ($len > $max) {
            return self::check($id, $label, true, (int) round($points * 0.75), $points, "{$len} karakter; biraz kısaltın ({$max}).");
        }

        return self::check($id, $label, false, 0, $points, "Hedef {$min}–{$max} karakter.");
    }

    private static function check(string $id, string $label, bool $ok, int $points, int $max, string $message): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'status' => $points >= $max ? 'good' : ($points > 0 ? 'warn' : 'bad'),
            'message' => $message,
            'points' => $points,
            'max' => $max,
        ];
    }

    private static function gradeLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Mükemmel',
            $score >= 70 => 'İyi',
            $score >= 50 => 'Orta',
            default => 'Geliştirilmeli',
        };
    }

    private static function gradeClass(int $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'fair',
            default => 'poor',
        };
    }
}
