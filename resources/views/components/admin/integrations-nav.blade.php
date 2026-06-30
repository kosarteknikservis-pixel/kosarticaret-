@props(['active' => 'marketplace'])

@php
    $isPayment = $active === 'index' || in_array($active, ['paytr', 'iyzico'], true);
    $isShipping = str_starts_with($active, 'shipping');
    $isMarketplace = str_starts_with($active, 'marketplace');
@endphp

<nav class="admin-integrations-nav mb-6" aria-label="Entegrasyonlar">
    <p class="admin-integrations-nav__label">Entegrasyon alanı</p>

    <div class="admin-integrations-nav__primary">
        <a href="{{ route('admin.integrations.payment.index') }}"
           class="admin-integrations-nav__tab {{ $isPayment ? 'is-active' : '' }}">Ödeme</a>
        <a href="{{ route('admin.integrations.shipping.dhl') }}"
           class="admin-integrations-nav__tab {{ $isShipping ? 'is-active' : '' }}">Kargo</a>
        <a href="{{ route('admin.integrations.marketplace.index') }}"
           class="admin-integrations-nav__tab {{ $isMarketplace ? 'is-active' : '' }}">Pazaryerleri</a>
    </div>

    @if($isPayment)
        <div class="admin-integrations-nav__secondary">
            <a href="{{ route('admin.integrations.payment.index') }}"
               class="admin-integrations-nav__subtab {{ $active === 'index' ? 'is-active' : '' }}">Genel bakış</a>
            <a href="{{ route('admin.integrations.payment.paytr') }}"
               class="admin-integrations-nav__subtab {{ $active === 'paytr' ? 'is-active' : '' }}">PayTR</a>
            <a href="{{ route('admin.integrations.payment.iyzico') }}"
               class="admin-integrations-nav__subtab {{ $active === 'iyzico' ? 'is-active' : '' }}">iyzico</a>
        </div>
    @elseif($isMarketplace)
        <div class="admin-integrations-nav__secondary">
            <a href="{{ route('admin.integrations.marketplace.index') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace' ? 'is-active' : '' }}">Genel bakış</a>
            <a href="{{ route('admin.integrations.marketplace.channels.index') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-channels' ? 'is-active' : '' }}">Kanallar</a>
            <a href="{{ route('admin.integrations.marketplace.readiness') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-readiness' ? 'is-active' : '' }}">Katalog hazırlığı</a>
            <a href="{{ route('admin.integrations.marketplace.mappings.index') }}"
               class="admin-integrations-nav__subtab {{ str_starts_with($active, 'marketplace-mappings') ? 'is-active' : '' }}">Eşleştirmeler</a>
            <a href="{{ route('admin.integrations.marketplace.listings.index') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-listings' ? 'is-active' : '' }}">Listelemeler</a>
            <a href="{{ route('admin.integrations.marketplace.orders.index') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-orders' ? 'is-active' : '' }}">Sipariş sync</a>
            <a href="{{ route('admin.integrations.marketplace.logistics-import') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-logistics' ? 'is-active' : '' }}">Barkod import</a>
            <a href="{{ route('admin.integrations.marketplace.logs') }}"
               class="admin-integrations-nav__subtab {{ $active === 'marketplace-logs' ? 'is-active' : '' }}">Loglar</a>
        </div>
    @endif
</nav>
