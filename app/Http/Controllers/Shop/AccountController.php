<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('email', $request->user()->email)
            ->excludePendingPayment()
            ->latest()
            ->paginate(10);

        return view('shop.account.index', [
            'user' => $request->user(),
            'orders' => $orders,
            'metaTitle' => 'Hesabım',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function order(Request $request, string $orderNumber): View
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('email', $request->user()->email)
            ->with('items')
            ->firstOrFail();

        return view('shop.account.order', [
            'order' => $order,
            'metaTitle' => 'Sipariş '.$order->order_number,
            ...Seo::noIndexMeta(),
        ]);
    }
}
