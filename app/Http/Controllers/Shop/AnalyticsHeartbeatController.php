<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsHeartbeatController extends Controller
{
    public function __invoke(Request $request, AnalyticsTracker $tracker): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['nullable', 'string', 'max:1000'],
        ]);

        $tracker->trackHeartbeat($request, $validated['url'] ?? null);

        return response()->json(['ok' => true]);
    }
}
