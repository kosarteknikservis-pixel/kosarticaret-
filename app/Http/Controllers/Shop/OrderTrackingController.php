<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function show(): View
    {
        return view('shop.tracking.index', [
            'menuCategories' => Category::menu()->get(),
            'metaTitle' => 'Sipariş takip',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function lookup(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('email', $data['email'])
            ->with('items')
            ->first();

        return view('shop.tracking.index', [
            'menuCategories' => Category::menu()->get(),
            'order' => $order,
            'searched' => true,
            'metaTitle' => 'Sipariş takip',
            ...Seo::noIndexMeta(),
        ]);
    }
}
