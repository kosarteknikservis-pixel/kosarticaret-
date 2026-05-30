<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMailService;
use App\Services\OrderService;
use App\Services\Payment\IyzicoPaymentProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IyzicoCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        OrderService $orders,
        OrderMailService $mail,
        IyzicoPaymentProvider $iyzico,
    ): RedirectResponse {
        $token = $request->input('token');
        $siparisNo = $request->input('conversationId') ?? $request->input('merchant_oid');

        $order = null;
        if ($siparisNo) {
            $order = Order::query()->where('order_number', $siparisNo)->first();
        }
        if (! $order && $token) {
            $order = Order::query()
                ->where('shipping_address->iyzico_token', $token)
                ->first();
        }

        if (! $order) {
            Log::warning('iyzico callback: sipariş bulunamadı', $request->all());

            return redirect()->route('home')->with('error', 'Sipariş bulunamadı.');
        }

        $paid = false;
        if ($token) {
            $retrieve = $iyzico->retrieveCheckout($token);
            $paid = $retrieve['ok'] && in_array($retrieve['paymentStatus'] ?? '', ['SUCCESS', 'success'], true);
        }

        $status = $request->input('status', $request->input('paymentStatus'));
        if (! $paid && in_array($status, ['success', 'SUCCESS'], true)) {
            $paid = true;
        }

        if ($paid) {
            $orders->confirmPayment($order);
            $mail->sendOrderConfirmation($order->fresh('items'));

            return redirect()->route('checkout.success', ['order' => $order->order_number]);
        }

        $order->update(['payment_status' => 'basarisiz', 'status' => 'beklemede']);

        return redirect()->route('checkout.payment', ['order' => $order->order_number])
            ->with('error', 'Ödeme tamamlanamadı. Tekrar deneyin.');
    }
}
