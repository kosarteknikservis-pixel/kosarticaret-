<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class SiteBackupManager
{
    private const MANIFEST = 'manifest.json';

    private const DATABASE_ENTRY = 'database/database.sqlite';

    private const DATABASE_SQL_ENTRY = 'database/database.sql';

    private const STORAGE_PREFIX = 'storage/public/';

    public function backupDirectory(): string
    {
        return storage_path('app/private/site-backups');
    }

    /** @return list<array{name: string, path: string, size: int, created_at: string, manifest: array<string, mixed>|null}> */
    public function backups(): array
    {
        $this->ensureBackupDirectory();

        return collect(File::files($this->backupDirectory()))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip')
            ->map(function ($file) {
                return [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'manifest' => $this->readManifest($file->getPathname()),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function create(?string $name = null, string $reason = 'manual'): string
    {
        $this->ensureBackupDirectory();

        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver", 'sqlite');
        $tmpSql = null;

        if ($driver === 'sqlite') {
            $databasePath = $this->databasePath();
            if (! is_file($databasePath)) {
                throw new RuntimeException('SQLite veritabanı dosyası bulunamadı.');
            }
            $dbEntry = self::DATABASE_ENTRY;
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $tmpSql = $this->exportMysqlDump();
            $databasePath = $tmpSql;
            $dbEntry = self::DATABASE_SQL_ENTRY;
        } else {
            throw new RuntimeException('Yedek yalnızca sqlite veya mysql/mariadb bağlantıları için desteklenir.');
        }

        $baseName = $this->backupFileName($name, $reason);
        $zipPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$baseName;
        $zip = new SimpleZipFile($zipPath);
        $zip->openForWriting();

        $storageFiles = $this->publicStorageFiles();
        $manifest = [
            'type' => 'kosar_site_backup',
            'version' => 2,
            'created_at' => now()->toDateTimeString(),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'database' => [
                'connection' => $connection,
                'driver' => $driver,
                'entry' => $dbEntry,
            ],
            'storage' => [
                'prefix' => self::STORAGE_PREFIX,
                'file_count' => count($storageFiles),
            ],
            'reason' => $reason,
        ];
        $zip->addString(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFile($databasePath, $dbEntry);
        $this->addPublicStorage($zip, $storageFiles);
        $zip->close();

        if ($tmpSql && is_file($tmpSql)) {
            @unlink($tmpSql);
        }

        return $zipPath;
    }

    public function import(UploadedFile $file): string
    {
        $this->ensureBackupDirectory();
        $this->assertValidBackup($file->getRealPath());

        $name = 'imported-'.now()->format('Ymd-His').'-'.$this->safeName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.zip';
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$name;
        File::copy($file->getRealPath(), $target);

        return $target;
    }

    public function restore(string $fileName): void
    {
        $zipPath = $this->pathFor($fileName);
        $this->assertValidBackup($zipPath);

        $this->create('Geri yukleme oncesi otomatik yedek', 'pre-restore');

        $tmp = storage_path('app/private/site-backup-restore-'.uniqid('', true));
        File::ensureDirectoryExists($tmp);

        SimpleZipFile::extractTo($zipPath, $tmp);

        $sqliteDump = $tmp.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, self::DATABASE_ENTRY);
        $sqlDump = $tmp.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, self::DATABASE_SQL_ENTRY);
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver", 'sqlite');

        DB::disconnect();

        if (is_file($sqlDump) && in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->importMysqlDump($sqlDump);
        } elseif (is_file($sqliteDump) && $driver === 'sqlite') {
            File::ensureDirectoryExists(dirname($this->databasePath()));
            File::copy($sqliteDump, $this->databasePath());
        } else {
            File::deleteDirectory($tmp);
            throw new RuntimeException('Yedek içindeki veritabanı, aktif bağlantı sürücüsüyle uyuşmuyor.');
        }

        $this->replacePublicStorage($tmp.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public');

        Cache::flush();
        File::deleteDirectory($tmp);
    }

    private function importMysqlDump(string $sqlPath): void
    {
        $connection = (string) config('database.default');
        $cfg = config("database.connections.{$connection}");
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        $result = Process::env([
            'MYSQL_PWD' => $password,
        ])->timeout(300)->input(File::get($sqlPath))->run([
            'mysql',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            $database,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('MySQL geri yükleme başarısız: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    public function delete(string $fileName): bool
    {
        $path = $this->pathFor($fileName);

        return File::exists($path) && File::delete($path);
    }

    public function pathFor(string $fileName): string
    {
        $base = basename($fileName);
        if ($base !== $fileName || ! str_ends_with(strtolower($base), '.zip')) {
            throw new RuntimeException('Geçersiz yedek dosyası.');
        }

        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$base;
        if (! is_file($path)) {
            throw new RuntimeException('Yedek dosyası bulunamadı.');
        }

        return $path;
    }

    /** @return array<string, mixed>|null */
    private function readManifest(string $zipPath): ?array
    {
        $raw = SimpleZipFile::get($zipPath, self::MANIFEST);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    private function assertValidBackup(string $zipPath): void
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('Yedek zip dosyası açılamadı.');
        }

        $hasManifest = false;
        $hasDatabase = false;

        foreach (SimpleZipFile::entries($zipPath) as $entry) {
            $this->assertSafeEntry($entry);
            $hasManifest = $hasManifest || $entry === self::MANIFEST;
            $hasDatabase = $hasDatabase
                || $entry === self::DATABASE_ENTRY
                || $entry === self::DATABASE_SQL_ENTRY;
        }

        $manifest = SimpleZipFile::get($zipPath, self::MANIFEST);

        $decoded = is_string($manifest) ? json_decode($manifest, true) : null;
        if (! $hasManifest || ! $hasDatabase || ! is_array($decoded) || ($decoded['type'] ?? null) !== 'kosar_site_backup') {
            throw new RuntimeException('Bu dosya geçerli bir KOŞAR site yedeği değil.');
        }
    }

    private function exportMysqlDump(): string
    {
        $connection = (string) config('database.default');
        $cfg = config("database.connections.{$connection}");
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        if ($database === '') {
            throw new RuntimeException('MySQL veritabanı adı boş.');
        }

        $tmp = storage_path('app/private/site-backup-mysql-'.uniqid('', true).'.sql');
        File::ensureDirectoryExists(dirname($tmp));

        $args = [
            'mysqldump',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            $database,
        ];

        $result = Process::env([
            'MYSQL_PWD' => $password,
        ])->timeout(300)->run($args);

        if (! $result->successful()) {
            @unlink($tmp);
            throw new RuntimeException('mysqldump çalışmadı: '.trim($result->errorOutput() ?: $result->output()));
        }

        File::put($tmp, $result->output());

        return $tmp;
    }

    private function assertSafeEntry(string $entry): void
    {
        if ($entry === '' || str_starts_with($entry, '/') || str_contains($entry, '\\') || str_contains($entry, '..')) {
            throw new RuntimeException('Yedek içinde güvenli olmayan dosya yolu var.');
        }
    }

    /** @param list<array{path: string, relative: string}> $files */
    private function addPublicStorage(SimpleZipFile $zip, array $files): void
    {
        foreach ($files as $file) {
            $zip->addFile($file['path'], self::STORAGE_PREFIX.$file['relative']);
        }
    }

    /** @return list<array{path: string, relative: string}> */
    private function publicStorageFiles(): array
    {
        $root = storage_path('app/public');
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        foreach (File::allFiles($root) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if ($relative === '.gitignore') {
                continue;
            }

            $files[] = [
                'path' => $file->getPathname(),
                'relative' => $relative,
            ];
        }

        return $files;
    }

    private function replacePublicStorage(string $source): void
    {
        $target = storage_path('app/public');
        File::ensureDirectoryExists($target);

        foreach (File::directories($target) as $directory) {
            File::deleteDirectory($directory);
        }
        foreach (File::files($target) as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }

        if (! is_dir($source)) {
            return;
        }

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $destination = $target.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getPathname(), $destination);
        }
    }

    private function databasePath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        return $path !== '' ? $path : database_path('database.sqlite');
    }

    private function ensureBackupDirectory(): void
    {
        File::ensureDirectoryExists($this->backupDirectory(), 0755);
    }

    private function backupFileName(?string $name, string $reason): string
    {
        $prefix = $reason === 'pre-restore' ? 'restore-oncesi' : 'site-yedegi';
        $label = $this->safeName($name ?: 'kosar');

        return $prefix.'-'.now()->format('Ymd-His').'-'.$label.'.zip';
    }

    private function safeName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9-_]+/i', '-', $value) ?? 'yedek';
        $value = trim($value, '-_');

        return substr($value !== '' ? $value : 'yedek', 0, 60);
    }
}
