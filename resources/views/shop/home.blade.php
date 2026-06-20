@extends('layouts.shop')

@php
    $firstHomeBlock = $homeRows
        ->flatMap(fn ($row) => $row->banners)
        ->first(fn ($block) => $block->canDisplay() && ($block->isProductList() || $block->imageUrl()));
    $preloadImageBlock = $firstHomeBlock && ! $firstHomeBlock->isProductList() && $firstHomeBlock->imageUrl()
        ? $firstHomeBlock
        : null;
@endphp

@if($preloadImageBlock)
    @push('head')
        <link rel="preload"
              as="image"
              href="{{ $preloadImageBlock->imageUrl('banner') }}"
              @if($srcset = $preloadImageBlock->imageSrcset()) imagesrcset="{{ $srcset }}" imagesizes="(max-width: 767px) 100vw, 80rem" @endif
              fetchpriority="high">
    @endpush
@endif





@section('content')
    <h1 class="sr-only">{{ \App\Support\SiteName::get() }}</h1>
    <div class="shop-page shop-page--home">
    @include('shop.partials.home-layout', ['homeRows' => $homeRows])

    @include('shop.partials.home-brands', ['brands' => $featuredBrands])

    @include('shop.partials.home-references', ['references' => $projectReferences])

    @include('shop.partials.home-prefooter', [
        'recentBlogPosts' => $recentBlogPosts,
    ])
    </div>
@endsection

