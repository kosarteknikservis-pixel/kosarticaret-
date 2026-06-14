<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\MappingBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceMappingBackupController extends Controller
{
    public function export(Request $request, MappingBackupService $backup): StreamedResponse
    {
        $channelKey = $request->query('channel');
        $payload = $backup->export(is_string($channelKey) && $channelKey !== '' ? $channelKey : null);
        $filename = 'pazaryeri-eslestirmeler-'.now()->format('Y-m-d-His').'.json';

        return Response::streamDownload(
            function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function import(Request $request, MappingBackupService $backup): RedirectResponse
    {
        $request->validate([
            'json_file' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
        ]);

        try {
            $counts = $backup->importFromJson($request->file('json_file'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['json_file' => $e->getMessage()]);
        }

        return back()->with('success', sprintf(
            'Import tamamlandı: %d kategori, %d marka, %d özellik, %d harici kategori.',
            $counts['category'],
            $counts['brand'],
            $counts['attribute'],
            $counts['external'],
        ));
    }
}
