@extends('layouts.shop')

@section('content')
    <div class="shop-page max-w-6xl">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
        <x-shop.page-hero :title="__('shop.html_sitemap_title')" :subtitle="__('shop.html_sitemap_lead')" />

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <section>
                <h2 class="text-base font-semibold text-slate-900 mb-3">{{ __('shop.categories') }}</h2>
                <ul class="space-y-3 text-sm">
                    @foreach($categoryTree as $root)
                        <li>
                            <a href="{{ $root->storefrontUrl() }}" class="font-medium text-slate-800 hover:underline">{{ $root->name }}</a>
                            @if($root->activeChildren->isNotEmpty())
                                <ul class="mt-1.5 ml-3 space-y-1 text-slate-600">
                                    @foreach($root->activeChildren as $child)
                                        <li><a href="{{ $child->storefrontUrl() }}" class="hover:underline">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section>
                <h2 class="text-base font-semibold text-slate-900 mb-3">{{ __('shop.brands') }}</h2>
                <ul class="space-y-1.5 text-sm">
                    @foreach($brands as $brand)
                        <li><a href="{{ route('brands.show', $brand) }}" class="text-slate-700 hover:underline">{{ $brand->name }}</a></li>
                    @endforeach
                </ul>
                <p class="mt-4">
                    <a href="{{ route('brands.index') }}" class="text-sm font-medium text-slate-800 hover:underline">{{ __('shop.brands') }}</a>
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-slate-900 mb-3">{{ __('shop.html_sitemap_pages') }}</h2>
                <ul class="space-y-1.5 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:underline">{{ __('shop.home') }}</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:underline">{{ __('shop.all_products') }}</a></li>
                    <li><a href="{{ route('contact.show') }}" class="hover:underline">{{ __('shop.contact') }}</a></li>
                    <li><a href="{{ route('blog.index') }}" class="hover:underline">{{ __('shop.blog') }}</a></li>
                    @if(\App\Support\PumpSelectorUiConfig::isEnabled())
                        <li><a href="{{ route('pump-selector.show') }}" class="hover:underline">{{ __('shop.pump_selector_nav') }}</a></li>
                    @endif
                    @foreach($pages as $page)
                        <li><a href="{{ route('pages.show', $page) }}" class="hover:underline">{{ $page->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        </div>

        @if($blogPosts->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-base font-semibold text-slate-900 mb-3">{{ __('shop.html_sitemap_blog') }}</h2>
                <ul class="grid gap-2 sm:grid-cols-2 text-sm">
                    @foreach($blogPosts as $post)
                        <li><a href="{{ route('blog.show', $post) }}" class="text-slate-700 hover:underline">{{ $post->title }}</a></li>
                    @endforeach
                </ul>
                <p class="mt-4">
                    <a href="{{ route('blog.index') }}" class="text-sm font-medium text-slate-800 hover:underline">{{ __('shop.blog') }}</a>
                </p>
            </section>
        @endif

        @if($recentProducts->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-base font-semibold text-slate-900 mb-3">{{ __('shop.html_sitemap_recent_products') }}</h2>
                <ul class="grid gap-2 sm:grid-cols-2 text-sm">
                    @foreach($recentProducts as $product)
                        <li><a href="{{ route('products.show', $product) }}" class="text-slate-700 hover:underline">{{ $product->name }}</a></li>
                    @endforeach
                </ul>
                <p class="mt-4">
                    <a href="{{ route('products.index') }}" class="text-sm font-medium text-slate-800 hover:underline">{{ __('shop.all_products') }}</a>
                </p>
            </section>
        @endif
    </div>
@endsection
