@extends('layouts.shop')

@section('content')
    {{-- Reading progress bar --}}
    <div class="shop-reading-progress" aria-hidden="true">
        <div class="shop-reading-progress__bar"></div>
    </div>

    <div class="shop-page shop-page--article shop-article-layout max-w-3xl mx-auto">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

        {{-- Hero image with animation --}}
        @if($post->imageUrl())
            <div class="shop-article-hero shop-reveal--scale">
                <img src="{{ $post->imageUrl('blog-card') }}" @if($srcset = $post->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 100vw, 52rem" @endif alt="{{ $post->image_alt ?: $post->title }}" width="960" height="540" decoding="async" fetchpriority="high">
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

            @if(!empty($author))
                <p class="shop-article-byline mt-3 text-sm text-slate-600">
                    {{ __('shop.blog_author_byline') }}
                    <a href="{{ $author['url'] }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ $author['name'] }}</a>
                    @if(filled($author['title']))
                        <span class="text-slate-500"> · {{ $author['title'] }}</span>
                    @endif
                </p>
            @endif

            {{-- Tags as chips --}}
            @if($post->tags)
                <div class="shop-article-tags">
                    @foreach($post->tags as $tag)
                        <a href="{{ \App\Support\InternalLinking::tagUrl($tag) }}" class="shop-article-tag">{{ $tag }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($geo))
            <div class="mt-6 shop-reveal">
                <x-shop.geo-block :geo="$geo" />
            </div>
        @endif

        {{-- Article content --}}
        <div class="mt-8 shop-reveal">
            <x-shop.rich-content :content="$post->content"
                class="shop-panel shop-panel--prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-a:text-brand-700" />
        </div>
    </div>

    @if(($relatedPosts ?? collect())->isNotEmpty())
        <section class="shop-related-section shop-related-section--wide shop-related-posts mt-14 pt-10 border-t border-slate-200 shop-reveal" aria-labelledby="related-posts-heading">
            <h2 id="related-posts-heading" class="shop-related-posts__title">{{ __('shop.related_posts') }}</h2>
            <div class="shop-blog-grid shop-blog-grid--related">
                @foreach($relatedPosts as $related)
                    <article class="shop-blog-card">
                        <div class="shop-blog-card__body">
                            <time class="shop-blog-card__date">{{ $related->published_at?->format('d.m.Y') }}</time>
                            <h3 class="shop-blog-card__title">
                                <a href="{{ route('blog.show', $related) }}">{{ $related->title }}</a>
                            </h3>
                            <a href="{{ route('blog.show', $related) }}" class="shop-blog-card__read">
                                {{ __('shop.read_more') }}
                                <x-shop.icon name="chevron-right" class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($suggestedProducts->isNotEmpty())
        <section class="shop-related-section shop-related-section--wide mt-14 pt-10 border-t border-slate-200 shop-reveal" aria-labelledby="blog-products-heading">
            @include('shop.partials.product-carousel', [
                'products' => $suggestedProducts,
                'title' => __('shop.blog_suggested_products'),
                'headingId' => 'blog-products-heading',
            ])
        </section>
    @endif
@endsection
