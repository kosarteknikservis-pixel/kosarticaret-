<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductFixesTransferCommand extends Command
{
    private const TEXT_FIELDS = ['sku', 'name', 'short_description', 'description', 'meta_title', 'meta_description', 'image_alt'];

    private const ARRAY_FIELDS = ['specs', 'tags'];

    protected $signature = 'catalog:product-fixes
                            {action : export veya import}
                            {--path=database/product-fixes/urun-duzeltme-2026-09-29.json : JSON dosyası}
                            {--baseline= : (export) Değişiklik öncesi SQLite yedeği}
                            {--slug=* : Sadece belirtilen ürün slug(lar)ı}
                            {--apply : (import) Değişiklikleri yaz; verilmezse yalnızca deneme raporu}
                            {--force : (import) Canlı değer beklenen eski değerden farklı olsa da üzerine yaz}';

    protected $description = 'Ürün düzeltmelerini slug ile eşleyerek JSON üzerinden canlıya taşır (varsayılan: deneme modu)';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'export' => $this->export(),
            'import' => $this->import(),
            default => $this->invalidAction(),
        };
    }

    private function invalidAction(): int
    {
        $this->error('Geçersiz işlem. "export" veya "import" kullanın.');

        return self::FAILURE;
    }

    private function export(): int
    {
        $baseline = (string) $this->option('baseline');
        $baselinePath = $baseline !== '' && ! File::exists($baseline) ? base_path($baseline) : $baseline;

        if ($baselinePath === '' || ! File::exists($baselinePath)) {
            $this->error('--baseline ile mevcut bir SQLite yedeği verin.');

            return self::FAILURE;
        }

        config(['database.connections.product_fixes_baseline' => [
            'driver' => 'sqlite',
            'database' => $baselinePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        $columns = array_merge(['id', 'slug'], self::TEXT_FIELDS, self::ARRAY_FIELDS);
        $before = DB::connection('product_fixes_baseline')->table('products')->get($columns)->keyBy('id');
        $slugs = array_filter((array) $this->option('slug'));

        $products = [];
        $fieldCounts = [];

        DB::table('products')
            ->when($slugs !== [], fn ($query) => $query->whereIn('slug', $slugs))
            ->orderBy('id')
            ->get($columns)
            ->each(function ($row) use ($before, &$products, &$fieldCounts) {
                $old = $before->get($row->id);

                if ($old === null || $old->slug !== $row->slug) {
                    return;
                }

                $changes = [];

                foreach (self::TEXT_FIELDS as $field) {
                    if ($this->textHash($old->{$field}) !== $this->textHash($row->{$field})) {
                        $changes[$field] = ['old_hash' => $this->textHash($old->{$field}), 'new' => $row->{$field}];
                    }
                }

                foreach (self::ARRAY_FIELDS as $field) {
                    $oldValue = $this->decodeArray($old->{$field});
                    $newValue = $this->decodeArray($row->{$field});

                    if ($this->arrayHash($oldValue) !== $this->arrayHash($newValue)) {
                        $changes[$field] = ['old_hash' => $this->arrayHash($oldValue), 'new' => $newValue];
                    }
                }

                if ($changes === []) {
                    return;
                }

                foreach (array_keys($changes) as $field) {
                    $fieldCounts[$field] = ($fieldCounts[$field] ?? 0) + 1;
                }

                $products[] = ['slug' => $row->slug, 'name' => $row->name, 'changes' => $changes];
            });

        if ($products === []) {
            $this->warn('Yedeğe göre değişen ürün bulunamadı.');

            return self::SUCCESS;
        }

        $path = base_path($this->option('path'));
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'type' => 'kosar-product-fixes',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'count' => count($products),
            'products' => $products,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $this->info("Ürün düzeltme dosyası hazır: {$path}");
        $this->line('Ürün sayısı: '.count($products));
        ksort($fieldCounts);
        $this->table(['Alan', 'Ürün'], collect($fieldCounts)->map(fn ($count, $field) => [$field, $count])->values()->all());

        return self::SUCCESS;
    }

    private function import(): int
    {
        $path = base_path($this->option('path'));

        if (! File::exists($path)) {
            $this->error("Dosya bulunamadı: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true);

        if (($payload['type'] ?? null) !== 'kosar-product-fixes') {
            $this->error('Dosya ürün düzeltme dosyası değil.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $slugs = array_filter((array) $this->option('slug'));
        $entries = collect($payload['products'] ?? [])
            ->when($slugs !== [], fn ($items) => $items->whereIn('slug', $slugs))
            ->values();

        $stats = ['products' => 0, 'missing' => 0, 'fields' => 0, 'current' => 0, 'conflicts' => 0];
        $fieldCounts = [];
        $missing = [];
        $conflicts = [];

        $run = function () use ($entries, $apply, $force, &$stats, &$fieldCounts, &$missing, &$conflicts) {
            foreach ($entries as $entry) {
                $product = Product::query()->where('slug', $entry['slug'])->first();

                if ($product === null) {
                    $stats['missing']++;
                    $missing[] = $entry['slug'];

                    continue;
                }

                $dirty = false;

                foreach ($entry['changes'] as $field => $change) {
                    $isArray = in_array($field, self::ARRAY_FIELDS, true);

                    if (! $isArray && ! in_array($field, self::TEXT_FIELDS, true)) {
                        continue;
                    }

                    $current = $isArray ? $product->getAttribute($field) : $product->getRawOriginal($field);
                    $currentHash = $isArray ? $this->arrayHash($current) : $this->textHash($current);
                    $newHash = $isArray ? $this->arrayHash($change['new']) : $this->textHash($change['new']);

                    if ($currentHash === $newHash) {
                        $stats['current']++;

                        continue;
                    }

                    if ($field === 'sku' && filled($change['new']) && Product::query()
                        ->where('sku', $change['new'])
                        ->whereKeyNot($product->getKey())
                        ->exists()) {
                        $stats['conflicts']++;
                        $conflicts[] = [$entry['slug'], $field, 'Stok kodu başka üründe kullanılıyor'];

                        continue;
                    }

                    if ($currentHash !== $change['old_hash'] && ! $force) {
                        $stats['conflicts']++;
                        $conflicts[] = [$entry['slug'], $field, 'Canlı değer beklenenden farklı'];

                        continue;
                    }

                    $product->{$field} = $change['new'];
                    $fieldCounts[$field] = ($fieldCounts[$field] ?? 0) + 1;
                    $stats['fields']++;
                    $dirty = true;
                }

                if ($dirty) {
                    $stats['products']++;

                    if ($apply) {
                        $product->save();
                    }
                }
            }
        };

        $apply ? DB::transaction($run) : $run();

        $this->line($apply ? 'UYGULANDI' : 'DENEME MODU (veritabanına yazılmadı; uygulamak için --apply)');
        $this->table(['Sonuç', 'Adet'], [
            ['Dosyadaki ürün', $entries->count()],
            ['Güncellenecek ürün', $stats['products']],
            ['Güncellenecek alan', $stats['fields']],
            ['Zaten güncel alan', $stats['current']],
            ['Çakışan alan (atlandı)', $stats['conflicts']],
            ['Canlıda bulunmayan ürün', $stats['missing']],
        ]);

        if ($fieldCounts !== []) {
            ksort($fieldCounts);
            $this->table(['Alan', 'Güncellenecek'], collect($fieldCounts)->map(fn ($count, $field) => [$field, $count])->values()->all());
        }

        if ($conflicts !== []) {
            $this->warn('Çakışmalar (canlıda sonradan düzenlenmiş olabilir; kontrol edip gerekirse --slug=... --force ile yazın):');
            $this->table(['Slug', 'Alan', 'Neden'], $conflicts);
        }

        if ($missing !== []) {
            $this->warn('Canlıda bulunmayan slug(lar):');
            foreach ($missing as $slug) {
                $this->line("- {$slug}");
            }
        }

        return self::SUCCESS;
    }

    private function textHash(mixed $value): string
    {
        return sha1(str_replace("\r\n", "\n", (string) $value));
    }

    private function arrayHash(mixed $value): string
    {
        return sha1(json_encode(is_array($value) ? $value : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = is_string($value) && $value !== '' ? json_decode($value, true) : null;

        return is_array($decoded) ? $decoded : [];
    }
}
