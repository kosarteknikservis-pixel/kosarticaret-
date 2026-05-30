<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\PaymentGatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaytrCallbackController extends Controller
{
    public function __invoke(Request $request, OrderService $orders): Response
    {
        $merchantOid = $request->input('merchant_oid', '');
        $status = $request->input('status', '');
        $totalAmount = $request->input('total_amount', '');
        $hash = $request->input('hash', '');

        $salt = PaymentGatewayConfig::paytrMerchantSalt();
        $key = PaymentGatewayConfig::paytrMerchantKey();
        if ($salt === '' || $key === '') {
            Log::warning('paytr callback: credentials missing');

            return response('AYAR HATALI', 500);
        }
        $beklenen = base64_encode(hash_hmac(
            'sha256',
            $merchantOid.$salt.$status.$totalAmount,
            $key,
            true,
        ));

        if ($hash !== $beklenen) {
            Log::warning('paytr callback: hash mismatch', ['oid' => $merchantOid]);

            return response('HASH HATALI', 400);
        }

        if ($status === 'success' && $merchantOid) {
            $order = Order::query()->where('order_number', $merchantOid)->first();
            if ($order && $order->payment_status !== 'basarili') {
                $orders->confirmPayment($order);
            }
        }

        return response('OK');
    }
}
