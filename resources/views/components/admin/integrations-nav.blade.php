@props(['active' => 'marketplace'])

<nav class="admin-integrations-nav mb-6" aria-label="Entegrasyonlar">
    <p class="admin-integrations-nav__root">
        <span class="admin-integrations-nav__group">Entegrasyonlar</span>
        <span class="admin-integrations-nav__sep">/</span>
        @if(str_starts_with($active, 'marketplace'))
            <span class="admin-integrations-nav__current">Pazaryerleri</span>
        @elseif($active === 'index')
            <span class="admin-integrations-nav__current">Ödeme</span>
        @else
            <a href="{{ route('admin.integrations.payment.index') }}" class="admin-integrations-nav__link">Ödeme</a>
            <span class="admin-integrations-nav__sep">/</span>
            <span class="admin-integrations-nav__current">{{ $active === 'paytr' ? 'PayTR' : 'iyzico' }}</span>
        @endif
    </p>

    <div class="admin-integrations-nav__tabs">
        <a href="{{ route('admin.integrations.payment.index') }}"
           class="admin-integrations-nav__tab {{ $active === 'index' || in_array($active, ['paytr', 'iyzico'], true) ? 'is-active' : '' }}">Ödeme</a>
        <a href="{{ route('admin.integrations.marketplace.index') }}"
           class="admin-integrations-nav__tab {{ str_starts_with($active, 'marketplace') ? 'is-active' : '' }}">Pazaryerleri</a>
    </div>

    @if(str_starts_with($active, 'marketplace'))
        <div class="admin-integrations-nav__tabs mt-2">
            <a href="{{ route('admin.integrations.marketplace.index') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace' ? 'is-active' : '' }}">Genel bakış</a>
            <a href="{{ route('admin.integrations.marketplace.channels.index') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-channels' ? 'is-active' : '' }}">Kanallar</a>
            <a href="{{ route('admin.integrations.marketplace.readiness') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-readiness' ? 'is-active' : '' }}">Katalog hazırlığı</a>
            <a href="{{ route('admin.integrations.marketplace.logistics-import') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-logistics' ? 'is-active' : '' }}">Barkod import</a>
            <a href="{{ route('admin.integrations.marketplace.mappings.index') }}" class="admin-integrations-nav__tab {{ str_starts_with($active, 'marketplace-mappings') ? 'is-active' : '' }}">Eşleştirmeler</a>
            <a href="{{ route('admin.integrations.marketplace.listings.index') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-listings' ? 'is-active' : '' }}">Listelemeler</a>
            <a href="{{ route('admin.integrations.marketplace.orders.index') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-orders' ? 'is-active' : '' }}">Sipariş sync</a>
            <a href="{{ route('admin.integrations.marketplace.logs') }}" class="admin-integrations-nav__tab {{ $active === 'marketplace-logs' ? 'is-active' : '' }}">Loglar</a>
        </div>
    @elseif($active !== 'index')
        <div class="admin-integrations-nav__tabs mt-2">
            <a href="{{ route('admin.integrations.payment.paytr') }}"
               class="admin-integrations-nav__tab {{ $active === 'paytr' ? 'is-active' : '' }}">PayTR</a>
            <a href="{{ route('admin.integrations.payment.iyzico') }}"
               class="admin-integrations-nav__tab {{ $active === 'iyzico' ? 'is-active' : '' }}">iyzico</a>
        </div>
    @endif
</nav>
