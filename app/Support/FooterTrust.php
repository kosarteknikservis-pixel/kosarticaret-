<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\StoreConfig;

final class FooterTrust
{
    /** @return list<array{key: string, label: string, brand: string, image: ?string, custom: bool}> */
    public static function cards(): array
    {
        return FooterPaymentCards::enabled();
    }

    /** @return list<string> */
    public static function enabledCardKeys(): array
    {
        return self::enabledKeys(
            'footer_trust_cards',
            'default_cards',
            self::legacyCardKeys()
        );
    }

    /** @return list<array{key: string, label: string, icon: ?string, hint: string, url: ?string, special: ?string}> */
    public static function compliance(): array
    {
        $enabled = self::enabledKeys('footer_trust_compliance', 'default_compliance');
        $items = self::mapKeys($enabled, config('kosar.footer.compliance', []));

        return array_map(function (array $item) {
            $item['url'] = match ($item['key']) {
                'etbis' => self::etbisUrl(),
                'kvkk' => self::kvkkUrl(),
                default => $item['url'] ?? null,
            };

            return $item;
        }, $items);
    }

    /** @return list<array{id: string, name: string, desc: string, icon: string}> */
    public static function paymentMethods(): array
    {
        $icons = config('kosar.footer.payment_icons', []);

        return array_map(function (array $method) use ($icons) {
            $method['icon'] = $icons[$method['id']] ?? 'credit-card';

            return $method;
        }, app(StoreConfig::class)->paymentMethods(PaymentMethodSettings::CONTEXT_FOOTER));
    }

    public static function etbisUrl(): ?string
    {
        $url = trim((string) SiteSetting::get('footer_etbis_url', ''));

        return $url !== '' ? $url : null;
    }

    public static function kvkkUrl(): ?string
    {
        $url = trim((string) SiteSetting::get('footer_kvkk_url', ''));

        if ($url !== '') {
            return $url;
        }

        $page = \App\Models\Page::query()
            ->where('published', true)
            ->where(function ($q) {
                $q->where('slug', 'gizlilik-politikasi')->orWhere('slug', 'kvkk');
            })
            ->first();

        return $page ? route('pages.show', $page) : null;
    }

    /** @return list<string> */
    private static function enabledKeys(string $settingKey, string $defaultKey, ?array $fallback = null): array
    {
        $raw = trim((string) SiteSetting::get($settingKey, ''));
        if ($raw !== '') {
            return self::parseList($raw);
        }

        if ($fallback !== null && $fallback !== []) {
            return $fallback;
        }

        return config("kosar.footer.{$defaultKey}", []);
    }

    /** @return list<string> */
    private static function parseList(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** @return list<string> */
    private static function legacyCardKeys(): array
    {
        $legacy = trim((string) SiteSetting::get('footer_payment_badges', ''));
        if ($legacy === '') {
            return [];
        }

        $map = [
            'visa' => 'visa',
            'mc' => 'mastercard',
            'mastercard' => 'mastercard',
            'master' => 'mastercard',
            'troy' => 'troy',
            'amex' => 'amex',
            'american express' => 'amex',
            'paypal' => 'paypal',
            'visa electron' => 'visa_electron',
            'maestro' => 'maestro',
        ];

        $keys = [];
        foreach (self::parseList($legacy) as $token) {
            $key = $map[strtolower($token)] ?? null;
            if ($key) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, array<string, mixed>>  $catalog
     * @return list<array{key: string}&array<string, mixed>>
     */
    private static function mapKeys(array $keys, array $catalog): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (! isset($catalog[$key])) {
                continue;
            }
            $out[] = array_merge(['key' => $key], $catalog[$key]);
        }

        return $out;
    }
}
