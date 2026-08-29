@extends('layouts.shop')

@section('content')
    <x-shop.catalog-layout
        :title="__('shop.category_page_h1', ['name' => $category->name])"
        :subtitle="$heroSubtitle ?? null"
        :intro="$category->description"
        :buying-guide="$buyingGuide ?? $category->buying_guide"
        :faq="$faq ?? []"
        :breadcrumbs="$breadcrumbs"
        :products="$products"
        :brands="$brands"
        :trust-points="$trustPoints ?? []"
        :subcategories="$subcategories ?? collect()"
        :related-categories="$siblingCategories ?? collect()"
        :related-categories-label="__('shop.sibling_categories')"
        :hub-categories="$hubCategories ?? collect()"
        :hub-categories-label="__('shop.cross_sell_categories')"
        :related-guides="$relatedGuides ?? collect()"
    />
@endsection
