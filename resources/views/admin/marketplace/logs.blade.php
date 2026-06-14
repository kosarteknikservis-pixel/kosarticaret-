@extends('layouts.admin')
@section('title', 'Pazaryeri logları')

@section('content')
    <x-admin.page-header title="Pazaryeri sync logları" subtitle="API işlemleri ve hata geçmişi" />

    <x-admin.integrations-nav active="marketplace-logs" />

    <form method="get" class="admin-card p-4 sm:p-5 mb-5">
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="admin-label">Kanal</label>
                <input name="channel" value="{{ $filters['channel'] }}" class="admin-input" placeholder="trendyol">
            </div>
            <div>
                <label class="admin-label">Durum</label>
                <select name="status" class="admin-input">
                    <option value="">Tümü</option>
                    @foreach(['success', 'failed', 'pending'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-end">
                <button class="admin-btn admin-btn-primary px-5 py-2.5">Filtrele</button>
            </div>
        </div>
    </form>

    <div class="admin-card overflow-hidden">
        <table class="admin-table admin-table--stack">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kanal</th>
                    <th>İşlem</th>
                    <th>Ürün / Sipariş</th>
                    <th>Durum</th>
                    <th>Mesaj</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td data-label="Tarih" class="text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                        <td data-label="Kanal">{{ $log->channel_key ?: '—' }}</td>
                        <td data-label="İşlem" class="font-mono text-xs">{{ $log->action }}</td>
                        <td data-label="Referans" class="text-xs">
                            @if($log->product)
                                {{ $log->product->sku ?: $log->product->name }}
                            @elseif($log->order)
                                {{ $log->order->order_number }}
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="Durum">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($log->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td data-label="Mesaj" class="text-sm text-slate-600 max-w-lg">{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-8">Log bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
