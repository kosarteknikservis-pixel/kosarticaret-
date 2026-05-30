@props(['active' => 'index'])

<nav class="admin-integrations-nav mb-6" aria-label="Entegrasyonlar">
    <p class="admin-integrations-nav__root">
        <span class="admin-integrations-nav__group">Entegrasyonlar</span>
        <span class="admin-integrations-nav__sep">/</span>
        @if($active === 'index')
            <span class="admin-integrations-nav__current">Ödeme</span>
        @else
            <a href="{{ route('admin.integrations.payment.index') }}" class="admin-integrations-nav__link">Ödeme</a>
            <span class="admin-integrations-nav__sep">/</span>
            <span class="admin-integrations-nav__current">{{ $active === 'paytr' ? 'PayTR' : 'iyzico' }}</span>
        @endif
    </p>
    @if($active !== 'index')
        <div class="admin-integrations-nav__tabs">
            <a href="{{ route('admin.integrations.payment.paytr') }}"
               class="admin-integrations-nav__tab {{ $active === 'paytr' ? 'is-active' : '' }}">PayTR</a>
            <a href="{{ route('admin.integrations.payment.iyzico') }}"
               class="admin-integrations-nav__tab {{ $active === 'iyzico' ? 'is-active' : '' }}">iyzico</a>
        </div>
    @endif
</nav>
