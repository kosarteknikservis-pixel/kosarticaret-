@extends('layouts.admin')

@section('title', 'İade talepleri')

@section('content')
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">İade talepleri</h1>
            <p class="admin-page-sub">Müşteri hesaplarından gelen iade / değişim talepleri</p>
        </div>
    </div>

    @if(session('success'))
        <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
    @endif
    @error('order')
        <p class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>
    @enderror

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.order-returns.index') }}" class="admin-btn {{ ! $statusFilter ? 'admin-btn-primary' : 'admin-btn-secondary' }} py-2 px-3 text-sm">Tümü</a>
        @foreach(['beklemede', 'onaylandi', 'reddedildi'] as $st)
            <a href="{{ route('admin.order-returns.index', ['status' => $st]) }}" class="admin-btn {{ $statusFilter === $st ? 'admin-btn-primary' : 'admin-btn-secondary' }} py-2 px-3 text-sm">{{ ucfirst($st) }}</a>
        @endforeach
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table min-w-full">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Sipariş</th>
                    <th>Müşteri</th>
                    <th>Sebep</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td class="whitespace-nowrap text-sm">{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $item->order_id) }}" class="font-semibold text-teal-700 hover:underline">
                                {{ $item->order?->order_number ?? '#'.$item->order_id }}
                            </a>
                        </td>
                        <td class="text-sm">
                            <div class="font-medium text-slate-900">{{ $item->order?->customer_name }}</div>
                            <div class="text-slate-500 break-all">{{ $item->order?->email }}</div>
                        </td>
                        <td class="text-sm">
                            <div>{{ $reasons[$item->reason] ?? $item->reason }}</div>
                            @if($item->note)
                                <div class="mt-1 text-xs text-slate-500">{{ $item->note }}</div>
                            @endif
                        </td>
                        <td class="text-sm font-semibold">{{ $item->status }}</td>
                        <td class="min-w-[220px]">
                            @if($item->isOpen())
                                <form method="post" action="{{ route('admin.order-returns.update', $item) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="admin_note" rows="2" class="admin-input text-sm" placeholder="Yönetici notu">{{ old('admin_note', $item->admin_note) }}</textarea>
                                    <label class="flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="refund" value="1">
                                        Onayda kart iadesi de yap
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button name="status" value="onaylandi" class="admin-btn admin-btn-primary py-1.5 px-3 text-xs">Onayla</button>
                                        <button name="status" value="reddedildi" class="admin-btn admin-btn-secondary py-1.5 px-3 text-xs">Reddet</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-xs text-slate-500">{{ $item->resolved_at?->format('d.m.Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-slate-500">İade talebi yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
