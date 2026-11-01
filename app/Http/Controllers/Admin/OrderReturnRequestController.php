<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturnRequest;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderReturnRequest::query()
            ->with(['order', 'user'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.order-returns.index', [
            'requests' => $query->paginate(30)->withQueryString(),
            'reasons' => OrderReturnRequest::reasonLabels(),
            'statusFilter' => $status,
        ]);
    }

    public function update(Request $request, OrderReturnRequest $returnRequest, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['beklemede', 'onaylandi', 'reddedildi', 'iptal'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'refund' => ['sometimes', 'boolean'],
        ]);

        $returnRequest->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? $returnRequest->admin_note,
            'resolved_at' => in_array($data['status'], ['onaylandi', 'reddedildi', 'iptal'], true)
                ? now()
                : $returnRequest->resolved_at,
        ]);

        $message = 'İade talebi güncellendi.';

        if ($data['status'] === 'onaylandi' && $request->boolean('refund')) {
            $result = $orders->refundPayment($returnRequest->order()->firstOrFail());
            $message .= ' '.$result['message'];
            if (! $result['ok']) {
                return back()->with('success', 'Talep onaylandı.')->withErrors(['order' => $result['message']]);
            }
        }

        return back()->with('success', $message);
    }
}
