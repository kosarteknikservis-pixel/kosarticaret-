@extends('layouts.shop')
@section('title', __('shop.compare_title'))

@section('content')
    <div class="shop-page shop-page--compare">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.compare_title')],
        ]])

        <x-shop.page-hero :title="__('shop.compare_title')" :subtitle="__('shop.compare_sub')" />

        @if($products->isEmpty())
            <x-shop.empty-state :title="__('shop.compare_empty_title')" :description="__('shop.compare_empty_message')">
                <x-slot:action>
                    <a href="{{ route('products.index') }}" class="btn-primary inline-flex">{{ __('shop.all_products') }}</a>
                </x-slot:action>
            </x-shop.empty-state>
        @else
            <div class="shop-compare-table-wrap shop-reveal">
                <table class="shop-compare-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('shop.compare_feature') }}</th>
                            @foreach($products as $product)
                                <th scope="col">
                                    <a href="{{ route('products.show', $product) }}" class="shop-compare-table__name">{{ $product->name }}</a>
                                    <form method="post" action="{{ route('compare.remove', $product->slug) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="shop-compare-table__remove">{{ __('shop.compare_remove') }}</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">{{ __('shop.price') }}</th>
                            @foreach($products as $product)
                                <td>{{ number_format($product->price, 2, ',', '.') }} ₺</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row">{{ __('shop.brand') }}</th>
                            @foreach($products as $product)
                                <td>{{ $product->brand?->name ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row">{{ __('shop.stock') }}</th>
                            @foreach($products as $product)
                                <td>{{ $product->stock > 0 ? __('shop.in_stock') : __('shop.out_of_stock') }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row">SKU</th>
                            @foreach($products as $product)
                                <td>{{ $product->sku ?? '—' }}</td>
                            @endforeach
                        </tr>
                        @php
                            $specKeys = $products
                                ->flatMap(fn ($p) => array_keys($p->specs ?? []))
                                ->unique()
                                ->take(12);
                        @endphp
                        @foreach($specKeys as $key)
                            <tr>
                                <th scope="row">{{ is_string($key) ? $key : '' }}</th>
                                @foreach($products as $product)
                                    @php $val = $product->specs[$key] ?? null; @endphp
                                    <td>{{ is_string($val) || is_numeric($val) ? $val : (is_array($val) ? ($val['value'] ?? '—') : '—') }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row">{{ __('shop.actions') }}</th>
                            @foreach($products as $product)
                                <td>
                                    @if($product->stock > 0)
                                        <button type="button" data-add-cart="{{ $product->slug }}" class="btn-primary text-sm py-2 px-4">{{ __('shop.add_to_cart') }}</button>
                                    @else
                                        <a href="{{ route('products.show', $product) }}" class="shop-link-inline">{{ __('shop.view_product') }}</a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
