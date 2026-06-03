@extends('layouts.admin')
@section('title', 'Yarım Sepet Detayı')

@section('content')
    <x-admin.page-header title="Yarım sepet detayı" subtitle="Müşteri bilgisi, sepet içeriği, kaynak ve varsa bağlı sipariş bilgilerini sade şekilde inceleyin.">
        <x-slot:actions>
            <a href="{{ route('admin.analytics.index') }}" class="admin-btn admin-btn-secondary">Müşteri hareketleri</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-analytics-page grid gap-5 lg:grid-cols-3">
        <section class="admin-card p-5 sm:p-6 lg:col-span-2 min-w-0">
            <div class="admin-panel-head">
                <div>
                    <p class="admin-dashboard-eyebrow">Sepet</p>
                    <h2>Yarım sepet geçmişi</h2>
                </div>
            </div>

            <div class="grid gap-4">
                @forelse($carts as $cart)
                    @php($contactLines = collect([
                        $cart->customer_name ? ['label' => 'Ad soyad', 'value' => $cart->customer_name] : null,
                        $cart->email ? ['label' => 'E-posta', 'value' => $cart->email] : null,
                        $cart->phone ? ['label' => 'Telefon', 'value' => $cart->phone] : null,
                    ])->filter())
                    <article class="admin-analytics-cart-detail">
                        <div class="admin-analytics-cart-detail__head">
                            <div>
                                <p class="admin-dashboard-eyebrow">Sepet tutarı</p>
                                <strong>{{ number_format((float) $cart->subtotal, 2, ',', '.') }} ₺</strong>
                            </div>
                            <span>{{ $cart->status === 'checkout' ? 'Checkout aşaması' : 'Sepet aşaması' }}</span>
                        </div>

                        <div class="admin-analytics-contact-grid">
                            @forelse($contactLines as $line)
                                <div>
                                    <dt>{{ $line['label'] }}</dt>
                                    <dd>{{ $line['value'] }}</dd>
                                </div>
                            @empty
                                <div>
                                    <dt>Müşteri bilgisi</dt>
                                    <dd>İletişim bilgisi yok</dd>
                                </div>
                            @endforelse
                        </div>

                        <div class="admin-analytics-cart-items">
                            <p class="admin-dashboard-eyebrow">Ürünler</p>
                            @forelse(collect($cart->items ?? []) as $item)
                                <div>
                                    <span>{{ $item['name'] ?? 'Ürün' }}</span>
                                    <strong>{{ (int) ($item['quantity'] ?? 1) }} adet</strong>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Sepet ürünü kaydı yok.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Bu ziyaretçi için yarım sepet kaydı yok.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5 min-w-0">
            <section class="admin-card p-5 sm:p-6 space-y-3 min-w-0">
                <div>
                    <p class="admin-dashboard-eyebrow">Ziyaretçi</p>
                    <h2 class="font-bold text-slate-900 mt-1">Oturum bilgisi</h2>
                </div>
                <dl class="admin-analytics-dl text-sm text-slate-600 space-y-2">
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

            <section class="admin-card p-5 sm:p-6 space-y-3 min-w-0">
                <div>
                    <p class="admin-dashboard-eyebrow">Sipariş</p>
                    <h2 class="font-bold text-slate-900 mt-1">Bağlı siparişler</h2>
                </div>
                @forelse($orders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-action-row min-w-0">
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
