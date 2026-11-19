<?php

/**
 * Aşama A v1 — alan bazlı içerik aktarımı (idempotent).
 *
 * Kullanım:
 *   php scripts/asama-a-content-transfer.php --dry-run
 *   php scripts/asama-a-content-transfer.php --apply
 *   php scripts/asama-a-content-transfer.php --rollback=docs/audit/release/asama-a-v1/backups/<stamp>
 *
 * Kurallar:
 * - Toplu DB kopyası yok.
 * - Yalnız content-ops.json içindeki id+slug + alan.
 * - Exact href rewrite/unwrap.
 * - Hedef zaten uygulanmışsa no-op.
 * - Beklenen eski `from` yoksa ve hedef de yoksa: diverge → yazma.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$args = $argv;
array_shift($args);
$mode = 'dry-run';
$rollbackDir = null;
foreach ($args as $a) {
    if ($a === '--apply') {
        $mode = 'apply';
    } elseif ($a === '--dry-run') {
        $mode = 'dry-run';
    } elseif (str_starts_with($a, '--rollback=')) {
        $mode = 'rollback';
        $rollbackDir = substr($a, strlen('--rollback='));
    }
}

$root = dirname(__DIR__);
$manifestPath = $root.'/docs/audit/release/asama-a-v1/content-ops.json';
$manifest = json_decode(file_get_contents($manifestPath), true);
if (! is_array($manifest)) {
    fwrite(STDERR, "manifest okunamadı\n");
    exit(1);
}

$reportDir = $root.'/docs/audit/release/asama-a-v1';
@mkdir($reportDir.'/backups', 0775, true);

function rewriteOrUnwrap(string $html, string $from, ?string $to): array
{
    $changes = 0;
    $pattern = '/<a\b([^>]*)href=(["\'])([^"\']*)\2([^>]*)>(.*?)<\/a>/is';
    $html = preg_replace_callback($pattern, function ($m) use ($from, $to, &$changes) {
        $href = $m[3];
        $path = parse_url($href, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $href;
        }
        $path = rtrim($path, '/') ?: $path;
        $fromNorm = rtrim($from, '/');
        $abs = 'https://kosarticaret.com'.$fromNorm;
        $absHttp = 'http://kosarticaret.com'.$fromNorm;
        $match = ($path === $fromNorm)
            || ($href === $from)
            || ($href === $fromNorm)
            || ($href === $abs)
            || ($href === $absHttp);
        if (! $match) {
            return $m[0];
        }
        $changes++;
        $inner = $m[5];
        if ($to === null) {
            return $inner;
        }
        $newHref = str_starts_with($href, 'http') ? 'https://kosarticaret.com'.$to : $to;

        return '<a'.$m[1].'href='.$m[2].$newHref.$m[2].$m[4].'>'.$inner.'</a>';
    }, $html ?? '');

    return [$html, $changes];
}

function countHref(string $html, string $path): int
{
    $n = 0;
    if (preg_match_all('/<a\b[^>]*href=(["\'])([^"\']*)\1/i', $html, $m)) {
        foreach ($m[2] as $href) {
            $p = parse_url($href, PHP_URL_PATH) ?: $href;
            $p = rtrim((string) $p, '/') ?: $p;
            if ($p === rtrim($path, '/')) {
                $n++;
            }
        }
    }

    return $n;
}

if ($mode === 'rollback') {
    if (! is_dir($rollbackDir)) {
        fwrite(STDERR, "rollback dir yok: {$rollbackDir}\n");
        exit(1);
    }
    $files = glob(rtrim($rollbackDir, '/\\').'/*.json') ?: [];
    $restored = 0;
    foreach ($files as $file) {
        if (basename($file) === 'index.json') {
            continue;
        }
        $row = json_decode(file_get_contents($file), true);
        if (! $row) {
            continue;
        }
        $table = $row['table'];
        $id = (int) $row['id'];
        $slug = $row['slug'];
        $field = $row['field'];
        $exists = DB::table($table)->where('id', $id)->where('slug', $slug)->exists();
        if (! $exists) {
            echo "SKIP mismatch id/slug {$table}#{$id}\n";
            continue;
        }
        DB::table($table)->where('id', $id)->update([
            $field => $row['before'],
            'updated_at' => now(),
        ]);
        $restored++;
        echo "RESTORED {$table}#{$id} {$slug}.{$field}\n";
    }
    echo "rollback_restored={$restored}\n";
    exit(0);
}

$report = [
    'package' => 'asama-a-v1',
    'mode' => $mode,
    'ran_at' => date('c'),
    'rows' => [],
    'summary' => [
        'apply_ready' => 0,
        'already_done' => 0,
        'diverged' => 0,
        'missing_record' => 0,
        'would_change_fields' => 0,
    ],
];

$backupStamp = date('Ymd-His');
$backupPath = $reportDir.'/backups/'.$backupStamp;
$backupIndex = [];

foreach ($manifest['ops'] as $op) {
    $table = $op['table'];
    $id = (int) $op['id'];
    $slug = $op['slug'];
    $field = $op['field'];
    $row = DB::table($table)->where('id', $id)->first();
    $entry = [
        'table' => $table,
        'id' => $id,
        'slug' => $slug,
        'field' => $field,
        'status' => null,
        'replacements' => [],
    ];

    if (! $row) {
        $entry['status'] = 'missing_record';
        $report['summary']['missing_record']++;
        $report['rows'][] = $entry;
        continue;
    }
    if (($row->slug ?? null) !== $slug) {
        $entry['status'] = 'slug_mismatch';
        $entry['live_slug'] = $row->slug ?? null;
        $report['summary']['diverged']++;
        $report['rows'][] = $entry;
        continue;
    }

    $before = (string) ($row->{$field} ?? '');
    $after = $before;
    $totalChanges = 0;
    $divergent = false;

    foreach ($op['replacements'] as $rep) {
        $from = $rep['from'];
        $to = $rep['to'];
        $fromCount = countHref($after, $from);
        $toCount = $to ? countHref($after, $to) : null;

        if ($fromCount === 0) {
            // already applied? for rewrite: to present; for unwrap: from absent (cannot prove)
            if ($to !== null && countHref($after, $to) > 0) {
                $entry['replacements'][] = [
                    'from' => $from,
                    'to' => $to,
                    'result' => 'already_done',
                    'from_count' => 0,
                    'to_count' => countHref($after, $to),
                ];
                continue;
            }
            if ($to === null) {
                // unwrap already done or never present — treat as already_done (idempotent)
                $entry['replacements'][] = [
                    'from' => $from,
                    'to' => null,
                    'result' => 'already_done_or_absent',
                    'from_count' => 0,
                ];
                continue;
            }
            // expected old href missing and new not present → diverge
            $divergent = true;
            $entry['replacements'][] = [
                'from' => $from,
                'to' => $to,
                'result' => 'diverged_expected_from_missing',
                'from_count' => 0,
                'to_count' => 0,
            ];
            continue;
        }

        [$after, $n] = rewriteOrUnwrap($after, $from, $to);
        $totalChanges += $n;
        $entry['replacements'][] = [
            'from' => $from,
            'to' => $to,
            'result' => $n > 0 ? 'would_apply' : 'no_anchor_match_but_path_seen',
            'replacements' => $n,
            'from_count_before' => $fromCount,
        ];
    }

    if ($divergent) {
        $entry['status'] = 'diverged_skip_write';
        $report['summary']['diverged']++;
        $report['rows'][] = $entry;
        continue;
    }

    if ($totalChanges === 0) {
        $entry['status'] = 'already_done';
        $report['summary']['already_done']++;
        $report['rows'][] = $entry;
        continue;
    }

    $entry['status'] = $mode === 'apply' ? 'applied' : 'dry_run_would_apply';
    $entry['bytes_before'] = strlen($before);
    $entry['bytes_after'] = strlen($after);
    $report['summary']['apply_ready']++;
    $report['summary']['would_change_fields']++;

    if ($mode === 'apply') {
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0775, true);
        }
        $backupFile = $backupPath.'/'.$table.'-'.$id.'-'.$field.'.json';
        file_put_contents($backupFile, json_encode([
            'table' => $table,
            'id' => $id,
            'slug' => $slug,
            'field' => $field,
            'before' => $before,
            'backed_up_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $backupIndex[] = basename($backupFile);

        DB::table($table)->where('id', $id)->where('slug', $slug)->update([
            $field => $after,
            'updated_at' => now(),
        ]);
    }

    $report['rows'][] = $entry;
}

if ($mode === 'apply' && $backupIndex !== []) {
    file_put_contents($backupPath.'/index.json', json_encode([
        'package' => 'asama-a-v1',
        'backed_up_at' => date('c'),
        'files' => $backupIndex,
        'rollback_command' => 'php scripts/asama-a-content-transfer.php --rollback='.$backupPath,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $report['backup_dir'] = $backupPath;
}

$outFile = $reportDir.'/transfer-report-'.$mode.'-'.date('Ymd-His').'.json';
file_put_contents($outFile, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode([
    'mode' => $mode,
    'report' => $outFile,
    'summary' => $report['summary'],
    'backup_dir' => $report['backup_dir'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

// Non-zero if diverged or missing when applying
if ($mode === 'apply' && ($report['summary']['diverged'] > 0 || $report['summary']['missing_record'] > 0)) {
    exit(2);
}
exit(0);
