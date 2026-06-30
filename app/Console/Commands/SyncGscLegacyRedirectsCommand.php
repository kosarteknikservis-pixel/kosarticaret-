<?php

namespace App\Console\Commands;

use App\Support\LegacyProductSlugMatcher;
use Illuminate\Console\Command;

class SyncGscLegacyRedirectsCommand extends Command
{
    protected $signature = 'seo:sync-gsc-redirects {file? : GSC 404 URL listesi (satir basina URL)}';

    protected $description = 'GSC 404 listesinden legacy urun yonlendirmelerini uretir.';

    public function handle(): int
    {
        $file = $this->argument('file')
            ?: storage_path('app/gsc-404-urls.txt');

        if (! is_readable($file)) {
            $this->error('Dosya okunamadi: '.$file);

            return self::FAILURE;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $redirects = config('legacy_product_redirects', []);
        $added = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '/urun/')) {
                continue;
            }

            $path = parse_url($line, PHP_URL_PATH) ?: '';
            if (! preg_match('#^/urun/([^/]+)/?$#', $path, $matches)) {
                continue;
            }

            $legacySlug = urldecode($matches[1]);
            $source = '/urun/'.rtrim($legacySlug, '/');

            if (isset($redirects[$source])) {
                $skipped++;

                continue;
            }

            $target = LegacyProductSlugMatcher::targetForLegacySlug($legacySlug);

            if ($target === null || $target === $source || str_starts_with($target, '/marka/')) {
                $skipped++;

                continue;
            }

            $redirects[$source] = $target;
            $added++;
        }

        ksort($redirects);
        $this->writeConfig($redirects);

        $this->info("Tamamlandi: {$added} yeni yonlendirme, {$skipped} atlandi.");
        $this->line('Dosya: '.config_path('legacy_product_redirects.php'));

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
