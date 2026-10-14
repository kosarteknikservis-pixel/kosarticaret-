<?php

namespace App\Services\PumpSelection;

use App\Models\Product;

class PumpSpecExtractor
{
    /**
     * @return array{flow_m3h: ?float, head_m: ?float, power_kw: ?float}
     */
    public function extract(Product $product): array
    {
        $flow = null;
        $head = null;
        $power = null;

        foreach ($this->specEntries($product) as $label => $value) {
            $labelLower = mb_strtolower($label, 'UTF-8');

            if ($flow === null && $this->isFlowLabel($labelLower)) {
                $flow = $this->parseFlow($value);
            }
            if ($head === null && $this->isHeadLabel($labelLower)) {
                $head = $this->parseHead($value);
            }
            if ($power === null && $this->isPowerLabel($labelLower)) {
                $power = $this->parsePower($value);
            }
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $product->name,
            strip_tags((string) $product->short_description),
            strip_tags((string) $product->description),
        ])), 'UTF-8');

        if ($flow === null) {
            $flow = $this->parseFlowFromText($haystack);
        }
        if ($head === null) {
            $head = $this->parseHeadFromText($haystack);
        }
        if ($power === null) {
            $power = $this->parsePowerFromText($haystack);
        }

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'power_kw' => $power,
        ];
    }

    /**
     * Debi–basma eğrisi. Anahtar «1 TON», «2.5 TON» biçimindeyse debi m³/saat, değer basma metre kabul edilir.
     *
     * @return list<array{flow: float, head: float}>
     */
    public function curvePoints(Product $product): array
    {
        $points = [];

        foreach ($this->specEntries($product) as $label => $value) {
            $labelNorm = str_replace(',', '.', mb_strtolower(trim($label), 'UTF-8'));
            if (! preg_match('/^([\d.]+)\s*ton\b/u', $labelNorm, $m)) {
                continue;
            }

            $head = $this->parseHead($value);
            if ($head === null) {
                continue;
            }

            $points[] = ['flow' => (float) $m[1], 'head' => $head];
        }

        usort($points, fn (array $a, array $b) => $a['flow'] <=> $b['flow']);

        return $points;
    }

    /**
     * İstenen debideki basma. Debi eğrinin son noktasını aşıyorsa pompa o debide su basamaz.
     *
     * @param  list<array{flow: float, head: float}>  $points
     */
    public function headAtFlow(array $points, float $flow): ?float
    {
        if (count($points) < 2) {
            return null;
        }

        if ($flow <= $points[0]['flow']) {
            return $points[0]['head'];
        }

        $last = $points[array_key_last($points)];
        if ($flow > $last['flow']) {
            return null;
        }

        for ($i = 1, $count = count($points); $i < $count; $i++) {
            $left = $points[$i - 1];
            $right = $points[$i];
            if ($flow > $right['flow']) {
                continue;
            }

            $span = $right['flow'] - $left['flow'];
            if ($span <= 0.0) {
                return $right['head'];
            }

            $ratio = ($flow - $left['flow']) / $span;

            return round($left['head'] + (($right['head'] - $left['head']) * $ratio), 1);
        }

        return null;
    }

    /**
     * @return 'monofaze'|'trifaze'|'her_ikisi'|null
     */
    public function supplyPhase(Product $product): ?string
    {
        $text = mb_strtolower($product->name.' '.implode(' ', $this->specEntries($product)), 'UTF-8');
        $threePhase = preg_match('/trifaze|üç\s*faz|uc\s*faz|\b380\s*v/u', $text) === 1;
        $singlePhase = preg_match('/monofaze|tek\s*faz|\b220\s*v|\b230\s*v/u', $text) === 1;

        if ($threePhase && $singlePhase) {
            return 'her_ikisi';
        }
        if ($threePhase) {
            return 'trifaze';
        }
        if ($singlePhase) {
            return 'monofaze';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function specEntries(Product $product): array
    {
        $entries = [];
        $specs = $product->specs ?? [];

        foreach ($specs as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $entries[$key] = (string) $value;
            } elseif (is_array($value)) {
                $label = (string) ($value['label'] ?? $value['name'] ?? '');
                $val = (string) ($value['value'] ?? '');
                if ($label !== '' && $val !== '') {
                    $entries[$label] = $val;
                }
            }
        }

        return $entries;
    }

    private function isFlowLabel(string $label): bool
    {
        return (bool) preg_match('/debi|akış|akis|flow|kapasite|verim|lt\/|m³|m3|m\^3/u', $label);
    }

    private function isHeadLabel(string $label): bool
    {
        return (bool) preg_match('/basma|manometrik|yükseklik|yukseklik|head|maks\.\s*bas|basınç/u', $label);
    }

    private function isPowerLabel(string $label): bool
    {
        return (bool) preg_match('/güç|guc|power|motor|hp|kw|kilowatt/u', $label);
    }

    public function parseFlow(string $value): ?float
    {
        $value = str_replace(',', '.', mb_strtolower(trim($value), 'UTF-8'));

        if (preg_match('/([\d.]+)\s*m[³3]\s*\/?\s*(?:h|saat)?/u', $value, $m)) {
            return round((float) $m[1], 2);
        }

        if (preg_match('/([\d.]+)\s*(?:lt|l)\s*\/?\s*(?:dk|min)/u', $value, $m)) {
            return round(((float) $m[1] * 60) / 1000, 2);
        }

        if (preg_match('/([\d.]+)\s*(?:lt|l)\s*\/?\s*h/u', $value, $m)) {
            return round((float) $m[1] / 1000, 2);
        }

        return null;
    }

    public function parseHead(string $value): ?float
    {
        $value = str_replace(',', '.', mb_strtolower(trim($value), 'UTF-8'));

        if (preg_match('/([\d.]+)\s*(?:metre|mss|m)(?![a-zçğıöşü³3])/u', $value, $m)) {
            return round((float) $m[1], 1);
        }

        if (preg_match('/([\d.]+)\s*bar/u', $value, $m)) {
            return round((float) $m[1] * 10.2, 1);
        }

        return null;
    }

    public function parsePower(string $value): ?float
    {
        $value = str_replace(',', '.', mb_strtolower(trim($value), 'UTF-8'));

        if (preg_match('/([\d.]+)\s*hp/u', $value, $m)) {
            return round((float) $m[1] * 0.746, 2);
        }

        if (preg_match('/([\d.]+)\s*kw/u', $value, $m)) {
            return round((float) $m[1], 2);
        }

        if (preg_match('/([\d.]+)\s*w(?!\w)/u', $value, $m)) {
            return round((float) $m[1] / 1000, 2);
        }

        return null;
    }

    private function parseFlowFromText(string $text): ?float
    {
        if (preg_match('/([\d.]+)\s*m[³3]\s*\/?\s*h/u', $text, $m)) {
            return round((float) $m[1], 2);
        }

        if (preg_match('/([\d.]+)\s*(?:lt|l)\s*\/?\s*dk/u', $text, $m)) {
            return round(((float) $m[1] * 60) / 1000, 2);
        }

        return null;
    }

    private function parseHeadFromText(string $text): ?float
    {
        if (preg_match('/(?:basma|max\.?\s*basma|manometrik)[^\d]{0,12}([\d.]+)\s*m/u', $text, $m)) {
            return round((float) $m[1], 1);
        }

        if (preg_match('/([\d.]+)\s*m\s*(?:basma|manometrik)/u', $text, $m)) {
            return round((float) $m[1], 1);
        }

        return null;
    }

    private function parsePowerFromText(string $text): ?float
    {
        if (preg_match('/([\d.]+)\s*hp/u', $text, $m)) {
            return round((float) $m[1] * 0.746, 2);
        }

        if (preg_match('/([\d.]+)\s*kw/u', $text, $m)) {
            return round((float) $m[1], 2);
        }

        return null;
    }
}
