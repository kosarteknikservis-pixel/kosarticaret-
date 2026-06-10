@extends('layouts.shop')
@section('title', 'Blog')

@section('content')
    <div class="shop-page">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => __('shop.home'), 'url' => route('home')],
            ['name' => 'Blog'],
        ]])

        <x-shop.page-hero title="Blog" :subtitle="__('shop.blog_subtitle')" />

        <div class="shop-blog-grid shop-reveal-group">
            @foreach($posts as $post)
                <article class="shop-blog-card">
                    <a href="{{ route('blog.show', $post) }}"
                       class="shop-blog-card__media{{ $post->imageUrl() ? '' : ' shop-blog-card__media--placeholder' }}"
                       aria-hidden="true"
                       tabindex="-1">
                        @if($post->imageUrl())
                            <img src="{{ $post->imageUrl('blog-card') }}" @if($srcset = $post->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 24rem" @endif alt="{{ $post->image_alt ?: $post->title }}" loading="lazy" decoding="async" width="960" height="540">
                        @else
                            <span class="shop-blog-card__media-ph" aria-hidden="true">
                                <x-shop.icon name="grid" class="w-8 h-8" />
                            </span>
                        @endif
                    </a>
                    <div class="shop-blog-card__body">
                        <time class="shop-blog-card__date">{{ $post->published_at?->format('d.m.Y') }}</time>
                        <h2 class="shop-blog-card__title">
                            <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                        </h2>
                        <p class="shop-blog-card__excerpt">{{ $post->excerpt ?: ' ' }}</p>
                        <a href="{{ route('blog.show', $post) }}" class="shop-blog-card__read">
                            {{ __('shop.read_more') }}
                            <x-shop.icon name="chevron-right" class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8 shop-pagination shop-reveal">{{ $posts->links('vendor.pagination.shop') }}</div>
    </div>
@endsection
