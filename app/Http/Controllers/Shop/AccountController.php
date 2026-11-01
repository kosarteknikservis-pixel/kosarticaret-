<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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

    public function profile(Request $request): View
    {
        $user = $request->user();
        $address = is_array($user->default_address) ? $user->default_address : [];

        return view('shop.account.profile', [
            'user' => $user,
            'address' => $address,
            'metaTitle' => 'Profilim',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'adres' => ['nullable', 'string', 'max:500'],
            'il' => ['nullable', 'string', 'max:80'],
            'ilce' => ['nullable', 'string', 'max:80'],
            'posta_kodu' => ['nullable', 'string', 'max:16'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'current_password' => ['required_with:password', 'current_password'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->default_address = array_filter([
            'adres' => $data['adres'] ?? null,
            'il' => $data['il'] ?? null,
            'ilce' => $data['ilce'] ?? null,
            'posta_kodu' => $data['posta_kodu'] ?? null,
        ], fn ($v) => filled($v));

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function order(Request $request, string $orderNumber): View
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('email', $request->user()->email)
            ->with(['items', 'returnRequests'])
            ->firstOrFail();

        return view('shop.account.order', [
            'order' => $order,
            'returnReasons' => OrderReturnRequest::reasonLabels(),
            'openReturn' => $order->returnRequests->firstWhere('status', 'beklemede'),
            'metaTitle' => 'Sipariş '.$order->order_number,
            ...Seo::noIndexMeta(),
        ]);
    }

    public function storeReturn(Request $request, string $orderNumber): RedirectResponse
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('email', $request->user()->email)
            ->firstOrFail();

        if (! in_array($order->status, ['teslim_edildi', 'kargoda'], true)) {
            return back()->withErrors(['return' => 'Bu sipariş için henüz iade talebi açılamaz.']);
        }

        if ($order->returnRequests()->where('status', 'beklemede')->exists()) {
            return back()->withErrors(['return' => 'Bekleyen bir iade talebiniz zaten var.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', Rule::in(array_keys(OrderReturnRequest::reasonLabels()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'status' => 'beklemede',
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'İade talebiniz alındı. En kısa sürede incelenecek.');
    }
}
