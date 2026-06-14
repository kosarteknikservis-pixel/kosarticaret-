<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\MarketplaceLogisticsImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class MarketplaceLogisticsImportController extends Controller
{
    public function create(): View
    {
        return view('admin.marketplace.logistics-import');
    }

    public function store(Request $request, MarketplaceLogisticsImporter $importer): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        try {
            $stats = $importer->importFromCsv($request->file('csv_file'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['csv_file' => $e->getMessage()]);
        }

        $message = sprintf(
            '%d ürün güncellendi, %d satır atlandı.',
            $stats['updated'],
            $stats['skipped'],
        );

        if ($stats['missing'] !== []) {
            $message .= ' Bulunamayan SKU: '.implode(', ', array_slice($stats['missing'], 0, 10));
            if (count($stats['missing']) > 10) {
                $message .= '… (+'.(count($stats['missing']) - 10).' daha)';
            }
        }

        return redirect()
            ->route('admin.integrations.marketplace.readiness')
            ->with('success', $message);
    }
}
