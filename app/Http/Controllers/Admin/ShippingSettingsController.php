<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\StoreConfig;
use App\Support\PaymentMethodSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ShippingSettingsController extends Controller
{
    private const KEYS = [
        'cod_fee', 'vat_rate',
        'shipping_rate_standart', 'shipping_rate_hizli',
        'ship_standart_name', 'ship_standart_desc', 'ship_standart_eta',
        'ship_hizli_name', 'ship_hizli_desc', 'ship_hizli_eta',
        'pay_kredi_karti_name', 'pay_kredi_karti_desc',
        'pay_havale_name', 'pay_havale_desc',
        'pay_kapida_odeme_name', 'pay_kapida_odeme_desc',
    ];

    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.settings.edit', ['tab' => 'shipping']);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cod_fee' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'checkout_add_vat' => ['sometimes', 'boolean'],
            'shipping_methods' => ['nullable', 'array'],
            'shipping_methods.*.active' => ['nullable', 'boolean'],
            'shipping_methods.*.name' => ['nullable', 'string', 'max:80'],
            'shipping_methods.*.fee' => ['nullable', 'numeric', 'min:0'],
            'shipping_methods.*.desc' => ['nullable', 'string', 'max:200'],
            'shipping_methods.*.eta' => ['nullable', 'string', 'max:80'],
            'pay_kredi_karti_name' => ['nullable', 'string', 'max:80'],
            'pay_kredi_karti_desc' => ['nullable', 'string', 'max:200'],
            'pay_havale_name' => ['nullable', 'string', 'max:80'],
            'pay_havale_desc' => ['nullable', 'string', 'max:200'],
            'pay_kapida_odeme_name' => ['nullable', 'string', 'max:80'],
            'pay_kapida_odeme_desc' => ['nullable', 'string', 'max:200'],
            'payment_checkout_enabled' => ['nullable', 'array'],
            'payment_checkout_enabled.*' => ['string', 'max:32'],
            'payment_footer_enabled' => ['nullable', 'array'],
            'payment_footer_enabled.*' => ['string', 'max:32'],
        ]);

        PaymentMethodSettings::saveEnabled(
            $request->input('payment_checkout_enabled', []),
            $request->input('payment_footer_enabled', []),
        );
        SiteSetting::set('shipping_methods_json', json_encode($this->cleanShippingMethods($request->input('shipping_methods', [])), JSON_UNESCAPED_UNICODE));
        SiteSetting::set('checkout_add_vat', $request->boolean('checkout_add_vat') ? '1' : '0');

        unset($data['payment_checkout_enabled'], $data['payment_footer_enabled'], $data['shipping_methods'], $data['checkout_add_vat']);

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value !== null ? (string) $value : null);
        }
        Cache::forget('settings.all');

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'shipping'])
            ->with('success', 'Kargo ve ödeme ayarları kaydedildi.');
    }

    public function defaultFor(string $key): string
    {
        if ($key === 'cod_fee') {
            return (string) config('shipping.cod_fee');
        }
        if ($key === 'vat_rate') {
            return (string) config('shipping.vat_rate');
        }
        if ($key === 'checkout_add_vat') {
            return '0';
        }
        if (str_starts_with($key, 'shipping_rate_')) {
            $id = str_replace('shipping_rate_', '', $key);

            return (string) (config('shipping.shipping_rates')[$id] ?? 0);
        }
        if (str_starts_with($key, 'ship_')) {
            $parts = explode('_', $key, 3);
            $id = $parts[1];
            $field = $parts[2] ?? 'name';
            foreach (config('shipping.shipping_methods') as $m) {
                if ($m['id'] === $id) {
                    return (string) ($m[$field] ?? '');
                }
            }
        }
        if (str_starts_with($key, 'pay_')) {
            $rest = substr($key, 4);
            $lastUnderscore = strrpos($rest, '_');
            $id = substr($rest, 0, $lastUnderscore);
            $field = substr($rest, $lastUnderscore + 1);
            foreach (config('shipping.payment_methods') as $m) {
                if ($m['id'] === $id) {
                    return (string) ($m[$field] ?? '');
                }
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $methods
     * @return array<int, array{id: string, name: string, desc: string, eta: string, fee: float, active: bool}>
     */
    private function cleanShippingMethods(array $methods): array
    {
        $clean = [];

        foreach ($methods as $index => $method) {
            $name = trim((string) ($method['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $clean[] = [
                'id' => 'cargo_'.($index + 1),
                'name' => $name,
                'desc' => trim((string) ($method['desc'] ?? '')),
                'eta' => trim((string) ($method['eta'] ?? '')),
                'fee' => (float) str_replace(',', '.', (string) ($method['fee'] ?? 0)),
                'active' => (bool) ($method['active'] ?? false),
            ];
        }

        if ($clean === []) {
            return app(StoreConfig::class)->storedShippingMethods();
        }

        if (! collect($clean)->contains(fn (array $method) => $method['active'])) {
            $clean[0]['active'] = true;
        }

        return $clean;
    }
}
