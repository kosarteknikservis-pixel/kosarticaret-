<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\SiteSetting;
use App\Services\Parasut\ParasutClient;
use App\Services\Parasut\ParasutOrderInvoiceService;
use Illuminate\Http\RedirectResponse;

class ParasutIntegrationController extends Controller
{
    public function connect(ParasutClient $client): RedirectResponse
    {
        $required = [
            'parasut_client_id' => 'Client ID',
            'parasut_client_secret' => 'Client Secret',
            'parasut_company_id' => 'Firma ID',
            'parasut_username' => 'Paraşüt e-posta/kullanıcı adı',
            'parasut_password' => 'Paraşüt şifresi',
        ];

        foreach ($required as $key => $label) {
            if (trim((string) SiteSetting::get($key)) === '') {
                return redirect()
                    ->route('admin.settings.edit', ['tab' => 'integrations'])
                    ->withErrors(['parasut' => "Paraşüt {$label} alanını kaydedin."]);
            }
        }

        try {
            $client->authenticateWithPassword();
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.settings.edit', ['tab' => 'integrations'])
                ->withErrors(['parasut' => 'Paraşüt bağlantısı kurulamadı: '.$e->getMessage()]);
        }

        SiteSetting::set('parasut_enabled', '1');

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'integrations'])
            ->with('success', 'Paraşüt bağlantısı kuruldu.');
    }

    public function callback(): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.edit', ['tab' => 'integrations'])
            ->with('success', 'Paraşüt bu kurulumda paneldeki kullanıcı adı/şifre ile bağlanır; callback kullanılmaz.');
    }

    public function disconnect(): RedirectResponse
    {
        foreach (['parasut_access_token', 'parasut_refresh_token', 'parasut_token_expires_at'] as $key) {
            SiteSetting::set($key, null);
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'integrations'])
            ->with('success', 'Paraşüt bağlantısı kaldırıldı.');
    }

    public function syncOrder(Order $order, ParasutOrderInvoiceService $service): RedirectResponse
    {
        if ($order->parasut_sales_invoice_id) {
            return back()->withErrors(['parasut' => 'Bu sipariş daha önce Paraşüt’e aktarılmış.']);
        }

        try {
            $result = $service->createDraftInvoice($order);
        } catch (\Throwable $e) {
            $order->update([
                'parasut_status' => 'failed',
                'parasut_error' => $e->getMessage(),
            ]);

            return back()->withErrors(['parasut' => 'Paraşüt aktarımı başarısız: '.$e->getMessage()]);
        }

        $order->update([
            'parasut_sales_invoice_id' => $result['invoice_id'] ?? null,
            'parasut_status' => 'draft_created',
            'parasut_error' => null,
            'parasut_synced_at' => now(),
        ]);

        OrderLog::query()->create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'type' => 'parasut',
            'message' => 'Paraşüt taslak satış faturası oluşturuldu.',
            'new_values' => ['parasut_sales_invoice_id' => $order->parasut_sales_invoice_id],
        ]);

        return back()->with('success', 'Sipariş Paraşüt’e taslak satış faturası olarak aktarıldı.');
    }
}
