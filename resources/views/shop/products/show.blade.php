@extends('layouts.shop')

@section('content')
    <div class="shop-page shop-page--pdp">
    @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <div class="grid lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)] shop-pdp shop-reveal-group">
        @php
            $thumbs = collect();
            if ($product->imageUrl()) {
                $thumbs->push([
                    'url' => $product->imageUrl('product-pdp'),
                    'thumb' => $product->imageUrl('product-thumb'),
                    'srcset' => $product->imageSrcset(),
                    'alt' => $product->imageAltText(),
                ]);
            }
            foreach ($product->images as $img) {
                $url = $img->url('product-pdp');
                if (! $thumbs->contains(fn ($t) => $t['url'] === $url)) {
                    $thumbs->push([
                        'url' => $url,
                        'thumb' => $img->url('product-thumb'),
                        'srcset' => $img->srcset(),
                        'alt' => $img->alt ?? $product->name,
                    ]);
                }
            }
        @endphp
        <div class="shop-pdp-gallery {{ $thumbs->count() > 1 ? 'shop-pdp-gallery--thumbs' : '' }}"
             @if($thumbs->isNotEmpty()) data-pdp-gallery='@json($thumbs->values())' @endif>
            <button type="button"
                    class="shop-pdp-gallery__main"
                    id="product-main-image"
                    @if($thumbs->isEmpty()) disabled @endif
                    aria-label="{{ __('shop.enlarge_image') }}">
                @if($thumbs->isNotEmpty())
                    <span class="shop-pdp-gallery__figure">
                        <img src="{{ $thumbs->first()['url'] }}" @if($thumbs->first()['srcset']) srcset="{{ $thumbs->first()['srcset'] }}" sizes="(max-width: 767px) 100vw, 42rem" @endif alt="{{ $thumbs->first()['alt'] }}" class="shop-pdp-gallery__img" id="pdp-main-img" decoding="async" fetchpriority="high">
                    </span>
                @else
                    <x-shop.icon name="grid" class="w-24 h-24 text-slate-300" />
                @endif
            </button>
            @if($thumbs->count() > 1)
                <div class="shop-pdp-gallery__thumbs" role="list" aria-label="{{ __('shop.gallery') }}">
                    @foreach($thumbs as $i => $thumb)
                        <button type="button"
                                class="shop-pdp-thumb pdp-thumb {{ $i === 0 ? 'is-active' : '' }}"
                                data-gallery-thumb="{{ $thumb['url'] }}"
                                aria-label="{{ __('shop.image') }} {{ $i + 1 }}"
                                aria-current="{{ $i === 0 ? 'true' : 'false' }}">
                            <img src="{{ $thumb['thumb'] ?? $thumb['url'] }}" alt="{{ $thumb['alt'] }}" class="shop-pdp-thumb__img" loading="lazy" decoding="async" width="80" height="80">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="shop-pdp-info">
            <div class="shop-pdp-info__panel">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    @if($product->brand)
                        <a href="{{ route('brands.show', $product->brand) }}" class="shop-pdp-info__brand hover:underline">{{ $product->brand->name }}</a>
                        <span class="text-slate-300" aria-hidden="true">|</span>
                    @endif
                    <span class="font-mono text-xs">SKU: {{ $product->sku }}</span>
                </div>

                <h1 class="shop-pdp-info__title">{{ $product->name }}</h1>

                <div class="shop-pdp-badges" aria-label="Ürün avantajları">
                    <span class="shop-pdp-badge">KDV dahil fiyat</span>
                    <span class="shop-pdp-badge">Kurumsal teslimat</span>
                    @if($product->brand)
                        <span class="shop-pdp-badge">{{ $product->brand->name }} ürünü</span>
                    @endif
                </div>

                @if($product->review_count > 0)
                    <div class="mt-3">
                        @include('shop.partials.product-rating', ['rating' => $product->rating, 'count' => $product->review_count, 'size' => 'lg'])
                    </div>
                @endif

                @if($product->short_description)
                    <p class="mt-4 text-slate-600 leading-relaxed max-w-xl">{{ $product->short_description }}</p>
                @endif

                <div class="shop-pdp-price-box">
                    <div class="pdp-pb__top">
                        <p class="shop-pdp-price-box__amount">{{ number_format($product->price, 2, ',', '.') }} ₺</p>
                        @if($product->hasDiscount())
                            <span class="shop-pdp-price-box__badge">-%{{ $product->discountPercent() }}</span>
                        @endif
                    </div>
                    @if($product->hasDiscount())
                        <p class="pdp-pb__compare">{{ number_format($product->compare_at_price, 2, ',', '.') }} ₺</p>
                    @endif
                    @php $stockLabel = \App\Support\ShopStockDisplay::storefrontLabel($product); @endphp
                    @if($stockLabel)
                        <p class="pdp-pb__stock {{ $product->inStock() ? 'pdp-pb__stock--in' : 'pdp-pb__stock--out' }}">
                            @if($product->inStock())
                                <span class="pdp-pb__dot"></span>
                            @endif
                            {{ $stockLabel }}
                        </p>
                    @endif
                </div>

                @if($product->inStock())
                    <div class="shop-pdp-actions">
                        <div class="shop-pdp-qty" role="group" aria-label="{{ __('shop.quantity') }}">
                            <button type="button" data-qty-minus aria-label="-">−</button>
                            <input type="number" id="pdp-qty" value="1" min="1" max="{{ $product->stock }}" aria-label="{{ __('shop.quantity') }}">
                            <button type="button" data-qty-plus aria-label="+">+</button>
                        </div>
                        <button type="button" data-add-cart="{{ $product->slug }}" data-qty-from="#pdp-qty" class="shop-cart-btn btn-primary shop-btn-premium flex-1 min-w-[200px] py-3.5 text-base">
                            <x-shop.icon name="cart" class="w-5 h-5" />
                            {{ __('shop.add_to_cart') }}
                        </button>
                        <button type="button" data-toggle-favorite="{{ $product->slug }}" class="shop-pdp-fav" aria-label="{{ __('shop.add_favorite') }}">
                            <x-shop.icon name="heart" class="w-6 h-6" />
                        </button>
                        <button type="button" data-compare-add="{{ $product->slug }}" class="shop-pdp-compare" title="{{ __('shop.compare_add') }}">
                            {{ __('shop.compare_short') }}
                        </button>
                    </div>

                    @if(\App\Support\WhatsAppOrder::isEnabledForPdp())
                        @include('shop.partials.pdp-whatsapp-order', ['product' => $product])
                    @endif
                @endif

                @include('shop.partials.pdp-trust')
            </div>
        </div>
    </div>

    <section class="shop-pdp-content mt-14 shop-reveal">
        <div class="shop-pdp-tabs flex overflow-x-auto" role="tablist">
            <button type="button" data-pdp-tab="description" class="shop-pdp-tab pdp-tab is-active" role="tab" aria-selected="true">{{ __('shop.tab_description') }}</button>
            @if(!empty($product->specs))
                <button type="button" data-pdp-tab="specs" class="shop-pdp-tab pdp-tab" role="tab" aria-selected="false">{{ __('shop.tab_specs') }}</button>
            @endif
            <button type="button" data-pdp-tab="installments" class="shop-pdp-tab pdp-tab" role="tab" aria-selected="false">{{ __('shop.tab_installments') }}</button>
            <button type="button" data-pdp-tab="reviews" class="shop-pdp-tab pdp-tab" role="tab" aria-selected="false">
                {{ __('shop.tab_reviews') }} ({{ $product->review_count }})
            </button>
        </div>

        <div id="pdp-panel-description" class="pdp-panel shop-pdp-panel mt-6 shop-rich-content">
            <x-shop.rich-content :content="$product->description" />
        </div>

        @if(!empty($product->specs))
            <div id="pdp-panel-specs" class="pdp-panel shop-pdp-panel mt-6 hidden">
                <dl class="rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    @foreach($product->specs as $key => $value)
                        <div class="grid sm:grid-cols-2 bg-white even:bg-slate-50">
                            <dt class="px-4 py-3 text-sm font-semibold text-slate-700">{{ is_string($key) ? $key : $value['label'] ?? '' }}</dt>
                            <dd class="px-4 py-3 text-sm text-slate-600">{{ is_string($key) ? $value : ($value['value'] ?? '') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        <div id="pdp-panel-installments" class="pdp-panel shop-pdp-panel mt-6 hidden">
            @include('shop.partials.pdp-installments', ['product' => $product, 'installmentTable' => $installmentTable])
        </div>

        <div id="pdp-panel-reviews"
             class="pdp-panel shop-pdp-panel mt-6 hidden"
             @if($errors->hasAny(['author_name', 'email', 'rating', 'title', 'body'])) data-open-reviews-tab @endif>
            @include('shop.partials.pdp-reviews', ['product' => $product])
        </div>
    </section>

    @if($related->isNotEmpty())
        <section class="shop-related-section mt-16 pt-12 border-t border-slate-200 shop-reveal" aria-labelledby="related-heading">
            @include('shop.partials.product-carousel', [
                'products' => $related,
                'title' => __('shop.related_products'),
                'headingId' => 'related-heading',
            ])
        </section>
    @endif

    @if($thumbs->isNotEmpty())
        <dialog id="pdp-lightbox" class="shop-pdp-lightbox" aria-label="{{ __('shop.gallery') }}">
            <div class="shop-pdp-lightbox__inner">
                <button type="button" class="shop-pdp-lightbox__close" data-pdp-lightbox-close aria-label="{{ __('shop.menu_close') }}">
                    <span aria-hidden="true">×</span>
                </button>
                @if($thumbs->count() > 1)
                    <button type="button" class="shop-pdp-lightbox__nav shop-pdp-lightbox__nav--prev" data-pdp-lightbox-prev aria-label="{{ __('shop.lightbox_prev') }}">‹</button>
                    <button type="button" class="shop-pdp-lightbox__nav shop-pdp-lightbox__nav--next" data-pdp-lightbox-next aria-label="{{ __('shop.lightbox_next') }}">›</button>
                @endif
                <figure class="shop-pdp-lightbox__figure">
                    <img src="" alt="" class="shop-pdp-lightbox__img" id="pdp-lightbox-img">
                </figure>
            </div>
        </dialog>
    @endif
    @include('shop.partials.pdp-sticky-bar', ['product' => $product])
    </div>
@endsection

