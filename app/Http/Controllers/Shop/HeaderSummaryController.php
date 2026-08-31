<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;

class HeaderSummaryController extends Controller
{
    public function __invoke(CartService $cart, FavoriteService $favorites): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'cart_count' => $cart->count(),
            'favorite_count' => $favorites->count(),
            'csrf_token' => csrf_token(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
