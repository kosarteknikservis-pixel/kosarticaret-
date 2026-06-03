<?php

namespace App\Support;

use App\Models\SiteSetting;

class PaymentGatewayConfig
{
    public const PROVIDERS = ['mock', 'paytr', 'iyzico'];

    public static function activeProvider(): string
    {
        $fromSetting = trim((string) SiteSetting::get('payment_gateway', ''));
        if (in_array($fromSetting, self::PROVIDERS, true)) {
            return $fromSetting;
        }

        $fromEnv = trim((string) config('payment.provider', 'mock'));

        return in_array($fromEnv, self::PROVIDERS, true) ? $fromEnv : 'mock';
    }

    public static function isLive(): bool
    {
        return self::activeProvider() !== 'mock' && self::isConfigured(self::activeProvider());
    }

    public static function isConfigured(?string $provider = null): bool
    {
        return match ($provider ?? self::activeProvider()) {
            'paytr' => self::paytrMerchantId() !== ''
                && self::paytrMerchantKey() !== ''
                && self::paytrMerchantSalt() !== '',
            'iyzico' => self::iyzicoApiKey() !== '' && self::iyzicoSecretKey() !== '',
            default => true,
        };
    }

    public static function label(?string $provider = null): string
    {
        return match ($provider ?? self::activeProvider()) {
            'paytr' => 'PayTR',
            'iyzico' => 'iyzico',
            default => 'Demo ödeme',
        };
    }

    public static function paytrMerchantId(): string
    {
        return self::credential('paytr_merchant_id', 'payment.paytr.merchant_id');
    }

    public static function paytrMerchantKey(): string
    {
        return self::credential('paytr_merchant_key', 'payment.paytr.merchant_key');
    }

    public static function paytrMerchantSalt(): string
    {
        return self::credential('paytr_merchant_salt', 'payment.paytr.merchant_salt');
    }

    public static function paytrTestMode(): bool
    {
        $val = SiteSetting::get('paytr_test_mode');
        if ($val !== null && $val !== '') {
            return $val === '1';
        }

        return (bool) config('payment.paytr.test_mode', true);
    }

    public static function paytrInstallmentTableToken(): string
    {
        return trim((string) SiteSetting::get('paytr_installment_table_token', ''));
    }

    public static function iyzicoApiKey(): string
    {
        return self::credential('iyzico_api_key', 'payment.iyzico.api_key');
    }

    public static function iyzicoSecretKey(): string
    {
        return self::credential('iyzico_secret_key', 'payment.iyzico.secret_key');
    }

    public static function iyzicoBaseUrl(): string
    {
        $custom = trim((string) SiteSetting::get('iyzico_base_url', ''));
        if ($custom !== '') {
            return rtrim($custom, '/');
        }

        if (SiteSetting::get('iyzico_sandbox') === '0') {
            return 'https://api.iyzipay.com';
        }

        $env = trim((string) config('payment.iyzico.base_url', ''));

        return $env !== '' ? rtrim($env, '/') : 'https://sandbox-api.iyzipay.com';
    }

    public static function iyzicoSandbox(): bool
    {
        $val = SiteSetting::get('iyzico_sandbox');
        if ($val !== null && $val !== '') {
            return $val === '1';
        }

        return ! str_contains(self::iyzicoBaseUrl(), 'api.iyzipay.com') || str_contains(self::iyzicoBaseUrl(), 'sandbox');
    }

    private static function credential(string $settingKey, string $configKey): string
    {
        $fromDb = trim((string) SiteSetting::get($settingKey, ''));

        if ($fromDb !== '') {
            return $fromDb;
        }

        return trim((string) config($configKey, ''));
    }

    /** @return array<string, string> */
    public static function adminValues(): array
    {
        return [
            'payment_gateway' => self::activeProvider(),
            'paytr_merchant_id' => self::paytrMerchantId(),
            'paytr_installment_table_token' => self::paytrInstallmentTableToken(),
            'paytr_test_mode' => self::paytrTestMode() ? '1' : '0',
            'iyzico_sandbox' => self::iyzicoSandbox() ? '1' : '0',
            'iyzico_base_url' => trim((string) SiteSetting::get('iyzico_base_url', '')),
            'has_paytr_key' => self::paytrMerchantKey() !== '',
            'has_paytr_salt' => self::paytrMerchantSalt() !== '',
            'has_iyzico_secret' => self::iyzicoSecretKey() !== '',
            'has_iyzico_api' => self::iyzicoApiKey() !== '',
        ];
    }
}
