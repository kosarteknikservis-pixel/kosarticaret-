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

        if (preg_match('/([\d.]+)\s*(?:m|metre)(?!\s*[³3])/u', $value, $m)) {
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
