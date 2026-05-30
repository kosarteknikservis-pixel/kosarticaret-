<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SanitizeWooCommerceCsvCommand extends Command
{
    protected $signature = 'catalog:sanitize-csv
                            {input : Bozuk veya uyarı içeren CSV}
                            {--output=wc-product-export-clean.csv : Temiz çıktı dosyası}';

    protected $description = 'WooCommerce CSV başındaki PHP uyarılarını temizler ve UTF-8 düzeltir';

    public function handle(): int
    {
        $input = (string) $this->argument('input');
        if (! str_contains($input, DIRECTORY_SEPARATOR)) {
            $input = base_path($input);
        }

        if (! is_file($input)) {
            $this->error("Dosya bulunamadı: {$input}");

            return self::FAILURE;
        }

        $output = (string) $this->option('output');
        if (! str_contains($output, DIRECTORY_SEPARATOR)) {
            $output = base_path($output);
        }

        $content = file_get_contents($input);
        if ($content === false) {
            $this->error('Dosya okunamadı.');

            return self::FAILURE;
        }

        $stripped = $this->stripPhpWarnings($content);
        $fixed = $this->fixEncoding($stripped);
        $delimiter = substr_count(strtok($fixed, "\n") ?: '', ';')
            > substr_count(strtok($fixed, "\n") ?: '', ',')
            ? ';' : ',';

        file_put_contents($output, $fixed);
        $this->line('Ayırıcı: '.($delimiter === ';' ? 'noktalı virgül (;) — Excel TR' : 'virgül (,)')));

        $lines = substr_count($fixed, "\n");
        $this->info("Temiz CSV yazıldı: {$output}");
        $this->line("Satır sayısı (yaklaşık): {$lines}");
        $this->line('Sonraki adım: php artisan catalog:import-woocommerce --file='.basename($output).' --force --limit=10');

        return self::SUCCESS;
    }

    private function stripPhpWarnings(string $content): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $clean = [];
        $headerFound = false;

        foreach ($lines as $line) {
            $trim = trim($line);
            if (! $headerFound) {
                if ($trim === '' || str_starts_with($trim, '<')) {
                    continue;
                }
                if (stripos($line, 'SKU') !== false && (stripos($line, 'İsim') !== false || stripos($line, 'Name') !== false)) {
                    $headerFound = true;
                    $clean[] = $line;
                    continue;
                }
                continue;
            }
            $clean[] = $line;
        }

        if (! $headerFound) {
            throw new \RuntimeException('CSV başlık satırı bulunamadı (SKU + İsim/Name).');
        }

        return implode("\n", $clean);
    }

    private function fixEncoding(string $content): string
    {
        $headerSample = substr($content, 0, 4000);
        $needsCp1254 = ! mb_check_encoding($headerSample, 'UTF-8')
            || str_contains($headerSample, "\u{FFFD}")
            || (str_contains($headerSample, 'Kimlik') && ! str_contains($headerSample, 'İsim'));

        if ($needsCp1254) {
            $from1254 = @mb_convert_encoding($content, 'UTF-8', 'Windows-1254');
            if ($from1254 !== false) {
                $content = $from1254;
            }
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    }

    private function looksCorrupted(string $content): bool
    {
        $sample = substr($content, 0, 200000);

        if (preg_match_all('/[a-zA-ZğüşıöçĞÜŞİÖÇ]{2,}\?{1,2}[a-zA-ZğüşıöçĞÜŞİÖÇ]{0,}/u', $sample, $m)) {
            return count($m[0]) > 20;
        }

        return substr_count($sample, '??') > 30;
    }
}
