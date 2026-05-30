<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customers = User::query()
            ->customers()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->withCount('orders')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'totalCustomers' => User::query()->customers()->count(),
        ]);
    }

    public function show(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => Order::query()
                ->where('user_id', $customer->id)
                ->latest()
                ->paginate(15),
            'ordersTotal' => Order::query()->where('user_id', $customer->id)->sum('total'),
        ]);
    }
}
