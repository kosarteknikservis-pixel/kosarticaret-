@extends('layouts.shop')

@section('content')
    <x-shop.catalog-layout
        :title="$search ? __('shop.search_results', ['q' => $search]) : __('shop.search_label')"
        :breadcrumbs="[
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => $search ? __('shop.search_results', ['q' => $search]) : __('shop.search_label')],
        ]"
        :products="$products"
        :brands="$brands"
    />
@endsection
