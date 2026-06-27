<?php



namespace App\Support;



use App\Models\SiteSetting;



class CarrierConfig

{

    public static function defaultCarrier(): string

    {

        return (string) (SiteSetting::get('carrier_default') ?: config('carriers.default', 'dhl'));

    }



    public static function isEnabled(?string $carrier = null): bool

    {

        $carrier ??= self::defaultCarrier();



        if ($carrier === 'dhl') {

            return SiteSetting::get('dhl_enabled', config('carriers.dhl.enabled') ? '1' : '0') === '1';

        }



        return false;

    }



    public static function isSandbox(?string $carrier = null): bool

    {

        $carrier ??= self::defaultCarrier();



        if ($carrier === 'dhl') {

            return SiteSetting::get('dhl_sandbox', config('carriers.dhl.sandbox', true) ? '1' : '0') === '1';

        }



        return true;

    }



    public static function apiBaseUrl(?string $carrier = null): string

    {

        $carrier ??= self::defaultCarrier();



        if ($carrier === 'dhl') {

            $custom = SiteSetting::get('dhl_base_url');

            if (filled($custom)) {

                return rtrim((string) $custom, '/');

            }



            return self::isSandbox($carrier)

                ? (string) config('carriers.dhl.test_base_url')

                : (string) config('carriers.dhl.production_base_url');

        }



        return '';

    }



    public static function hasApiCredentials(?string $carrier = null): bool

    {

        $carrier ??= self::defaultCarrier();



        if ($carrier === 'dhl') {

            return filled(SiteSetting::get('dhl_client_id') ?: config('carriers.dhl.client_id'))

                && filled(SiteSetting::get('dhl_client_secret') ?: config('carriers.dhl.client_secret'))

                && filled(SiteSetting::get('dhl_customer_number') ?: SiteSetting::get('dhl_account_number') ?: config('carriers.dhl.customer_number'))

                && filled(SiteSetting::get('dhl_password') ?: config('carriers.dhl.password'));

        }



        return false;

    }



    public static function isConfigured(?string $carrier = null): bool

    {

        if (! self::isEnabled($carrier)) {

            return false;

        }



        $carrier ??= self::defaultCarrier();



        if ($carrier === 'dhl') {

            if (self::isSandbox($carrier) && ! self::hasApiCredentials($carrier)) {

                return true;

            }



            return self::hasApiCredentials($carrier);

        }



        return false;

    }



    /** @return array<string, mixed> */

    public static function dhlSettings(): array

    {

        return [

            'base_url' => self::apiBaseUrl('dhl'),

            'client_id' => SiteSetting::get('dhl_client_id') ?: config('carriers.dhl.client_id'),

            'client_secret' => SiteSetting::get('dhl_client_secret') ?: config('carriers.dhl.client_secret'),

            'customer_number' => SiteSetting::get('dhl_customer_number')

                ?: SiteSetting::get('dhl_account_number')

                ?: config('carriers.dhl.customer_number'),

            'password' => SiteSetting::get('dhl_password') ?: config('carriers.dhl.password'),

            'sender' => [

                'name' => SiteSetting::get('dhl_sender_name') ?: config('carriers.dhl.sender.name'),

                'phone' => SiteSetting::get('dhl_sender_phone') ?: config('carriers.dhl.sender.phone'),

                'email' => SiteSetting::get('dhl_sender_email') ?: config('carriers.dhl.sender.email'),

                'address' => SiteSetting::get('dhl_sender_address') ?: config('carriers.dhl.sender.address'),

                'city' => SiteSetting::get('dhl_sender_city') ?: config('carriers.dhl.sender.city'),

                'district' => SiteSetting::get('dhl_sender_district') ?: config('carriers.dhl.sender.district'),

                'postal_code' => SiteSetting::get('dhl_sender_postal_code') ?: config('carriers.dhl.sender.postal_code'),

            ],

        ];

    }



    public static function mapCarrierStatus(string $carrier, string $remoteStatus): string

    {

        $map = config("carriers.status_map.{$carrier}", []);



        return (string) ($map[strtolower($remoteStatus)] ?? $remoteStatus);

    }

}


