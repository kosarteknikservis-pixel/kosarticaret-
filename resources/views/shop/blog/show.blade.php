@extends('layouts.shop')

@section('content')
    {{-- Reading progress bar --}}
    <div class="shop-reading-progress" aria-hidden="true">
        <div class="shop-reading-progress__bar"></div>
    </div>

    <div class="shop-page shop-page--article max-w-3xl">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

        {{-- Hero image with animation --}}
        @if($post->imageUrl())
            <div class="shop-article-hero shop-reveal--scale">
                <img src="{{ $post->imageUrl('blog-card') }}" @if($srcset = $post->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 52rem" @endif alt="{{ $post->image_alt ?: $post->title }}" decoding="async" fetchpriority="high">
            </div>
        @endif

        {{-- Article header --}}
        <div class="shop-reveal">
            {{-- Meta row: date + reading time --}}
            <div class="shop-article-meta">
                <time class="shop-article-meta__date" datetime="{{ $post->published_at?->toDateString() }}">
                    {{ $post->published_at?->format('d F Y') }}
                </time>
                @if($post->tags)
                    <span class="shop-article-meta__dot" aria-hidden="true"></span>
                @endif
                <span class="shop-article-meta__reading" data-reading-time aria-live="polite">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    &hellip;
                </span>
            </div>

            {{-- Title --}}
            <h1 class="shop-page-hero__title mt-1">{{ $post->title }}</h1>

            {{-- Tags as chips --}}
            @if($post->tags)
                <div class="shop-article-tags">
                    @foreach($post->tags as $tag)
                        <span class="shop-article-tag">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Article content --}}
        <div class="mt-8 shop-reveal">
            <x-shop.rich-content :content="$post->content"
                class="shop-panel shop-panel--prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-brand-700" />
        </div>
    </div>
@endsection
