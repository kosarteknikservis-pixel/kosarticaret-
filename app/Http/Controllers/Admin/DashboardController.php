<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->orderBy('stock')->get();

        return view('admin.dashboard', [
            'productCount' => $products->count(),
            'orderCount' => Order::query()->count(),
            'lowStock' => $products->filter(fn ($p) => $p->stock > 0 && $p->stock <= 3),
            'outOfStock' => $products->filter(fn ($p) => $p->stock === 0),
            'recentOrders' => Order::query()->latest()->take(8)->get(),
            'pendingReviews' => ProductReview::query()->where('approved', false)->count(),
            'unreadContactMessages' => ContactMessage::query()->whereNull('read_at')->count(),
        ]);
    }
}
