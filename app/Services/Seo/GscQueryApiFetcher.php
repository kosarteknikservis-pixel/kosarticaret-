<?php

namespace App\Services\Seo;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GscQueryApiFetcher
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(int $days): array
    {
        $credentials = $this->credentialsPath();
        $json = $this->runPythonFetcher($credentials, $days);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('GSC query fetcher gecersiz JSON dondurdu.');
        }

        return $data;
    }

    private function credentialsPath(): string
    {
        $path = (string) config('google_seo.credentials_path');

        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException(
                'Google SEO credentials bulunamadi. .env icinde GOOGLE_SEO_CREDENTIALS yolunu ayarlayin.'
            );
        }

        return $path;
    }

    private function runPythonFetcher(string $credentials, int $days): string
    {
        $script = base_path('scripts/seo/fetch_gsc_queries.py');
        if (! is_readable($script)) {
            throw new RuntimeException('GSC query fetch script bulunamadi: '.$script);
        }

        $uvx = $this->resolveBinary([
            env('UVX_BINARY'),
            'C:\\Users\\PC\\.local\\bin\\uvx.exe',
            'uvx',
        ]);

        $python = $this->resolveBinary([
            env('PYTHON_BINARY'),
            'C:\\Users\\PC\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
        ], required: false);

        $command = [$uvx];
        if ($python !== null) {
            $command[] = '--python';
            $command[] = $python;
        }

        array_push(
            $command,
            '--with', 'google-auth',
            '--with', 'google-api-python-client',
            'python',
            $script,
            '--days',
            (string) $days,
        );

        $process = new Process($command, base_path(), [
            'GOOGLE_APPLICATION_CREDENTIALS' => $credentials,
            'GSC_SITE_URL' => (string) config('google_seo.gsc_site_url'),
            'PYTHONIOENCODING' => 'utf-8',
        ], null, 120);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    /**
     * @param  list<string|null>  $candidates
     */
    private function resolveBinary(array $candidates, bool $required = true): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }

            $which = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows'
                ? 'where '.escapeshellarg($candidate).' 2>nul'
                : 'command -v '.escapeshellarg($candidate).' 2>/dev/null'));

            if ($which !== '' && is_file(explode("\n", $which)[0])) {
                return explode("\n", $which)[0];
            }
        }

        if ($required) {
            throw new RuntimeException('Gerekli binary bulunamadi: '.implode(', ', array_filter($candidates)));
        }

        return null;
    }
}
