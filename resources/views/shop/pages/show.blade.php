@extends('layouts.shop')

@section('content')
    <div class="shop-page shop-page--article max-w-3xl">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
        <x-shop.page-hero :title="$page->title" />
        <div class="shop-panel shop-panel--prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-brand-700">
            <x-shop.rich-content :content="$page->content" />
        </div>
    </div>
@endsection
