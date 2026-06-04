@extends('layouts.admin')

@section('title', 'Görsel Optimizasyonu')

@section('content')
    <section class="admin-dashboard-hero">
        <div>
            <p class="admin-dashboard-eyebrow">Performans bakım aracı</p>
            <h2>Görsel optimizasyonu</h2>
            <p>Ürün, banner, kategori, marka, blog ve logo görsellerini panelden küçültüp optimize varyantlarını oluşturun.</p>
        </div>
        <div class="admin-dashboard-hero__actions">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">Mağazayı aç</a>
        </div>
    </section>

    <div class="admin-dashboard-stats">
        <div class="admin-metric-card">
            <span class="admin-metric-card__icon">□</span>
            <span class="admin-metric-card__label">Kayıtlı görsel kaynağı</span>
            <strong>{{ $totalSources }}</strong>
            <small>{{ $existingSources }} dosya storage içinde mevcut</small>
        </div>
        <div class="admin-metric-card">
            <span class="admin-metric-card__icon">✓</span>
            <span class="admin-metric-card__label">Optimize varyant hazır</span>
            <strong>{{ $variantReady }}</strong>
            <small>{{ $missingVariants }} kaynak için varyant eksik olabilir</small>
        </div>
        <div class="admin-metric-card {{ $webpSupported ? '' : 'admin-metric-card--danger' }}">
            <span class="admin-metric-card__icon">{{ $webpSupported ? 'WEBP' : '!' }}</span>
            <span class="admin-metric-card__label">Sunucu desteği</span>
            <strong>{{ $webpSupported ? 'Hazır' : 'Eksik' }}</strong>
            <small>{{ $webpSupported ? 'GD WebP aktif' : 'Hosting PHP GD/WebP desteği açmalı' }}</small>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        <section class="admin-card space-y-4">
            <div>
                <p class="admin-dashboard-eyebrow">Güvenli işlem</p>
                <h2 class="text-xl font-bold text-slate-900">Optimize varyantları üret</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Orijinal dosyalara dokunmadan ürün kartı, ürün detay, thumbnail, banner ve logo için küçük WebP dosyaları üretir.
                    Yeni yüklenen görsellerde bu otomatik çalışır; bu buton mevcut eski görselleri tamamlar.
                </p>
            </div>
            <form method="post" action="{{ route('admin.performance.images.optimize') }}">
                @csrf
                <input type="hidden" name="mode" value="variants">
                <button type="submit" class="admin-btn admin-btn-primary" @disabled(! $webpSupported)>
                    Varyantları oluştur
                </button>
            </form>
        </section>

        <section class="admin-card space-y-4 border border-amber-200 bg-amber-50/40">
            <div>
                <p class="admin-dashboard-eyebrow text-amber-700">Daha güçlü işlem</p>
                <h2 class="text-xl font-bold text-slate-900">Büyük orijinalleri küçült</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Çok büyük orijinal görselleri güvenli maksimum ölçülere indirir, ardından WebP varyantlarını yeniden üretir.
                    Veritabanındaki dosya yolları değişmez. İşlem ilk çalıştırmada uzun sürebilir.
                </p>
            </div>
            <form method="post" action="{{ route('admin.performance.images.optimize') }}"
                  onsubmit="return confirm('Büyük orijinal görseller küçültülecek ve varyantlar yeniden oluşturulacak. Devam edilsin mi?')">
                @csrf
                <input type="hidden" name="mode" value="shrink">
                <button type="submit" class="admin-btn admin-btn-secondary" @disabled(! $webpSupported)>
                    Orijinalleri küçült ve optimize et
                </button>
            </form>
        </section>
    </div>

    <section class="admin-card mt-5">
        <h2 class="text-lg font-bold text-slate-900">Ne zaman kullanılmalı?</h2>
        <div class="mt-3 grid md:grid-cols-3 gap-3 text-sm text-slate-600">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <strong class="block text-slate-900">Yeni toplu ürün importu sonrası</strong>
                Çok sayıda ürün görseli geldiyse varyantları tek seferde üretin.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <strong class="block text-slate-900">Lighthouse görsel uyarısı varsa</strong>
                "Resim yayınlamayı kolaylaştırın" uyarısını azaltmak için çalıştırın.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <strong class="block text-slate-900">Banner veya logo büyükse</strong>
                Panelden yüklenen büyük görselleri site hızına uygun hale getirin.
            </div>
        </div>
    </section>
@endsection
