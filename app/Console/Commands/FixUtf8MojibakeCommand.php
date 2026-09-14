<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Çift UTF-8 kodlanmış (mojibake) SEO metinlerini onarır.
 * Örn. "DÄ±ÅŸ" → "Dış"
 */
final class FixUtf8MojibakeCommand extends Command
{
    protected $signature = 'seo:fix-utf8-mojibake
                            {--dry-run : Veritabanina yazmadan goster}';

    protected $description = 'Cift kodlanmis Turkce SEO alanlarini (meta/title/description) duzeltir.';

    /** @var list<string> */
    private array $fields = [
        'name', 'description', 'short_description', 'meta_title', 'meta_description', 'buying_guide', 'image_alt',
    ];

    public function handle(): int
    {
        $updated = 0;
        $updated += $this->fixModel(Category::query()->get(), 'categories');
        $updated += $this->fixModel(Brand::query()->get(), 'brands');
        $updated += $this->fixModel(Product::query()->where('sku', 'like', 'HB%')->orWhere('slug', 'like', 'ardonat-%')->get(), 'products');

        $this->info($this->option('dry-run')
            ? "Dry-run: {$updated} kayit duzeltilirdi."
            : "Tamam: {$updated} kayit guncellendi.");

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category|Brand|Product>  $rows
     */
    private function fixModel($rows, string $label): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $dirty = false;
            foreach ($this->fields as $field) {
                if (! array_key_exists($field, $row->getAttributes()) && ! isset($row->{$field})) {
                    continue;
                }
                $value = $row->getAttributes()[$field] ?? null;
                if (! is_string($value) || $value === '') {
                    continue;
                }
                $fixed = $this->repair($value);
                if ($fixed !== null && $fixed !== $value) {
                    $this->line("{$label}#{$row->id}.{$field}: ".$this->preview($value).' → '.$this->preview($fixed));
                    $row->{$field} = $fixed;
                    $dirty = true;
                }
            }

            if (is_array($row->faq ?? null)) {
                $faqFixed = $this->repairDeep($row->faq);
                if ($faqFixed !== $row->faq) {
                    $row->faq = $faqFixed;
                    $dirty = true;
                    $this->line("{$label}#{$row->id}.faq duzeltildi");
                }
            }

            if (is_array($row->specs ?? null)) {
                $specsFixed = $this->repairDeep($row->specs);
                if ($specsFixed !== $row->specs) {
                    $row->specs = $specsFixed;
                    $dirty = true;
                    $this->line("{$label}#{$row->id}.specs duzeltildi");
                }
            }

            if ($dirty) {
                $count++;
                if (! $this->option('dry-run')) {
                    $row->save();
                }
            }
        }

        return $count;
    }

    private function repair(string $value): ?string
    {
        if (! $this->looksMojibake($value)) {
            return null;
        }

        // Double UTF-8: decode once
        $fixed = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $decoded = @utf8_decode($value);
        if (is_string($decoded) && $decoded !== '' && ! $this->looksMojibake($decoded) && $this->hasTurkish($decoded)) {
            return $decoded;
        }

        // Alternative: interpret as Windows-1252 bytes of UTF-8 misread
        $alt = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        if (is_string($alt) && $this->hasTurkish($alt) && ! $this->looksMojibake($alt)) {
            // Sometimes need another pass
            if ($this->looksMojibake($alt)) {
                $alt2 = @utf8_decode($alt);
                if (is_string($alt2) && $this->hasTurkish($alt2)) {
                    return $alt2;
                }
            }

            return $alt;
        }

        return null;
    }

    /** @param mixed $data */
    private function repairDeep(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->repair($data) ?? $data;
        }
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $fk = is_string($k) ? ($this->repair($k) ?? $k) : $k;
                $out[$fk] = $this->repairDeep($v);
            }

            return $out;
        }

        return $data;
    }

    private function looksMojibake(string $value): bool
    {
        return (bool) preg_match('/Ä±|ÅŸ|Ã¢|Ã§|Ã¶|Ã¼|ÄŸ|Å¾|Ã‡|Ã–|Ãœ|Ä°|Â|Ã/u', $value);
    }

    private function hasTurkish(string $value): bool
    {
        return (bool) preg_match('/[ışğüöçİŞĞÜÖÇâîû]/u', $value);
    }

    private function preview(string $value): string
    {
        $plain = preg_replace('/\s+/', ' ', strip_tags($value)) ?? $value;

        return mb_substr($plain, 0, 70);
    }
}
