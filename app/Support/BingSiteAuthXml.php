<?php

namespace App\Support;

final class BingSiteAuthXml
{
    public static function normalize(?string $input): string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return '';
        }

        $code = self::extractUserCode($input);
        if ($code === null) {
            return self::ensureXmlDeclaration($input);
        }

        return self::build($code);
    }

    public static function build(string $code): string
    {
        $code = strtoupper(trim($code));

        return implode("\n", [
            '<?xml version="1.0"?>',
            '<users>',
            "\t<user>{$code}</user>",
            '</users>',
        ]);
    }

    private static function extractUserCode(string $input): ?string
    {
        if (preg_match('/<user>\s*([A-Z0-9]+)\s*<\/user>/i', $input, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[A-Z0-9]{32}$/i', $input) === 1) {
            return $input;
        }

        return null;
    }

    private static function ensureXmlDeclaration(string $input): string
    {
        if (str_starts_with($input, '<?xml')) {
            return $input;
        }

        return "<?xml version=\"1.0\"?>\n".$input;
    }
}
