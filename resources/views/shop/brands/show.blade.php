@extends('layouts.shop')

@section('content')
    <x-shop.catalog-layout
        :title="$brand->name"
        :intro="$brand->description"
        :faq="$brand->faq ?? []"
        :breadcrumbs="[
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => __('shop.brands'), 'url' => route('brands.index')],
            ['name' => $brand->name],
        ]"
        :products="$products"
        :brands="$brands"
    />
@endsection
