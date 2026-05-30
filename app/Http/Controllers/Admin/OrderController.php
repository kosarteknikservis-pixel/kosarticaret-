<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', [
            'orders' => Order::query()->with('items')->latest()->paginate(20),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items.product');

        return view('admin.orders.show', ['order' => $order]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(\App\Support\OrderStatus::labels()))],
            'payment_status' => ['nullable', 'string'],
            'shipping_tracking' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
        ]);

        $order->update($data);

        return back()->with('success', 'Sipariş güncellendi.');
    }
}
