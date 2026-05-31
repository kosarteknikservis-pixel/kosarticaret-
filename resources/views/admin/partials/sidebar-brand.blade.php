<div class="admin-sidebar-brand">
    <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-brand__link">
        @if($logoUrl = \App\Support\SiteLogo::url())
            <img src="{{ $logoUrl }}" alt="{{ \App\Support\SiteLogo::alt() }}" class="admin-sidebar-brand__logo" width="120" height="36">
        @else
            <span class="admin-sidebar-brand__mark">K</span>
        @endif
    </a>
</div>
