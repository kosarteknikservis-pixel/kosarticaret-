@extends('layouts.shop')
@section('title', __('shop.all_products'))

@section('content')
    <x-shop.catalog-layout
        :title="__('shop.all_products')"
        :intro="config('kosar.description')"
        :products="$products"
        :brands="$brands"
    />
@endsection
