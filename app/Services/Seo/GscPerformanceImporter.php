<?php

namespace App\Services\Seo;

use RuntimeException;
use ZipArchive;

class GscPerformanceImporter
{
    private const QUERY_SHEET_NAMES = ['Sorgular', 'Queries', 'Sorgu'];

    /**
     * @return array{
     *     imported_at: string,
     *     source_file: string,
     *     queries: list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>,
     *     opportunities: array{
     *         near_first_page: list<array{query: string, clicks: int, impressions: int, position: float}>,
     *         high_impressions_no_clicks: list<array{query: string, impressions: int, position: float}>
     *     },
     *     totals: array{clicks: int, impressions: int, query_count: int}
     * }
     */
    public function import(string $filePath): array
    {
        if (! is_readable($filePath)) {
            throw new RuntimeException('Dosya okunamadi: '.$filePath);
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('GSC xlsx dosyasi acilamadi: '.$filePath);
        }

        $sharedStrings = $this->loadSharedStrings($zip);
        $sheetPath = $this->resolveQuerySheetPath($zip);

        if ($sheetPath === null) {
            $zip->close();
            throw new RuntimeException('Sorgular sayfasi bulunamadi. GSC performans exportu kullanin.');
        }

        $queries = $this->parseQuerySheet($zip->getFromName($sheetPath), $sharedStrings);
        $zip->close();

        usort($queries, fn (array $a, array $b): int => $b['impressions'] <=> $a['impressions']);

        return [
            'imported_at' => now()->toIso8601String(),
            'source_file' => basename($filePath),
            'queries' => $queries,
            'opportunities' => $this->buildOpportunities($queries),
            'totals' => [
                'clicks' => array_sum(array_column($queries, 'clicks')),
                'impressions' => array_sum(array_column($queries, 'impressions')),
                'query_count' => count($queries),
            ],
        ];
    }

    /** @return list<string> */
    private function loadSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = simplexml_load_string($xml);
        if ($doc === false) {
            return [];
        }

        $strings = [];
        foreach ($doc->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;

                continue;
            }

            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function resolveQuerySheetPath(ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook === false) {
            return null;
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return null;
        }

        $workbookDoc = simplexml_load_string($workbook);
        $relsDoc = simplexml_load_string($relsXml);
        if ($workbookDoc === false || $relsDoc === false) {
            return null;
        }

        $workbookDoc->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $relsDoc->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relMap = [];
        foreach ($relsDoc->Relationship as $rel) {
            $relMap[(string) $rel['Id']] = (string) $rel['Target'];
        }

        foreach ($workbookDoc->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            if (! in_array($name, self::QUERY_SHEET_NAMES, true)) {
                continue;
            }

            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $target = $relMap[$rid] ?? null;
            if ($target === null) {
                continue;
            }

            return str_starts_with($target, 'worksheets/')
                ? 'xl/'.$target
                : 'xl/worksheets/'.basename($target);
        }

        return null;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>
     */
    private function parseQuerySheet(string|false $xml, array $sharedStrings): array
    {
        if ($xml === false) {
            return [];
        }

        $doc = simplexml_load_string($xml);
        if ($doc === false) {
            return [];
        }

        $rows = [];
        foreach ($doc->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = preg_replace('/\d+/', '', $ref) ?: 'A';
                $cells[$col] = $this->cellValue($cell, $sharedStrings);
            }
            $rows[] = $cells;
        }

        if ($rows === []) {
            return [];
        }

        array_shift($rows);

        $queries = [];
        foreach ($rows as $cells) {
            $query = trim((string) ($cells['A'] ?? ''));
            if ($query === '' || $this->shouldSkipQuery($query)) {
                continue;
            }

            $queries[] = [
                'query' => $query,
                'clicks' => (int) round((float) ($cells['B'] ?? 0)),
                'impressions' => (int) round((float) ($cells['C'] ?? 0)),
                'ctr' => round((float) ($cells['D'] ?? 0), 4),
                'position' => round((float) ($cells['E'] ?? 0), 2),
            ];
        }

        return $queries;
    }

    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $value = (string) ($cell->v ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function shouldSkipQuery(string $query): bool
    {
        if (str_starts_with($query, 'http://') || str_starts_with($query, 'https://')) {
            return true;
        }

        $headers = [
            'En çok yapılan sorgular',
            'En alakalı sayfalar',
            'Top queries',
            'Sorgu',
            'Query',
        ];

        return in_array($query, $headers, true);
    }

    /**
     * @param  list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>  $queries
     * @return array{
     *     near_first_page: list<array{query: string, clicks: int, impressions: int, position: float}>,
     *     high_impressions_no_clicks: list<array{query: string, impressions: int, position: float}>
     * }
     */
    private function buildOpportunities(array $queries): array
    {
        $nearFirstPage = [];
        $noClicks = [];

        foreach ($queries as $row) {
            if ($row['impressions'] < 5) {
                continue;
            }

            if ($row['position'] >= 4 && $row['position'] <= 15) {
                $nearFirstPage[] = [
                    'query' => $row['query'],
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'position' => $row['position'],
                ];
            }

            if ($row['clicks'] === 0 && $row['impressions'] >= 10) {
                $noClicks[] = [
                    'query' => $row['query'],
                    'impressions' => $row['impressions'],
                    'position' => $row['position'],
                ];
            }
        }

        usort($nearFirstPage, fn (array $a, array $b): int => $a['position'] <=> $b['position']);
        usort($noClicks, fn (array $a, array $b): int => $b['impressions'] <=> $a['impressions']);

        return [
            'near_first_page' => array_slice($nearFirstPage, 0, 25),
            'high_impressions_no_clicks' => array_slice($noClicks, 0, 25),
        ];
    }
}
