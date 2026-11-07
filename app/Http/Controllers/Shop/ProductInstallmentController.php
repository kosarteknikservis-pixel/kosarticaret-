<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Payment\InstallmentOptionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductInstallmentController extends Controller
{
    public function __invoke(Request $request, Product $product, InstallmentOptionsService $installments): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $unitPrice = $request->filled('amount')
            ? (float) $request->query('amount')
            : (float) $product->price;
        $qty = max(1, (int) $request->query('qty', 1));
        $amount = round($unitPrice * ($request->has('qty') ? $qty : 1), 2);

        $table = $installments->forAmount($amount);

        return response()->json([
            ...$table,
            'formatted_amount' => number_format($table['amount'], 2, ',', '.').' ₺',
            'html' => view('shop.partials.pdp-installments-table', ['installmentTable' => $table])->render(),
        ]);
    }
}
