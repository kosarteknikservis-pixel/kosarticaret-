@props(['title', 'subtitle' => null, 'intro' => null, 'breadcrumbs' => null, 'products', 'brands' => collect()])

<div class="shop-page shop-page--catalog">
@if($breadcrumbs)
    @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
@endif

<x-shop.page-hero :title="$title" :subtitle="$subtitle" />

<div class="lg:grid lg:grid-cols-4 lg:gap-8 shop-catalog-grid">
    <div id="filter-drawer-overlay" class="fixed inset-0 z-50 hidden lg:hidden bg-slate-900/40 backdrop-blur-sm" aria-hidden="true">
        <aside id="filter-drawer-panel" class="absolute left-0 top-0 h-full w-[min(100%,20rem)] bg-white shadow-2xl overflow-y-auto p-4 translate-x-[-100%]">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <span class="font-bold text-slate-900">{{ __('shop.filters') }}</span>
                <button type="button" id="filter-drawer-close" class="shop-header-icon" aria-label="{{ __('shop.menu_close') }}">
                    <x-shop.icon name="x" class="shop-header-icon__svg" />
                </button>
            </div>
            @include('shop.partials.catalog-filters', ['brands' => $brands])
        </aside>
    </div>

    <aside class="hidden lg:block lg:col-span-1">
        <div class="sticky top-28">
            @include('shop.partials.catalog-filters', ['brands' => $brands])
        </div>
    </aside>

    <div class="lg:col-span-3 shop-catalog-main">
        @include('shop.partials.catalog-active-filters', ['brands' => $brands])
        @include('shop.partials.catalog-toolbar', ['products' => $products])

        <div class="grid gap-4 grid-cols-2 md:grid-cols-3 shop-reveal-group">
            @forelse($products as $p)
                @include('shop.partials.product-card', ['product' => $p])
            @empty
                <x-shop.empty-state
                    icon="search"
                    :title="__('shop.no_products')"
                    class="shop-catalog-empty col-span-full"
                >
                    <x-slot:action>
                        <a href="{{ route('products.index') }}" class="btn-primary">{{ __('shop.browse_all') }}</a>
                    </x-slot:action>
                </x-shop.empty-state>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="mt-10 shop-pagination shop-reveal">{{ $products->links('vendor.pagination.shop') }}</div>
        @endif
    </div>
</div>

@if(filled($intro))
    <section class="shop-catalog-intro shop-reveal" aria-label="{{ __('shop.tab_description') }}">
        <x-shop.rich-content :content="$intro" />
    </section>
@endif
</div>
