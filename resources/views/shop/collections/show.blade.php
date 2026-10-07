@extends('layouts.shop')

@section('content')
    @if(!empty($preview))
        <p class="shop-collection-draft" role="status">Taslak önizleme. Bu adres yayında değil ve site haritasında yok.</p>
    @endif

    <x-shop.catalog-layout
        :title="$collection->name"
        :subtitle="$sentence"
        :faq="$faq"
        :breadcrumbs="$breadcrumbs"
        :products="$products"
        :brands="$brands"
        :related-categories="$relatedCategories"
        related-categories-label="İlgili kategori"
    />
@endsection
