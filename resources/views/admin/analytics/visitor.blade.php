@extends('layouts.admin')
@section('title', 'Ziyaretçi Yolculuğu')

@section('content')
    <x-admin.page-header title="Ziyaretçi yolculuğu" subtitle="Anonim ziyaretçinin sayfa, ürün, sepet, checkout ve sipariş akışını zaman sırasıyla inceleyin.">
        <x-slot:actions>
            <a href="{{ route('admin.analytics.index') }}" class="admin-btn admin-btn-secondary">Müşteri hareketleri</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="admin-card p-5 sm:p-6 lg:col-span-2">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Akış</p>
                    <h2>Zaman çizelgesi</h2>
                </div>
            </div>

            <div class="admin-journey-timeline">
                @forelse($timeline as $item)
                    @php($event = $item['event'])
                    <article class="admin-journey-event">
                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <span>{{ $event->occurred_at?->format('d.m.Y H:i:s') }}</span>
                        </div>
                        @if($event->product)
                            <a href="{{ route('products.show', $event->product->slug) }}" target="_blank" class="link">{{ $event->product->name }}</a>
                        @elseif($event->order)
                            <a href="{{ route('admin.orders.show', $event->order) }}" class="link">{{ $event->order->order_number }}</a>
                        @endif
                        <p class="break-words">{{ $event->url }}</p>
                        @if($event->metadata)
                            <dl>
                                @foreach($event->metadata as $key => $value)
                                    <div><dt>{{ $key }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>
                                @endforeach
                            </dl>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Bu ziyaretçi için olay kaydı yok.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5">
            <section class="admin-card p-5 sm:p-6 space-y-3">
                <div>
                    <p class="admin-dashboard-eyebrow">Ziyaretçi</p>
                    <h2 class="font-bold text-slate-900 mt-1">Oturum bilgisi</h2>
                </div>
                <dl class="text-sm text-slate-600 space-y-2">
                    <div><dt class="font-semibold text-slate-900">Cihaz</dt><dd>{{ $visitor->device_type ?: 'Bilinmiyor' }}</dd></div>
                    <div><dt class="font-semibold text-slate-900">İlk görüldü</dt><dd>{{ $visitor->first_seen_at?->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="font-semibold text-slate-900">Son görüldü</dt><dd>{{ $visitor->last_seen_at?->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="font-semibold text-slate-900">Kaynak</dt><dd>{{ $visitor->utm_source ?: 'direct' }}</dd></div>
                    @if($visitor->utm_medium)
                        <div><dt class="font-semibold text-slate-900">Medium</dt><dd>{{ $visitor->utm_medium }}</dd></div>
                    @endif
                    @if($visitor->utm_campaign)
                        <div><dt class="font-semibold text-slate-900">Kampanya</dt><dd>{{ $visitor->utm_campaign }}</dd></div>
                    @endif
                    <div><dt class="font-semibold text-slate-900">İlk giriş</dt><dd class="break-words">{{ $visitor->landing_url }}</dd></div>
                    <div><dt class="font-semibold text-slate-900">Son sayfa</dt><dd class="break-words">{{ $visitor->last_url }}</dd></div>
                </dl>
            </section>

            <section class="admin-card p-5 sm:p-6 space-y-3">
                <div>
                    <p class="admin-dashboard-eyebrow">Sepet</p>
                    <h2 class="font-bold text-slate-900 mt-1">Yarım sepet geçmişi</h2>
                </div>
                @forelse($carts as $cart)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <strong class="text-slate-900">{{ number_format((float) $cart->subtotal, 2, ',', '.') }} ₺</strong>
                            <span class="text-xs font-bold uppercase text-slate-500">{{ $cart->status }}</span>
                        </div>
                        <p class="mt-1 text-slate-500">{{ $cart->email ?: ($cart->phone ?: 'İletişim yok') }}</p>
                        <p class="mt-2 text-slate-700">{{ collect($cart->items ?? [])->pluck('name')->take(3)->implode(', ') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sepet kaydı yok.</p>
                @endforelse
            </section>

            <section class="admin-card p-5 sm:p-6 space-y-3">
                <div>
                    <p class="admin-dashboard-eyebrow">Sipariş</p>
                    <h2 class="font-bold text-slate-900 mt-1">Bağlı siparişler</h2>
                </div>
                @forelse($orders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-action-row">
                        <span>{{ $order->order_number }}</span>
                        <strong>{{ number_format((float) $order->total, 2, ',', '.') }} ₺</strong>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Bu ziyaretçi henüz siparişe dönmemiş.</p>
                @endforelse
            </section>
        </aside>
    </div>
@endsection
