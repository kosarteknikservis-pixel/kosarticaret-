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
                    @if($post->imageUrl())
                        <a href="{{ route('blog.show', $post) }}" class="shop-blog-card__media block aspect-[16/9] rounded-xl mb-0" aria-hidden="true" tabindex="-1">
                            <img src="{{ $post->imageUrl('blog-card') }}" @if($srcset = $post->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 24rem" @endif alt="{{ $post->image_alt ?: $post->title }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </a>
                    @endif
                    <div class="shop-blog-card__body">
                        <time class="shop-blog-card__date">{{ $post->published_at?->format('d.m.Y') }}</time>
                        <h2 class="shop-blog-card__title mt-2">
                            <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                        </h2>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed line-clamp-3 flex-1">{{ $post->excerpt }}</p>
                        <a href="{{ route('blog.show', $post) }}" class="shop-link-inline mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800">
                            {{ __('shop.read_more') }}
                            <x-shop.icon name="chevron-right" class="w-4 h-4" />
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8 shop-pagination shop-reveal">{{ $posts->links('vendor.pagination.shop') }}</div>
    </div>
@endsection
