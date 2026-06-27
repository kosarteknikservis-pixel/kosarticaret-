<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\Shipping\OrderShipmentService;
use App\Support\CarrierConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderShipmentController extends Controller
{
    public function generatePlan(Order $order, OrderShipmentService $service): RedirectResponse
    {
        if ($order->isPendingPayment()) {
            return back()->withErrors(['order' => 'Ödeme tamamlanmadan kargo planı oluşturulamaz.']);
        }

        try {
            $service->generatePlan($order, auth()->id());
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Koli planı otomatik oluşturuldu.');
    }

    public function savePlan(Request $request, Order $order, OrderShipmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.package_number' => ['required', 'integer', 'min:1'],
            'packages.*.weight_kg' => ['nullable', 'numeric', 'min:0.1'],
            'packages.*.desi' => ['nullable', 'numeric', 'min:0.1'],
            'packages.*.items' => ['required', 'array', 'min:1'],
            'packages.*.items.*.order_item_id' => ['required', 'integer'],
            'packages.*.items.*.product_name' => ['required', 'string'],
            'packages.*.items.*.sku' => ['nullable', 'string'],
            'packages.*.items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $service->saveDraftPackages($order, $data['packages'], auth()->id());
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Koli planı kaydedildi.');
    }

    public function submit(Order $order, OrderShipment $shipment, OrderShipmentService $service): RedirectResponse
    {
        if ($shipment->order_id !== $order->id) {
            abort(404);
        }

        if (! CarrierConfig::isEnabled()) {
            return back()->withErrors(['order' => 'DHL entegrasyonu kapalı. Entegrasyonlar → Kargo ayarlarını açın.']);
        }

        try {
            $service->submitShipment($shipment, auth()->id());
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Koli DHL\'e bildirildi.');
    }

    public function submitAll(Order $order, OrderShipmentService $service): RedirectResponse
    {
        if (! CarrierConfig::isEnabled()) {
            return back()->withErrors(['order' => 'DHL entegrasyonu kapalı.']);
        }

        try {
            $service->submitAllDrafts($order, auth()->id());
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Tüm taslak koliler DHL\'e bildirildi.');
    }

    public function sync(Order $order, OrderShipment $shipment, OrderShipmentService $service): RedirectResponse
    {
        if ($shipment->order_id !== $order->id) {
            abort(404);
        }

        $service->syncShipment($shipment, auth()->id());

        return back()->with('success', 'Kargo durumu güncellendi.');
    }

    public function label(Order $order, OrderShipment $shipment, OrderShipmentService $service): Response|RedirectResponse
    {
        if ($shipment->order_id !== $order->id) {
            abort(404);
        }

        $contents = $service->labelContents($shipment);
        if ($contents === null) {
            return back()->withErrors(['order' => 'Taşıyıcı etiketi bulunamadı.']);
        }

        $filename = $order->order_number.'-p'.$shipment->package_number;
        $mime = match (true) {
            str_ends_with((string) $shipment->label_path, '.zpl') => 'text/plain',
            str_ends_with((string) $shipment->label_path, '.pdf') => 'application/pdf',
            default => 'text/plain',
        };

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
