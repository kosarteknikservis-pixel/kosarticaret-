@extends('layouts.shop')

@php
    // LCP: ilk aktif slider görseli (ürün listesi / kampanya kutusu değil)
    $preloadImageBlock = $homeRows
        ->flatMap(fn ($row) => $row->banners)
        ->first(fn ($block) => $block->isSlider() && $block->canDisplay() && filled($block->image));

    // Aqua: ilk ürün sekmeleri → aqv → ikinci ürün sekmeleri. Ardışık product_list tek tab setine
    // birleşmesin diye satırlar utility öncesi / sonrası bölünür (row sort_order eşiği 15).
    $primaryHomeRows = $homeRows->filter(fn ($row) => (int) $row->sort_order < 15)->values();
    $secondaryHomeRows = $homeRows->filter(fn ($row) => (int) $row->sort_order >= 15)->values();
@endphp

@if($preloadImageBlock)
    @push('head')
        <link rel="preload"
              as="image"
              href="{{ $preloadImageBlock->imageUrl('banner') }}"
              @if($srcset = $preloadImageBlock->imageSrcset()) imagesrcset="{{ $srcset }}" imagesizes="(max-width: 767px) 100vw, (max-width: 1279px) 70vw, 852px" @endif
              fetchpriority="high">
    @endpush
@endif

@section('content')
    <div class="shop-page shop-page--home">
        <h1 class="shop-home-heading">{{ $homeH1 ?? \App\Support\SiteName::get() }}</h1>

        @include('shop.partials.home-layout', ['homeRows' => $primaryHomeRows])

        {{-- Aqua .aqv çift kart şeridi (ürün sekmelerinden sonra) — Koşar içerik --}}
        @include('shop.partials.home-utility-strip')

        @include('shop.partials.home-layout', ['homeRows' => $secondaryHomeRows])

        {{-- Aqua midBlock: .aqt + ürün şeridi yan yana --}}
        @include('shop.partials.home-facility-cta')

        {{-- Aqua .aqu üyelik CTA — sahte indirim yok; kayıt/giriş --}}
        @include('shop.partials.home-join-cta')

        {{-- Aqua .aqm — yalnız doğrulanmış mağaza URL’leri --}}
        @include('shop.partials.home-marketplace-strip')

        <div class="shop-home-after shop-reveal">
            @include('shop.partials.home-references', ['references' => $projectReferences])

            {{-- Aqua .aqs alt açıklama — doğrulanmış kategori/ürün bilgisi --}}
            @include('shop.partials.home-seo-content', ['siteStats' => $siteStats])

            @include('shop.partials.home-prefooter', [
                'recentBlogPosts' => $recentBlogPosts,
            ])
        </div>
    </div>
@endsection
