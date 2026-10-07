<?php

namespace App\Support;

/**
 * Koleksiyon eşleşmesi için özellik okuyucusu.
 * Ürünün specs alanına ve vitrindeki teknik tabloya yazmaz.
 */
class CollectionSpecReader
{
    /** @var list<array{0: float, 1: float}> */
    public const KW_TO_HP = [
        [0.37, 0.5],
        [0.55, 0.75],
        [0.75, 1.0],
        [1.1, 1.5],
        [1.5, 2.0],
        [2.2, 3.0],
        [3.0, 4.0],
        [4.0, 5.5],
        [5.5, 7.5],
    ];

    /** @var list<string> */
    private const MOTOR_KEYS = [
        'motor gucu',
        'guc',
        'pompa gucu',
        'guc (kw)',
        'kw',
        'motor gucu (kw)',
        'guc (hp)',
        'hp',
        'motor gucu (hp)',
        'pompa gucu (hp)',
        'beygir',
        'motor beygir',
    ];

    /**
     * @param  array<int|string, mixed>|null  $specs
     * @return array{status: string, hp: ?float, evidence: ?string, reason: ?string}
     */
    public function motorHp(?array $specs): array
    {
        $motor = [];
        $uncertain = [];

        foreach ($this->pairs($specs) as [$key, $value]) {
            $role = $this->powerRole($key);
            if ($role === 'ignore') {
                continue;
            }
            if ($role === 'uncertain' || $this->valueLooksLikeInputPower($value)) {
                $uncertain[] = $key.'='.$value;

                continue;
            }
            $motor[] = [$key, $value];
        }

        if ($motor === []) {
            return [
                'status' => $uncertain === [] ? 'empty' : 'uncertain',
                'hp' => null,
                'evidence' => null,
                'reason' => $uncertain === [] ? null : 'Motor gücü belli değil: '.implode('; ', $uncertain),
            ];
        }

        $hpValues = [];
        $kwValues = [];
        $evidence = [];

        foreach ($motor as [$key, $value]) {
            $foldedKey = $this->fold($key);
            $fromHp = $this->numbers($value, 'hp');
            $fromKw = $this->numbers($value, 'kw');
            if ($fromHp === [] && $fromKw === [] && preg_match('/^\s*\d+(?:[.,]\d+)?\s*$/', $value) === 1) {
                $bare = (float) str_replace(',', '.', trim($value));
                if (in_array($foldedKey, ['hp', 'guc (hp)', 'motor gucu (hp)', 'pompa gucu (hp)', 'beygir', 'motor beygir'], true)) {
                    $fromHp = [$bare];
                } elseif (in_array($foldedKey, ['kw', 'guc (kw)', 'motor gucu (kw)'], true)) {
                    $fromKw = [$bare];
                } else {
                    $uncertain[] = $key.'='.$value.' (birim yok)';

                    continue;
                }
            }
            foreach ($fromHp as $hp) {
                $hpValues[] = $hp;
            }
            if ($fromHp === [] && preg_match('/\d\s*[x×]\s*\d/u', $this->fold($value)) === 1) {
                $uncertain[] = $key.'='.$value.' (birden fazla motor)';

                continue;
            }
            if ($fromHp === []) {
                foreach ($fromKw as $kw) {
                    $kwValues[] = $kw;
                }
            }
            $evidence[] = $key.'='.$value;
        }

        $hpValues = $this->uniqueNumbers($hpValues);
        if (count($hpValues) > 1) {
            return [
                'status' => 'uncertain',
                'hp' => null,
                'evidence' => null,
                'reason' => 'Birden fazla HP: '.implode(', ', $evidence),
            ];
        }
        if (count($hpValues) === 1) {
            return [
                'status' => 'clear',
                'hp' => $hpValues[0],
                'evidence' => implode('; ', $evidence),
                'reason' => null,
            ];
        }

        $mapped = [];
        foreach ($this->uniqueNumbers($kwValues) as $kw) {
            $hp = $this->mapKw($kw);
            if ($hp === null) {
                return [
                    'status' => 'uncertain',
                    'hp' => null,
                    'evidence' => null,
                    'reason' => 'kW tablo dışı: '.implode('; ', $evidence),
                ];
            }
            $mapped[] = $hp;
        }
        $mapped = $this->uniqueNumbers($mapped);
        if (count($mapped) === 1) {
            return [
                'status' => 'clear',
                'hp' => $mapped[0],
                'evidence' => implode('; ', $evidence).' → '.$this->formatHp($mapped[0]).' HP',
                'reason' => null,
            ];
        }
        if ($mapped !== []) {
            return [
                'status' => 'uncertain',
                'hp' => null,
                'evidence' => null,
                'reason' => 'kW değerleri farklı HP veriyor: '.implode('; ', $evidence),
            ];
        }

        return [
            'status' => 'uncertain',
            'hp' => null,
            'evidence' => null,
            'reason' => 'Güç okunamadı: '.implode('; ', array_merge($evidence, $uncertain)),
        ];
    }

    /**
     * @param  array<int|string, mixed>|null  $specs
     * @return array{status: string, evidence: ?string}
     */
    public function phase(?array $specs): array
    {
        $mono = false;
        $tri = false;
        $evidence = [];

        foreach ($this->pairs($specs) as [$key, $value]) {
            if (! $this->isPhaseKey($key)) {
                continue;
            }
            $kind = $this->classifyPhaseValue($value);
            if ($kind === 'unknown') {
                continue;
            }
            $evidence[] = $key.'='.$value;
            if ($kind === 'mono') {
                $mono = true;
            }
            if ($kind === 'tri' || $kind === 'both') {
                $tri = true;
            }
        }

        if ($tri) {
            return ['status' => 'tri', 'evidence' => implode('; ', $evidence)];
        }
        if ($mono) {
            return ['status' => 'mono', 'evidence' => implode('; ', $evidence)];
        }

        return ['status' => 'unknown', 'evidence' => null];
    }

