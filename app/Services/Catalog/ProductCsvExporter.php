<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvExporter
{
    /**
     * @param  Builder<Product>  $query
     */
    public function download(Builder $query, string $filename = 'urunler.csv'): StreamedResponse
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) ?: 'urunler.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'id',
                'sku',
                'name',
                'slug',
                'brand',
                'categories',
                'price',
                'compare_at_price',
                'barcode',
                'weight_kg',
                'width_cm',
                'height_cm',
                'depth_cm',
                'vat_rate',
                'stock',
                'is_active',
                'featured',
                'url',
                'updated_at',
            ], ';');

            $query->with(['brand:id,name', 'categories:id,name'])
                ->orderBy('id')
                ->chunkById(200, function ($products) use ($handle): void {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            $product->sku,
                            $product->name,
                            $product->slug,
                            $product->brand?->name,
                            $product->categories->pluck('name')->implode(' | '),
                            number_format((float) $product->price, 2, '.', ''),
                            $product->compare_at_price !== null
                                ? number_format((float) $product->compare_at_price, 2, '.', '')
                                : '',
                            $product->barcode,
                            $product->weight_kg,
                            $product->width_cm,
                            $product->height_cm,
                            $product->depth_cm,
                            $product->vat_rate,
                            $product->stock,
                            $product->is_active ? '1' : '0',
                            $product->featured ? '1' : '0',
                            route('products.show', $product, absolute: true),
                            $product->updated_at?->format('Y-m-d H:i:s'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
