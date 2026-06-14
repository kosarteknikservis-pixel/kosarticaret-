<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceSyncLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceSyncLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $logs = MarketplaceSyncLog::query()
            ->with(['product:id,name,sku', 'order:id,order_number'])
            ->when($request->filled('channel'), fn ($q) => $q->where('channel_key', $request->string('channel')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.marketplace.logs', [
            'logs' => $logs,
            'filters' => [
                'channel' => $request->input('channel', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }
}
