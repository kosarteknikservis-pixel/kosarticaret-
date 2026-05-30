<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\PaymentGatewayConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function indexPayment(): View
    {
        $active = PaymentGatewayConfig::activeProvider();

        return view('admin.integrations.payment.index', [
            'activeProvider' => $active,
            'activeLabel' => PaymentGatewayConfig::label(),
            'providers' => [
                [
                    'id' => 'paytr',
                    'name' => 'PayTR',
                    'description' => 'Türkiye\'de yaygın sanal POS — kredi kartı tahsilatı.',
                    'url' => route('admin.integrations.payment.paytr'),
                    'configured' => PaymentGatewayConfig::isConfigured('paytr'),
                    'active' => $active === 'paytr',
                ],
                [
                    'id' => 'iyzico',
                    'name' => 'iyzico',
                    'description' => 'iyzico Checkout Form ile kredi kartı ödemesi.',
                    'url' => route('admin.integrations.payment.iyzico'),
                    'configured' => PaymentGatewayConfig::isConfigured('iyzico'),
                    'active' => $active === 'iyzico',
                ],
            ],
        ]);
    }

    public function editPaytr(): View
    {
        $values = PaymentGatewayConfig::adminValues();

        return view('admin.integrations.payment.paytr', [
            'values' => $values,
            'callbackUrl' => route('payment.paytr.callback'),
            'isActive' => $values['payment_gateway'] === 'paytr',
            'isConfigured' => PaymentGatewayConfig::isConfigured('paytr'),
        ]);
    }

    public function updatePaytr(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'paytr_merchant_id' => ['nullable', 'string', 'max:64'],
            'paytr_merchant_key' => ['nullable', 'string', 'max:255'],
            'paytr_merchant_salt' => ['nullable', 'string', 'max:255'],
            'paytr_test_mode' => ['sometimes', 'boolean'],
            'payment_gateway' => ['nullable', Rule::in(PaymentGatewayConfig::PROVIDERS)],
        ]);

        $data['paytr_test_mode'] = $request->boolean('paytr_test_mode') ? '1' : '0';

        if (! $request->filled('paytr_merchant_key')) {
            unset($data['paytr_merchant_key']);
        }
        if (! $request->filled('paytr_merchant_salt')) {
            unset($data['paytr_merchant_salt']);
        }

        unset($data['payment_gateway']);

        foreach ($data as $key => $value) {
            SiteSetting::set($key, (string) $value);
        }

        if ($request->boolean('set_active')) {
            SiteSetting::set('payment_gateway', 'paytr');
        } elseif ($request->input('payment_gateway') === 'mock') {
            SiteSetting::set('payment_gateway', 'mock');
        }

        Cache::forget('settings.all');

        return redirect()
            ->route('admin.integrations.payment.paytr')
            ->with('success', 'PayTR ayarları kaydedildi.');
    }

    public function editIyzico(): View
    {
        $values = PaymentGatewayConfig::adminValues();

        return view('admin.integrations.payment.iyzico', [
            'values' => $values,
            'callbackUrl' => route('payment.iyzico.callback'),
            'isActive' => $values['payment_gateway'] === 'iyzico',
            'isConfigured' => PaymentGatewayConfig::isConfigured('iyzico'),
        ]);
    }

    public function updateIyzico(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'iyzico_api_key' => ['nullable', 'string', 'max:255'],
            'iyzico_secret_key' => ['nullable', 'string', 'max:255'],
            'iyzico_sandbox' => ['sometimes', 'boolean'],
            'iyzico_base_url' => ['nullable', 'url', 'max:255'],
            'payment_gateway' => ['nullable', Rule::in(PaymentGatewayConfig::PROVIDERS)],
        ]);

        $data['iyzico_sandbox'] = $request->boolean('iyzico_sandbox', true) ? '1' : '0';

        if (! $request->filled('iyzico_api_key')) {
            unset($data['iyzico_api_key']);
        }
        if (! $request->filled('iyzico_secret_key')) {
            unset($data['iyzico_secret_key']);
        }

        unset($data['payment_gateway']);

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value !== null ? (string) $value : null);
        }

        if ($request->boolean('set_active')) {
            SiteSetting::set('payment_gateway', 'iyzico');
        } elseif ($request->input('payment_gateway') === 'mock') {
            SiteSetting::set('payment_gateway', 'mock');
        }

        Cache::forget('settings.all');

        return redirect()
            ->route('admin.integrations.payment.iyzico')
            ->with('success', 'iyzico ayarları kaydedildi.');
    }
}
