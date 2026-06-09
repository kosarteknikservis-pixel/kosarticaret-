@extends('layouts.shop')
@section('title', __('shop.cart'))

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.cart')],
        ]])
        @include('shop.partials.checkout-steps', ['step' => 1])

        <x-shop.page-hero :title="__('shop.cart')" />

        @if(count($lines) === 0)
            <x-shop.empty-state
                icon="cart"
                :title="__('shop.cart_empty')"
                :description="__('shop.cart_empty_sub')"
                class="shop-cart-empty"
            >
                <x-slot:action>
                    <a href="{{ route('products.index') }}" class="btn-primary px-8 py-3">{{ __('shop.hero_cta_shop') }}</a>
                </x-slot:action>
            </x-shop.empty-state>
        @else
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4" id="cart-lines">
                    @foreach($lines as $line)
                        @php $p = $line['product']; @endphp
                        <article class="shop-cart-line" data-cart-line data-slug="{{ $p->slug }}" data-max="{{ $p->stock }}" data-unit-price="{{ $p->price }}">
                            <a href="{{ route('products.show', $p) }}" class="shop-cart-line__thumb block">
                                @if($p->imageUrl())
                                    <img src="{{ $p->imageUrl('product-thumb') }}" alt="" loading="lazy" decoding="async" width="96" height="96" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"><x-shop.icon name="grid" class="w-8 h-8 text-slate-300" /></div>
                                @endif
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('products.show', $p) }}" class="font-semibold text-slate-900 hover:text-brand-700 line-clamp-2">{{ $p->name }}</a>
                                @if($p->brand)<p class="text-xs text-slate-500 mt-0.5">{{ $p->brand->name }}</p>@endif
                                <p class="shop-cart-line__price mt-2">{{ number_format($p->price, 2, ',', '.') }} ₺</p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <div class="shop-qty" role="group" aria-label="{{ __('shop.quantity') }}">
                                        <button type="button" data-cart-qty-minus aria-label="-">−</button>
                                        <input type="number" data-cart-qty value="{{ $line['quantity'] }}" min="1" max="{{ $p->stock }}" aria-label="{{ __('shop.quantity') }}">
                                        <button type="button" data-cart-qty-plus aria-label="+">+</button>
                                    </div>
                                    <button type="button" data-cart-remove class="shop-cart-line__remove">{{ __('shop.remove') }}</button>
                                </div>
                            </div>
                            <p class="shop-cart-line__total" data-line-total>{{ number_format($line['line_total'], 2, ',', '.') }} ₺</p>
                        </article>
                    @endforeach
                </div>

                <aside class="lg:col-span-1">
                    <div class="shop-panel shop-panel--sticky">
                        <h2 class="shop-panel__title">{{ __('shop.order_summary') }}</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-500">{{ __('shop.subtotal') }}</dt><dd class="font-semibold text-slate-900" id="cart-page-subtotal">{{ number_format($subtotal, 2, ',', '.') }} ₺</dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-slate-500 leading-relaxed">{{ __('shop.checkout_estimate_note') }}</p>
                        @include('shop.partials.pdp-trust')
                        <a href="{{ route('checkout.show') }}" class="mt-6 btn-primary w-full py-3.5 text-center block">{{ __('shop.checkout_cta') }}</a>
                        <a href="{{ route('products.index') }}" class="mt-3 block text-center text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('shop.continue_shopping') }}</a>

                        @include('shop.partials.cart-quote-form')
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-cart-line]').forEach(initCartLine);
    function initCartLine(row) {
        const slug = row.dataset.slug;
        const max = parseInt(row.dataset.max || '99', 10);
        const input = row.querySelector('[data-cart-qty]');
        const totalEl = row.querySelector('[data-line-total]');
        const unitPrice = parseFloat(row.dataset.unitPrice || '0');
        const update = async (qty) => {
            const res = await fetch(`/sepet/ajax/${slug}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ quantity: qty }),
            });
            const data = await res.json();
            if (!data.ok) return;
            if (qty === 0) { row.remove(); if (!document.querySelector('[data-cart-line]')) location.reload(); return; }
            input.value = qty;
            document.getElementById('cart-page-subtotal')?.textContent = data.subtotal_formatted;
            document.querySelectorAll('[data-cart-count]').forEach(el => { el.textContent = data.count; el.classList.toggle('hidden', data.count < 1); });
            if (totalEl && unitPrice) totalEl.textContent = (unitPrice * qty).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺';
        };
        row.querySelector('[data-cart-qty-minus]')?.addEventListener('click', () => update(Math.max(0, parseInt(input.value, 10) - 1));
        row.querySelector('[data-cart-qty-plus]')?.addEventListener('click', () => update(Math.min(max, parseInt(input.value, 10) + 1));
        row.querySelector('[data-cart-remove]')?.addEventListener('click', () => update(0));
        input?.addEventListener('change', () => update(Math.min(max, Math.max(1, parseInt(input.value, 10) || 1)));
    }
</script>
@endpush
