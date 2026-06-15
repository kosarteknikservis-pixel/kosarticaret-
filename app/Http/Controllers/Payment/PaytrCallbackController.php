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

        if ($merchantOid === '') {
            return response('OK');
        }

        $order = $this->findOrder($merchantOid);
        if ($order === null) {
            Log::warning('paytr callback: order not found', ['oid' => $merchantOid]);

            return response('OK');
        }

        if ($status === 'success') {
            if ($order->payment_status !== 'basarili') {
                $orders->confirmPayment($order);
            }
        } elseif ($status === 'failed') {
            $reason = $request->input('failed_reason_msg') ?: $request->input('failed_reason_code');
            $orders->markPaymentFailed($order, 'paytr_callback', is_string($reason) ? $reason : null);
        }

        return response('OK');
    }

    private function findOrder(string $merchantOid): ?Order
    {
        return Order::query()->where('order_number', $merchantOid)->first()
            ?? Order::query()->where('order_number', $this->orderNumberFromMerchantOid($merchantOid))->first();
    }

    private function orderNumberFromMerchantOid(string $merchantOid): string
    {
        $prefix = config('kosar.order_prefix', 'KOS');

        if (str_starts_with($merchantOid, $prefix) && strlen($merchantOid) > strlen($prefix)) {
            return $prefix.'-'.substr($merchantOid, strlen($prefix));
        }

        return $merchantOid;
    }
}
