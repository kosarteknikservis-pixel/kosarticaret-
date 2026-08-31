<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ImportBlogBatchCommand extends Command
{
    protected $signature = 'blog:import-batch
                            {series? : manifest.json içindeki series değeri (ör. b2-2026-09)}
                            {--files=* : Ek JSON dosya adları (manifest dışı)}
                            {--dry-run : Import etmeden listele}
                            {--force : Onay sormadan içe aktar}';

    protected $description = 'Blog kuyruğundan seçili seri veya dosyaları içe aktarır (deploy batch)';

    public function handle(): int
    {
        $manifestPath = base_path('database/blog-queue/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->error('Manifest bulunamadı.');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $series = $this->argument('series');

        $files = collect($manifest['posts'] ?? [])
            ->when(filled($series), fn ($entries) => $entries->filter(
                fn (array $entry) => ($entry['series'] ?? '') === $series
            ))
            ->pluck('file')
            ->filter()
            ->merge($this->option('files'))
            ->unique()
            ->values();

        if ($files->isEmpty()) {
            $this->warn('İçe aktarılacak dosya bulunamadı.');

            return self::SUCCESS;
        }

        $this->line('Batch import: '.$files->count().' dosya');

        foreach ($files as $index => $file) {
            $path = base_path('database/blog-queue/'.$file);

            if (! File::exists($path)) {
                $this->warn("Atlandı (dosya yok): {$file}");

                continue;
            }

            $this->line('- '.$file);

            if ($this->option('dry-run')) {
                continue;
            }

            $code = Artisan::call('blog:import', [
                'path' => 'database/blog-queue/'.$file,
                '--force' => $this->option('force'),
                '--from-queue' => true,
                '--publish-offset' => $index,
            ]);

            if ($code !== self::SUCCESS) {
                $this->error("Import başarısız: {$file}");
                $output = trim(Artisan::output());
                if ($output !== '') {
                    $this->line($output);
                }

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run tamamlandı.');

            return self::SUCCESS;
        }

        $this->call('blog:assign-covers', ['--force' => true]);
        $this->info('Batch blog import tamamlandı.');

        return self::SUCCESS;
    }
}
