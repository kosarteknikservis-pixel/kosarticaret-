<?php

namespace App\Support;

use InvalidArgumentException;

class Code128Barcode
{
    private const START_B = 104;

    private const STOP = 106;

    /** @var list<string> */
    private const PATTERNS = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100', '10001001100',
        '10011001000', '10011000100', '10001100100', '11001001000', '11001000100', '11000100100',
        '10110011100', '10011011100', '10011001110', '10111001100', '10011101100', '10011100110',
        '11001110010', '11001011100', '11001001110', '11011100100', '11001110100', '11101101110',
        '11101001100', '11100101100', '11100100110', '11101100100', '11100110100', '11100110010',
        '11011011000', '11011000110', '11000110110', '10100011000', '10001011000', '10001000110',
        '10110001000', '10001101000', '10001100010', '11010001000', '11000101000', '11000100010',
        '10110111000', '10110001110', '10001101110', '10111011000', '10111000110', '10001110110',
        '11101110110', '11010001110', '11000101110', '11011101000', '11011100010', '11011101110',
        '11101011000', '11101000110', '11100010110', '11101101000', '11101100010', '11100011010',
        '11101111010', '11001000010', '11110001010', '10100110000', '10100001100', '10010110000',
        '10010000110', '10000101100', '10000100110', '10110010000', '10110000100', '10011010000',
        '10011000010', '10000110100', '10000110010', '11000010010', '11001010000', '11110111010',
        '11000010100', '10001111010', '10100111100', '10010111100', '10010011110', '10111100100',
        '10011110100', '10011110010', '11110100100', '11110010100', '11110010010', '11011011110',
        '11011110110', '11110110110', '10101111000', '10100011110', '10001011110', '10111101000',
        '10111100010', '11110101000', '11110100010', '10111011110', '10111101110', '11101011110',
        '11110101110', '11010000100', '11010010000', '11010011100', '1100011101011',
    ];

    public static function svg(string $text, float $heightMm = 8.0, float $moduleWidthMm = 0.28): string
    {
        $text = trim($text);
        if ($text === '') {
            throw new InvalidArgumentException('Barcode text cannot be empty.');
        }

        $binary = self::encode($text);
        $widthMm = strlen($binary) * $moduleWidthMm;
        $bars = '';
        $x = 0.0;

        for ($i = 0, $len = strlen($binary); $i < $len; $i++) {
            if ($binary[$i] === '1') {
                $bars .= sprintf(
                    '<rect x="%.4f" y="0" width="%.4f" height="%.4f" fill="#000"/>',
                    $x,
                    $moduleWidthMm,
                    $heightMm
                );
            }
            $x += $moduleWidthMm;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %.4f %.4f" width="100%%" height="100%%" preserveAspectRatio="none" role="img" aria-label="%s">%s</svg>',
            $widthMm,
            $heightMm,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            $bars
        );
    }

    private static function encode(string $text): string
    {
        $codes = [self::START_B];

        foreach (mb_str_split($text) as $char) {
            $ord = ord($char);
            if ($ord < 32 || $ord > 126) {
                throw new InvalidArgumentException("Unsupported barcode character: {$char}");
            }
            $codes[] = $ord - 32;
        }

        $checksum = self::START_B;
        foreach ($codes as $index => $code) {
            if ($index === 0) {
                continue;
            }
            $checksum += $code * $index;
        }
        $codes[] = $checksum % 103;
        $codes[] = self::STOP;

        $binary = '';
        foreach ($codes as $code) {
            if (! isset(self::PATTERNS[$code])) {
                throw new InvalidArgumentException("Invalid Code128 sequence at index {$code}.");
            }
            $binary .= self::PATTERNS[$code];
        }

        return $binary;
    }
}
