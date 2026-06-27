<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Shipping\CarrierManager;
use App\Support\CarrierConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrierIntegrationController extends Controller
{
    public function editDhl(): View
    {
        return view('admin.integrations.shipping.dhl', [
            'values' => $this->values(),
            'configured' => CarrierConfig::isConfigured('dhl'),
            'sandbox' => CarrierConfig::isSandbox('dhl'),
        ]);
    }

    public function updateDhl(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dhl_enabled' => ['sometimes', 'boolean'],
            'dhl_sandbox' => ['sometimes', 'boolean'],
            'dhl_base_url' => ['nullable', 'string', 'max:255'],
            'dhl_client_id' => ['nullable', 'string', 'max:255'],
            'dhl_client_secret' => ['nullable', 'string', 'max:255'],
            'dhl_customer_number' => ['nullable', 'string', 'max:64'],
            'dhl_account_number' => ['nullable', 'string', 'max:64'],
            'dhl_password' => ['nullable', 'string', 'max:64'],
            'dhl_sender_name' => ['nullable', 'string', 'max:120'],
            'dhl_sender_phone' => ['nullable', 'string', 'max:30'],
            'dhl_sender_email' => ['nullable', 'email', 'max:150'],
            'dhl_sender_address' => ['nullable', 'string', 'max:255'],
            'dhl_sender_city' => ['nullable', 'string', 'max:80'],
            'dhl_sender_district' => ['nullable', 'string', 'max:80'],
            'dhl_sender_postal_code' => ['nullable', 'string', 'max:12'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'sms_provider' => ['nullable', 'in:log,netgsm'],
            'sms_sender' => ['nullable', 'string', 'max:20'],
            'sms_tracking_template' => ['nullable', 'string', 'max:320'],
            'netgsm_usercode' => ['nullable', 'string', 'max:64'],
            'netgsm_password' => ['nullable', 'string', 'max:64'],
            'netgsm_header' => ['nullable', 'string', 'max:20'],
        ]);

        if (! $request->filled('dhl_client_secret')) {
            unset($data['dhl_client_secret']);
        }
        if (! $request->filled('dhl_password')) {
            unset($data['dhl_password']);
        }
        if (! $request->filled('netgsm_password')) {
            unset($data['netgsm_password']);
        }

        SiteSetting::set('dhl_enabled', $request->boolean('dhl_enabled') ? '1' : '0');
        SiteSetting::set('dhl_sandbox', $request->boolean('dhl_sandbox') ? '1' : '0');
        SiteSetting::set('sms_enabled', $request->boolean('sms_enabled') ? '1' : '0');

        foreach ([
            'dhl_base_url', 'dhl_client_id', 'dhl_client_secret', 'dhl_customer_number', 'dhl_account_number', 'dhl_password',
            'dhl_sender_name', 'dhl_sender_phone', 'dhl_sender_email', 'dhl_sender_address',
            'dhl_sender_city', 'dhl_sender_district', 'dhl_sender_postal_code',
            'sms_provider', 'sms_sender', 'sms_tracking_template',
            'netgsm_usercode', 'netgsm_password', 'netgsm_header',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                SiteSetting::set($key, $data[$key] !== null ? (string) $data[$key] : null);
            }
        }

        return redirect()
            ->route('admin.integrations.shipping.dhl')
            ->with('success', 'DHL ve SMS ayarları kaydedildi.');
    }

    public function testDhl(CarrierManager $carriers): RedirectResponse
    {
        $result = $carriers->provider('dhl')->testConnection();

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? ($result['message'] ?? 'Bağlantı başarılı.')
                : ($result['message'] ?? 'Bağlantı başarısız.')
        );
    }

    /** @return array<string, string|null> */
    private function values(): array
    {
        $keys = [
            'dhl_enabled', 'dhl_sandbox', 'dhl_base_url', 'dhl_client_id', 'dhl_client_secret',
            'dhl_customer_number', 'dhl_account_number', 'dhl_password', 'dhl_sender_name', 'dhl_sender_phone', 'dhl_sender_email',
            'dhl_sender_address', 'dhl_sender_city', 'dhl_sender_district', 'dhl_sender_postal_code',
            'sms_enabled', 'sms_provider', 'sms_sender', 'sms_tracking_template',
            'netgsm_usercode', 'netgsm_password', 'netgsm_header',
        ];

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = SiteSetting::get($key);
        }

        return $values;
    }
}
