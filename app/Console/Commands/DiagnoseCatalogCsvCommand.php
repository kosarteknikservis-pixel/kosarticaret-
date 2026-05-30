<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseCatalogCsvCommand extends Command
{
    protected $signature = 'catalog:diagnose-csv {file : CSV dosyası (proje köküne göre)}';

    protected $description = 'WooCommerce CSV kodlama ve Türkçe karakter bozulmasını analiz eder';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! str_contains($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            $this->error("Dosya bulunamadı: {$path}");

            return self::FAILURE;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->error('Dosya okunamadı.');

            return self::FAILURE;
        }

        $phpWarnings = (bool) preg_match('/^\s*</m', $raw);
        $utf8HeaderOk = str_contains(substr($raw, 0, 4000), 'İsim');
        $utf8Invalid = ! mb_check_encoding(substr($raw, 0, 8000), 'UTF-8')
            || str_contains(substr($raw, 0, 4000), "\u{FFFD}");

        $cp1254 = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1254') ?: '';
        $cp1254HeaderOk = str_contains(substr($cp1254, 0, 4000), 'İsim');

        $this->table(['Kontrol', 'Sonuç'], [
            ['Dosya başında PHP/HTML uyarısı', $phpWarnings ? 'EVET — sanitize gerekli' : 'Hayır'],
            ['UTF-8 başlık (İsim sütunu)', $utf8HeaderOk ? 'OK' : 'Bozuk'],
            ['UTF-8 geçersiz karakter ()', $utf8Invalid ? 'EVET' : 'Hayır'],
            ['Windows-1254 → UTF-8 başlık', $cp1254HeaderOk ? 'OK — dosya CP1254 olmalı' : '—'],
        ]);

        $stats = $this->sampleProductNames($cp1254HeaderOk ? $cp1254 : $raw);
        $this->newLine();
        $this->table(['Ürün adı örneği (ilk 300)', 'Adet'], [
            ['Türkçe harf var (ığüşöçİ…)', (string) $stats['turkish']],
            ['Soru işareti (?) / ??', (string) $stats['broken']],
        ]);

        if ($stats['broken'] > $stats['turkish'] && $stats['broken'] > 0) {
            $this->newLine();
            $this->warn('Ürün metinleri CSV içinde zaten "?" ile kayıtlı — dosya kodlamasından değil, WooCommerce veritabanında bozulmuş.');
            $this->line('Çözüm: kosarticaret.com → phpMyAdmin → wp_posts içeriğini düzeltin veya yedekten geri yükleyin, sonra CSV’yi yeniden indirin.');
            $this->line('Canlı sitede de bozuksa (ürün sayfası başlığı), CSV ile düzeltilemez.');
        } elseif (! $cp1254HeaderOk && $utf8Invalid) {
            $this->info('Önce: php artisan catalog:sanitize-csv '.$this->argument('file'));
        } else {
            $this->info('Dosya import için uygun görünüyor (sanitize sonrası).');
        }

        return self::SUCCESS;
    }

    /** @return array{turkish: int, broken: int} */
    private function sampleProductNames(string $utf8Content): array
    {
        $turkish = 0;
        $broken = 0;
        $delimiter = substr_count(strtok($utf8Content, "\n") ?: '', ';')
            > substr_count(strtok($utf8Content, "\n") ?: '', ',')
            ? ';' : ',';

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $utf8Content);
        rewind($handle);

        $header = null;
        $nameKey = null;
        $count = 0;

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false && $count < 400) {
            if ($header === null) {
                $header = $line;
                foreach ($header as $cell) {
                    $cell = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $cell)) ?? trim((string) $cell);
                    if (in_array($cell, ['İsim', 'Name'], true)) {
                        $nameKey = array_search($cell, $header, true);
                        break;
                    }
                }
                continue;
            }
            if ($nameKey === null || ! isset($line[$nameKey])) {
                continue;
            }
            $name = (string) $line[$nameKey];
            if ($name === '') {
                continue;
            }
            $count++;
            if (preg_match('/[ığüşöçİĞÜŞÖÇ]/u', $name)) {
                $turkish++;
            }
            if (str_contains($name, '?')) {
                $broken++;
            }
        }

        fclose($handle);

        return ['turkish' => $turkish, 'broken' => $broken];
    }
}
