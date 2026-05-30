@extends('layouts.shop')

@section('content')
    <div class="shop-page shop-page--article max-w-3xl">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
        @if($post->imageUrl())
            <figure class="mb-6 rounded-2xl overflow-hidden border border-slate-200">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt ?: $post->title }}" class="w-full max-h-[28rem] object-cover">
            </figure>
        @endif
        <x-shop.page-hero :title="$post->title">
            <x-slot:eyebrow>{{ $post->published_at?->format('d F Y') }}</x-slot:eyebrow>
            @if($post->tags)
                <x-slot:subtitle>{{ implode(' · ', $post->tags) }}</x-slot:subtitle>
            @endif
        </x-shop.page-hero>
        <x-shop.rich-content :content="$post->content" class="shop-panel shop-panel--prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-brand-700" />
    </div>
@endsection
