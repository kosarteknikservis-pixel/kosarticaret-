<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminOrderEditor;
use App\Support\OrderStatus;
use App\Support\PaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()->with('items')->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($tracking = $request->query('tracking')) {
            $tracking === 'var'
                ? $query->whereNotNull('shipping_tracking')->where('shipping_tracking', '<>', '')
                : $query->where(function ($q) {
                    $q->whereNull('shipping_tracking')->orWhere('shipping_tracking', '');
                });
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($salesChannel = $request->query('sales_channel')) {
            $query->where('sales_channel', $salesChannel);
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'statuses' => OrderStatus::labels(),
            'paymentStatuses' => PaymentStatus::labels(),
            'salesChannels' => config('marketplace.sales_channels', []),
            'filters' => $request->only(['q', 'status', 'payment_status', 'tracking', 'date_from', 'date_to', 'sales_channel']),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'logs.user']);
        $districtsByCity = config('turkiye.cities', []);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::labels(),
            'paymentStatuses' => PaymentStatus::labels(),
            'cities' => array_keys($districtsByCity),
            'districtsByCity' => $districtsByCity,
            'products' => Product::query()
                ->select(['id', 'name', 'sku', 'price', 'stock'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Order $order, AdminOrderEditor $editor): RedirectResponse
    {
        $districtsByCity = config('turkiye.cities', []);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(OrderStatus::labels()))],
            'payment_status' => ['required', 'string', Rule::in(array_keys(PaymentStatus::labels()))],
            'shipping_tracking' => ['nullable', 'string', 'max:120'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'eposta' => ['required', 'email', 'max:150'],
            'telefon' => ['required', 'string', 'max:30'],
            'il' => ['required', 'string', Rule::in(array_keys($districtsByCity))],
            'ilce' => ['required', 'string', Rule::in($districtsByCity[$request->input('il')] ?? [])],
            'adres' => ['required', 'string', 'max:500'],
            'posta_kodu' => ['nullable', 'string', 'max:10'],
            'kurumsal_fatura' => ['sometimes', 'boolean'],
            'firma_adi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:200'],
            'vergi_numarasi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:30'],
            'vergi_dairesi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:120'],
            'fatura_adresi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.remove' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $editor->update($order, $data, $data['items'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $result['order'])
            ->with('success', $result['notify']
                ? 'Sipariş güncellendi ve müşteriye e-posta gönderildi.'
                : 'Sipariş güncellendi.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $orderNumber = $order->order_number;
        $order->delete();

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "{$orderNumber} numaralı sipariş silindi.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['integer', 'exists:orders,id'],
        ]);

        $count = DB::transaction(function () use ($data): int {
            $orders = Order::query()->whereIn('id', $data['orders'])->get();
            $count = $orders->count();

            foreach ($orders as $order) {
                $order->delete();
            }

            return $count;
        });

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "{$count} sipariş silindi.");
    }
}
