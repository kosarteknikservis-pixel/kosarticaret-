@extends('layouts.admin')
@section('title', 'Özet')

@section('content')
    <p class="admin-page-sub -mt-2 mb-6">Hoş geldiniz, {{ auth()->user()->name }}. Mağaza özeti aşağıda.</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="admin-card admin-stat">
            <p class="admin-stat-value admin-stat-value--accent">{{ $productCount }}</p>
            <p class="admin-stat-label">Toplam ürün</p>
            <a href="{{ route('admin.products.index') }}" class="admin-link mt-2 inline-block">Ürünleri yönet →</a>
        </div>
        <div class="admin-card admin-stat">
            <p class="admin-stat-value">{{ $orderCount }}</p>
            <p class="admin-stat-label">Toplam sipariş</p>
            <a href="{{ route('admin.orders.index') }}" class="admin-link mt-2 inline-block">Siparişler →</a>
        </div>
        <div class="admin-card admin-stat">
            <p class="admin-stat-value text-amber-600">{{ $lowStock->count() + $outOfStock->count() }}</p>
            <p class="admin-stat-label">Stok uyarısı</p>
            <p class="text-xs text-slate-400 mt-1">{{ $outOfStock->count() }} tükendi</p>
        </div>
        <div class="admin-card admin-stat">
            <p class="admin-stat-value text-rose-600">{{ $pendingReviews }}</p>
            <p class="admin-stat-label">Onay bekleyen yorum</p>
            @if($pendingReviews > 0)
                <a href="{{ route('admin.reviews.index') }}" class="admin-link mt-2 inline-block">İncele →</a>
            @endif
        </div>
        <div class="admin-card admin-stat">
            <p class="admin-stat-value admin-stat-value--accent">{{ $unreadContactMessages }}</p>
            <p class="admin-stat-label">Yeni iletişim</p>
            @if($unreadContactMessages > 0)
                <a href="{{ route('admin.contact-messages.index') }}" class="admin-link mt-2 inline-block">Mesajlar →</a>
            @endif
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Son siparişler</h2>
                <a href="{{ route('admin.orders.index') }}" class="admin-link text-sm">Tümü</a>
            </div>
            @if($recentOrders->isEmpty())
                <p class="p-6 text-sm text-slate-500">Henüz sipariş yok.</p>
            @else
                <table class="admin-table">
                    <thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tutar</th><th>Durum</th></tr></thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="link font-mono text-xs">{{ $order->order_number }}</a></td>
                                <td class="max-w-[140px] truncate">{{ $order->email }}</td>
                                <td class="font-semibold">{{ number_format($order->total, 2, ',', '.') }} ₺</td>
                                <td><span class="inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold">{{ \App\Support\OrderStatus::label($order->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Stok uyarıları</h2>
            </div>
            <div class="p-5 space-y-4 max-h-80 overflow-y-auto">
                @if($outOfStock->isNotEmpty())
                    <div>
                        <p class="text-xs font-bold uppercase text-red-600 tracking-wide mb-2">Stokta yok</p>
                        <ul class="space-y-2 text-sm">
                            @foreach($outOfStock->take(5) as $p)
                                <li class="flex justify-between gap-2">
                                    <a href="{{ route('admin.products.edit', $p) }}" class="link truncate">{{ $p->name }}</a>
                                    <span class="text-red-600 font-bold shrink-0">0</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($lowStock->isNotEmpty())
                    <div>
                        <p class="text-xs font-bold uppercase text-amber-600 tracking-wide mb-2">Düşük stok (≤3)</p>
                        <ul class="space-y-2 text-sm">
                            @foreach($lowStock->take(5) as $p)
                                <li class="flex justify-between gap-2">
                                    <a href="{{ route('admin.products.edit', $p) }}" class="link truncate">{{ $p->name }}</a>
                                    <span class="text-amber-600 font-bold shrink-0">{{ $p->stock }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($outOfStock->isEmpty() && $lowStock->isEmpty())
                    <p class="text-sm text-slate-500">Stok uyarısı yok. Harika!</p>
                @endif
            </div>
        </section>
    </div>

    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.products.create') }}" class="admin-card p-4 hover:border-teal-400 transition-colors group">
            <p class="font-semibold text-slate-900 group-hover:text-teal-700">+ Yeni ürün</p>
            <p class="text-xs text-slate-500 mt-1">Kataloga ürün ekle</p>
        </a>
        <a href="{{ route('admin.settings.edit') }}" class="admin-card p-4 hover:border-teal-400 transition-colors group">
            <p class="font-semibold text-slate-900 group-hover:text-teal-700">Site ayarları</p>
            <p class="text-xs text-slate-500 mt-1">Hero, iletişim, promo</p>
        </a>
        <a href="{{ route('admin.promotions.create') }}" class="admin-card p-4 hover:border-teal-400 transition-colors group">
            <p class="font-semibold text-slate-900 group-hover:text-teal-700">Kampanya ekle</p>
            <p class="text-xs text-slate-500 mt-1">Otomatik indirim</p>
        </a>
        <a href="{{ route('home') }}" target="_blank" class="admin-card p-4 hover:border-teal-400 transition-colors group">
            <p class="font-semibold text-slate-900 group-hover:text-teal-700">Mağazayı önizle</p>
            <p class="text-xs text-slate-500 mt-1">Canlı vitrin</p>
        </a>
    </div>
@endsection
