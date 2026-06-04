@extends('layouts.admin')

@section('title', 'Görsel Optimizasyonu')

@section('content')
    <div class="admin-image-optimizer">
        <section class="admin-image-optimizer__hero">
            <div class="admin-image-optimizer__hero-copy">
                <p class="admin-dashboard-eyebrow">Performans bakım aracı</p>
                <h2>Görsel optimizasyonu</h2>
                <p>Ürün, banner, kategori, marka, blog ve logo görsellerini panelden küçültüp optimize varyantlarını oluşturun.</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">Mağazayı aç</a>
        </section>

        <div class="admin-image-optimizer__stats">
            <div class="admin-image-optimizer__stat">
                <span class="admin-image-optimizer__stat-icon">□</span>
                <span class="admin-image-optimizer__stat-label">Kayıtlı görsel kaynağı</span>
                <strong>{{ $totalSources }}</strong>
                <small>{{ $existingSources }} dosya storage içinde mevcut</small>
            </div>
            <div class="admin-image-optimizer__stat">
                <span class="admin-image-optimizer__stat-icon">✓</span>
                <span class="admin-image-optimizer__stat-label">Optimize varyant hazır</span>
                <strong>{{ $variantReady }}</strong>
                <small>{{ $missingVariants }} kaynak için varyant eksik olabilir</small>
            </div>
            <div class="admin-image-optimizer__stat {{ $webpSupported ? '' : 'is-warning' }}">
                <span class="admin-image-optimizer__stat-icon">{{ $webpSupported ? 'WEBP' : '!' }}</span>
                <span class="admin-image-optimizer__stat-label">Sunucu desteği</span>
                <strong>{{ $webpSupported ? 'Hazır' : 'Eksik' }}</strong>
                <small>{{ $webpSupported ? 'GD WebP aktif' : 'Hosting PHP GD/WebP desteği açmalı' }}</small>
            </div>
        </div>

        <div class="admin-image-optimizer__actions">
            <section class="admin-image-optimizer__action-card">
                <div>
                    <p class="admin-dashboard-eyebrow">Güvenli işlem</p>
                    <h2>Optimize varyantları üret</h2>
                    <p>
                        Orijinal dosyalara dokunmadan ürün kartı, ürün detay, thumbnail, banner ve logo için küçük WebP dosyaları üretir.
                        Canlı sunucuda timeout yaşamamak için işlem küçük güvenli turlar halinde çalışır; kalan varsa butona tekrar basın.
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

            <section class="admin-image-optimizer__action-card admin-image-optimizer__action-card--warm">
                <div>
                    <p class="admin-dashboard-eyebrow">Daha güçlü işlem</p>
                    <h2>Büyük orijinalleri küçült</h2>
                    <p>
                        Çok büyük orijinal görselleri güvenli maksimum ölçülere indirir, ardından WebP varyantlarını yeniden üretir.
                        Veritabanındaki dosya yolları değişmez. Büyük dosyalarda işlem küçük turlara bölünür.
                    </p>
                </div>
                <form method="post" action="{{ route('admin.performance.images.optimize') }}"
                      onsubmit="return confirm('Büyük orijinal görseller güvenli turlar halinde küçültülecek ve varyantlar yeniden oluşturulacak. Devam edilsin mi?')">
                    @csrf
                    <input type="hidden" name="mode" value="shrink">
                    <button type="submit" class="admin-btn admin-btn-secondary" @disabled(! $webpSupported)>
                        Orijinalleri küçült ve optimize et
                    </button>
                </form>
            </section>
        </div>

        <section class="admin-image-optimizer__info">
            <h2>Ne zaman kullanılmalı?</h2>
            <div class="admin-image-optimizer__info-grid">
                <div>
                    <strong>Yeni toplu ürün importu sonrası</strong>
                    <span>Çok sayıda ürün görseli geldiyse varyantları tamamlayın.</span>
                </div>
                <div>
                    <strong>Lighthouse görsel uyarısı varsa</strong>
                    <span>"Resim yayınlamayı kolaylaştırın" uyarısını azaltmak için çalıştırın.</span>
                </div>
                <div>
                    <strong>Banner veya logo büyükse</strong>
                    <span>Panelden yüklenen büyük görselleri site hızına uygun hale getirin.</span>
                </div>
            </div>
        </section>
    </div>
@endsection
