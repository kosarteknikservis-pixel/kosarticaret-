<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class SimpleZipFile
{
    /** @var list<array{name: string, crc: int, size: int, offset: int, time: int, date: int}> */
    private array $entries = [];

    /** @var resource|null */
    private $handle = null;

    public function __construct(private readonly string $path)
    {
    }

    public function openForWriting(): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        $this->handle = fopen($this->path, 'wb');
        if (! $this->handle) {
            throw new RuntimeException('Zip dosyası yazılamadı.');
        }
    }

    public function addFile(string $source, string $entry): void
    {
        if (! is_file($source)) {
            return;
        }

        $this->addEntry($entry, filesize($source) ?: 0, (int) hexdec(hash_file('crc32b', $source)), function ($target) use ($source) {
            $input = fopen($source, 'rb');
            if (! $input) {
                throw new RuntimeException('Yedeklenecek dosya okunamadı.');
            }

            stream_copy_to_stream($input, $target);
            fclose($input);
        });
    }

    public function addString(string $entry, string $contents): void
    {
        $this->addEntry($entry, strlen($contents), (int) hexdec(hash('crc32b', $contents)), function ($target) use ($contents) {
            fwrite($target, $contents);
        });
    }

    public function close(): void
    {
        if (! $this->handle) {
            return;
        }

        $centralOffset = ftell($this->handle);
        foreach ($this->entries as $entry) {
            fwrite($this->handle, pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $entry['time'],
                $entry['date'],
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                strlen($entry['name']),
                0,
                0,
                0,
                0,
                0,
                $entry['offset'],
            ));
            fwrite($this->handle, $entry['name']);
        }

        $centralSize = ftell($this->handle) - $centralOffset;
        $count = count($this->entries);
        fwrite($this->handle, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0));
        fclose($this->handle);
        $this->handle = null;
    }

    /** @return list<string> */
    public static function entries(string $path): array
    {
        $entries = [];
        self::readEntries($path, function (string $name) use (&$entries) {
            $entries[] = $name;
        }, false);

        return $entries;
    }

    public static function get(string $path, string $wantedEntry): ?string
    {
        $result = null;
        self::readEntries($path, function (string $name, string $contents) use ($wantedEntry, &$result) {
            if ($name === $wantedEntry) {
                $result = $contents;

                return false;
            }

            return true;
        }, true);

        return $result;
    }

    public static function extractTo(string $path, string $target): void
    {
        File::ensureDirectoryExists($target);
        self::readEntries($path, function (string $name, string $contents) use ($target) {
            if (str_ends_with($name, '/')) {
                return;
            }

            $destination = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
            File::ensureDirectoryExists(dirname($destination));
            file_put_contents($destination, $contents);
        }, true);
    }

    private function addEntry(string $entry, int $size, int $crc, callable $writer): void
    {
        if (! $this->handle) {
            throw new RuntimeException('Zip dosyası açık değil.');
        }

        [$time, $date] = $this->dosTime();
        $offset = ftell($this->handle);
        fwrite($this->handle, pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $time, $date, $crc, $size, $size, strlen($entry), 0));
        fwrite($this->handle, $entry);
        $writer($this->handle);

        $this->entries[] = [
            'name' => $entry,
            'crc' => $crc,
            'size' => $size,
            'offset' => $offset,
            'time' => $time,
            'date' => $date,
        ];
    }

    /** @return array{0: int, 1: int} */
    private function dosTime(): array
    {
        $now = getdate();
        $time = (($now['hours'] ?? 0) << 11) | (($now['minutes'] ?? 0) << 5) | (int) floor(($now['seconds'] ?? 0) / 2);
        $date = ((($now['year'] ?? 1980) - 1980) << 9) | (($now['mon'] ?? 1) << 5) | ($now['mday'] ?? 1);

        return [$time, $date];
    }

    private static function readEntries(string $path, callable $callback, bool $withContents): void
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('Zip dosyası okunamadı.');
        }

        while (! feof($handle)) {
            $header = fread($handle, 30);
            if ($header === '' || strlen($header) < 4) {
                break;
            }

            $signature = unpack('V', substr($header, 0, 4))[1] ?? 0;
            if ($signature === 0x02014b50 || $signature === 0x06054b50) {
                break;
            }
            if ($signature !== 0x04034b50 || strlen($header) !== 30) {
                fclose($handle);
                throw new RuntimeException('Desteklenmeyen zip yapısı.');
            }

            $data = unpack('Vsig/vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnamelen/vextralen', $header);
            if (($data['method'] ?? null) !== 0 || ($data['flags'] ?? 0) !== 0) {
                fclose($handle);
                throw new RuntimeException('Sadece panelin oluşturduğu standart yedek zipleri geri yüklenebilir.');
            }

            $name = self::readBytes($handle, (int) $data['namelen']);
            if ((int) $data['extralen'] > 0) {
                self::readBytes($handle, (int) $data['extralen']);
            }

            if ($withContents) {
                $contents = self::readBytes($handle, (int) $data['csize']);
                $keepReading = $callback($name, $contents);
                if ($keepReading === false) {
                    break;
                }
            } else {
                if ((int) $data['csize'] > 0) {
                    fseek($handle, (int) $data['csize'], SEEK_CUR);
                }
                $keepReading = $callback($name, '');
                if ($keepReading === false) {
                    break;
                }
            }
        }

        fclose($handle);
    }

    /** @param resource $handle */
    private static function readBytes($handle, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($handle)) {
            $chunk = fread($handle, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buffer .= $chunk;
        }

        if (strlen($buffer) !== $length) {
            throw new RuntimeException('Zip dosyası eksik veya bozuk.');
        }

        return $buffer;
    }
}
