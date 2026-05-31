@extends('layouts.shop')

@section('content')
    <x-shop.catalog-layout
        :title="$category->name"
        :intro="$category->description"
        :faq="$category->faq ?? []"
        :breadcrumbs="$breadcrumbs"
        :products="$products"
        :brands="$brands"
    />
@endsection
