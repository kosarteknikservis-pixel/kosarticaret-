<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FooterPaymentCards
{
    /** @return array<string, array{label: string, brand: string, image: ?string}> */
    public static function catalog(): array
    {
        $builtin = collect(config('kosar.footer.cards', []))
            ->map(function (array $card) {
                $card['image'] = null;

                return $card;
            })
            ->all();

        return array_merge($builtin, self::extraCatalog());
    }

    /** @return list<array{key: string, label: string, brand: string, image: ?string, custom: bool}> */
    public static function enabled(): array
    {
        $keys = FooterTrust::enabledCardKeys();
        $catalog = self::catalog();
        $out = [];

        foreach ($keys as $key) {
            if (! isset($catalog[$key])) {
                continue;
            }
            $out[] = array_merge(
                ['key' => $key, 'custom' => str_starts_with($key, 'custom_')],
                $catalog[$key]
            );
        }

        return $out;
    }

    /** @return list<array{key: string, label: string, image: string}> */
    public static function extraStored(): array
    {
        $raw = SiteSetting::get('footer_extra_cards', '[]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function addExtra(string $label, UploadedFile $file): string
    {
        $key = 'custom_'.Str::lower(Str::random(8));
        $path = $file->store('payments/extra', 'public');
        $items = self::extraStored();
        $items[] = [
            'key' => $key,
            'label' => trim($label) ?: 'Kart',
            'image' => $path,
        ];
        SiteSetting::set('footer_extra_cards', json_encode($items, JSON_UNESCAPED_UNICODE));

        $enabled = FooterTrust::enabledCardKeys();
        if (! in_array($key, $enabled, true)) {
            $enabled[] = $key;
            SiteSetting::set('footer_trust_cards', implode(',', $enabled));
        }

        return $key;
    }

    public static function removeExtra(string $key): void
    {
        $stored = self::extraStored();
        foreach ($stored as $item) {
            if (($item['key'] ?? '') === $key && ! empty($item['image'])) {
                Storage::disk('public')->delete($item['image']);
            }
        }

        $items = array_values(array_filter(
            $stored,
            fn (array $item) => ($item['key'] ?? '') !== $key
        ));

        SiteSetting::set('footer_extra_cards', json_encode($items, JSON_UNESCAPED_UNICODE));

        $enabled = array_values(array_filter(
            FooterTrust::enabledCardKeys(),
            fn (string $k) => $k !== $key
        ));
        SiteSetting::set('footer_trust_cards', implode(',', $enabled));
    }

    /** @return array<string, array{label: string, brand: string, image: ?string}> */
    private static function extraCatalog(): array
    {
        $out = [];
        foreach (self::extraStored() as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $path = (string) ($item['image'] ?? '');
            $out[$key] = [
                'label' => (string) ($item['label'] ?? 'Kart'),
                'brand' => 'custom',
                'image' => $path !== '' ? Storage::disk('public')->url($path) : null,
            ];
        }

        return $out;
    }

}