    public function mapKw(float $kw): ?float
    {
        foreach (self::KW_TO_HP as [$from, $hp]) {
            if (abs($kw - $from) <= 0.051) {
                return $hp;
            }
        }

        return null;
    }

    public function fold(string $value): string
    {
        $value = str_replace(['İ', 'I'], ['i', 'ı'], $value);
        $value = mb_strtolower(trim($value), 'UTF-8');

        return strtr($value, [
            'ı' => 'i',
            'ğ' => 'g',
            'ü' => 'u',
            'ş' => 's',
            'ö' => 'o',
            'ç' => 'c',
        ]);
    }

    /**
     * @param  array<int|string, mixed>|null  $specs
     * @return list<array{0: string, 1: string}>
     */
    private function pairs(?array $specs): array
    {
        $pairs = [];
        foreach ($specs ?? [] as $key => $value) {
            if (is_array($value)) {
                $label = $value['label'] ?? (is_string($key) ? $key : '');
                $text = $value['value'] ?? '';
            } else {
                $label = is_string($key) ? $key : '';
                $text = $value;
            }
            if (! is_scalar($label) || ! is_scalar($text)) {
                continue;
            }
            $label = trim((string) $label);
            $text = trim((string) $text);
            if ($label !== '' && $text !== '') {
                $pairs[] = [$label, $text];
            }
        }

        return $pairs;
    }

    private function powerRole(string $key): string
    {
        $folded = $this->fold($key);
        if ($this->containsAny($folded, ['kablo', 'tuketim', 'secenek', 'cekilen', 'p1', 'giris gucu', 'elektrik gucu', 'sebeke'])) {
            return 'uncertain';
        }
        if ($this->containsAny($folded, ['mil gucu', 'nominal guc', 'p2', 'cikis gucu']) || in_array($folded, self::MOTOR_KEYS, true)) {
            return 'motor';
        }
        if ($this->containsAny($folded, ['guc', 'kw', 'hp', 'watt', 'beygir'])) {
            return 'uncertain';
        }

        return 'ignore';
    }

    private function valueLooksLikeInputPower(string $value): bool
    {
        $folded = $this->fold($value);
        $input = str_contains($folded, 'cekilen') || preg_match('/\bp1\b/', $folded) === 1;
        $output = str_contains($folded, 'p2') || str_contains($folded, 'mil');

        return $input && ! $output;
    }

    private function isPhaseKey(string $key): bool
    {
        $folded = $this->fold($key);
        if (str_contains($folded, 'guc')) {
            return false;
        }

        return $this->containsAny($folded, ['voltaj', 'elektrik', 'gerilim', 'besleme', 'faz', 'voltage']);
    }

    private function classifyPhaseValue(string $value): string
    {
        $folded = $this->fold($value);
        $monoWord = str_contains($folded, 'monofaze') || str_contains($folded, 'mono faz');
        $triWord = str_contains($folded, 'trifaze') || str_contains($folded, 'tri faz');
        if ($monoWord && $triWord) {
            return 'both';
        }
        if ($triWord) {
            return 'tri';
        }
        if ($monoWord) {
            return 'mono';
        }

        preg_match_all('/\d+(?:[.,]\d+)?/', $folded, $matches);
        $volts = [];
        foreach ($matches[0] as $raw) {
            $number = (float) str_replace(',', '.', $raw);
            if ($number >= 100 && $number <= 500) {
                $volts[] = $number;
            }
        }
        if ($volts === []) {
            return 'unknown';
        }

        $triVolt = false;
        $monoVolt = false;
        $other = false;
        foreach ($volts as $volt) {
            if (abs($volt - 380) < 1 || abs($volt - 400) < 1) {
                $triVolt = true;
            } elseif (abs($volt - 220) < 1 || abs($volt - 230) < 1) {
                $monoVolt = true;
            } else {
                $other = true;
            }
        }
        if ($triVolt) {
            return 'tri';
        }
        if ($monoVolt && ! $other) {
            return 'mono';
        }

        return 'unknown';
    }

    /** @return list<float> */
    private function numbers(string $value, string $unit): array
    {
        $pattern = $unit === 'hp'
            ? '/(\d+(?:[.,]\d+)?)\s*hp\b/i'
            : '/(\d+(?:[.,]\d+)?)\s*kw\b/i';
        preg_match_all($pattern, $this->fold($value), $matches);
        $numbers = [];
        foreach ($matches[1] as $raw) {
            $numbers[] = (float) str_replace(',', '.', $raw);
        }

        return $numbers;
    }

    /** @param  list<float>  $numbers
     * @return list<float>
     */
    private function uniqueNumbers(array $numbers): array
    {
        $unique = [];
        foreach ($numbers as $number) {
            $seen = false;
            foreach ($unique as $existing) {
                if (abs($existing - $number) <= 0.051) {
                    $seen = true;
                    break;
                }
            }
            if (! $seen) {
                $unique[] = $number;
            }
        }

        return $unique;
    }

    /** @param  list<string>  $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function formatHp(float $hp): string
    {
        $text = number_format($hp, 2, '.', '');

        return rtrim(rtrim($text, '0'), '.');
    }
}
