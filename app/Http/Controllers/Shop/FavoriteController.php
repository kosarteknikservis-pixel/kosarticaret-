<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function __construct(private FavoriteService $favorites) {}

    public function index(): View
    {
        return view('shop.favorites.index', [
            'products' => $this->favorites->products(),
        ]);
    }

    public function toggle(Product $product): JsonResponse
    {
        $added = $this->favorites->toggle($product->id);

        return response()->json([
            'ok' => true,
            'added' => $added,
            'count' => $this->favorites->count(),
            'message' => $added ? 'Favorilere eklendi.' : 'Favorilerden çıkarıldı.',
        ]);
    }
}
