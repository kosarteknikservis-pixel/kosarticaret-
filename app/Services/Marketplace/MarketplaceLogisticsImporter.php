<?php

namespace App\Services\Marketplace;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class MarketplaceLogisticsImporter
{
    /**
     * @return array{updated: int, skipped: int, missing: list<string>}
     */
    public function importFromCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new InvalidArgumentException('CSV dosyası okunamadı.');
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('CSV dosyası açılamadı.');
        }

        $header = null;
        $updated = 0;
        $skipped = 0;
        $missing = [];

        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeader($line);
                continue;
            }

            if ($this->rowEmpty($line)) {
                continue;
            }

            $row = array_combine($header, array_pad($line, count($header), ''));

            if ($row === false) {
                $skipped++;
                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));

            if ($sku === '') {
                $skipped++;
                continue;
            }

            $product = Product::query()->where('sku', $sku)->first();

            if (! $product) {
                $missing[] = $sku;
                continue;
            }

            $product->update(array_filter([
                'barcode' => $this->nullableString($row['barcode'] ?? null),
                'weight_kg' => $this->nullableDecimal($row['weight_kg'] ?? $row['weight'] ?? null),
                'width_cm' => $this->nullableDecimal($row['width_cm'] ?? $row['width'] ?? null),
                'height_cm' => $this->nullableDecimal($row['height_cm'] ?? $row['height'] ?? null),
                'depth_cm' => $this->nullableDecimal($row['depth_cm'] ?? $row['depth'] ?? null),
                'vat_rate' => $this->nullableDecimal($row['vat_rate'] ?? null),
            ], fn ($value) => $value !== null));

            $updated++;
        }

        fclose($handle);

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    /** @param  list<string>  $line */
    private function normalizeHeader(array $line): array
    {
        return array_map(function (string $column): string {
            $column = strtolower(trim($column));
            $column = str_replace([' ', '-'], '_', $column);

            return match ($column) {
                'ean', 'gtin' => 'barcode',
                'genislik' => 'width_cm',
                'yukseklik' => 'height_cm',
                'derinlik', 'uzunluk' => 'depth_cm',
                'agirlik' => 'weight_kg',
                'kdv' => 'vat_rate',
                default => $column,
            };
        }, $line);
    }

    /** @param  list<string|null>  $line */
    private function rowEmpty(array $line): bool
    {
        return trim(implode('', $line)) === '';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $value = trim(str_replace(',', '.', (string) $value));

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? $value : null;
    }
}
