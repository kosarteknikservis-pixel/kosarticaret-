<?php

namespace App\Console\Commands;

use App\Support\LegacyProductSlugMatcher;
use App\Support\LegacySlugCategoryGuesser;
use Illuminate\Console\Command;

class FixLegacyProductRedirectsCommand extends Command
{
    protected $signature = 'seo:fix-legacy-redirects';

    protected $description = 'legacy (urun) yonlendirmelerinde /marka/ hedeflerini kategori veya urun yoluna cevirir.';

    public function handle(): int
    {
        $redirects = config('legacy_product_redirects', []);
        $updated = 0;

        foreach ($redirects as $source => $target) {
            if (! str_starts_with((string) $target, '/marka/')) {
                continue;
            }

            $slug = preg_replace('#^/urun/#', '', (string) $source) ?: '';
            $newTarget = LegacyProductSlugMatcher::targetForLegacySlug($slug);

            if ($newTarget === null || str_starts_with($newTarget, '/marka/')) {
                $newTarget = LegacySlugCategoryGuesser::pathForSlug($slug) ?? '/urunler';
            }

            if ($newTarget !== $target) {
                $redirects[$source] = $newTarget;
                $updated++;
            }
        }

        ksort($redirects);
        $this->writeConfig($redirects);

        $this->info("Guncellendi: {$updated} yonlendirme /marka/ → kategori veya urun.");

        return self::SUCCESS;
    }

    /** @param  array<string, string>  $redirects */
    private function writeConfig(array $redirects): void
    {
        $export = var_export($redirects, true);
        $export = preg_replace('/^(\s*)array \(/m', '$1[', $export) ?? $export;
        $export = preg_replace('/\)$/s', '];', $export) ?? $export;

        $contents = <<<PHP
<?php

/**
 * GSC 404 urun slug yonlendirmeleri (otomatik + manuel).
 *
 * @var array<string, string>
 */
return {$export};

PHP;

        file_put_contents(config_path('legacy_product_redirects.php'), $contents);
    }
}
