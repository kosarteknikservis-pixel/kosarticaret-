@extends('layouts.shop')

@section('content')
    <x-shop.catalog-layout
        :title="$category->name"
        :subtitle="$heroSubtitle ?? null"
        :intro="$category->description"
        :buying-guide="$buyingGuide ?? $category->buying_guide"
        :faq="$category->faq ?? []"
        :breadcrumbs="$breadcrumbs"
        :products="$products"
        :brands="$brands"
        :trust-points="$trustPoints ?? []"
        :subcategories="$subcategories ?? collect()"
    />
@endsection
