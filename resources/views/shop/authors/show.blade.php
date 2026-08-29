@extends('layouts.shop')

@section('content')
    <div class="shop-page max-w-3xl mx-auto">
        @include('shop.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

        <header class="shop-page-hero shop-reveal">
            <div class="shop-page-hero__inner">
                <div class="shop-page-hero__content">
                    <div class="shop-page-hero__eyebrow">{{ __('shop.blog_author_eyebrow') }}</div>
                    <h1 class="shop-page-hero__title">{{ $author['name'] }}</h1>
                    @if(filled($author['title']))
                        <p class="shop-page-hero__subtitle">{{ $author['title'] }}</p>
                    @endif
                </div>
            </div>
        </header>

        @if(filled($author['bio']))
            <section class="shop-panel shop-panel--prose mt-8 shop-reveal">
                <p class="text-slate-700 leading-relaxed">{{ $author['bio'] }}</p>
            </section>
        @endif

        @if(!empty($author['expertise']))
            <section class="mt-8 shop-reveal" aria-labelledby="author-expertise-heading">
                <h2 id="author-expertise-heading" class="text-lg font-semibold text-slate-900 mb-3">{{ __('shop.blog_author_expertise') }}</h2>
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach($author['expertise'] as $item)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <x-shop.icon name="check" class="w-4 h-4 text-brand-700 shrink-0 mt-0.5" />
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($posts->isNotEmpty())
            <section class="mt-12 pt-8 border-t border-slate-200 shop-reveal" aria-labelledby="author-posts-heading">
                <h2 id="author-posts-heading" class="text-lg font-semibold text-slate-900 mb-6">{{ __('shop.blog_author_recent_posts') }}</h2>
                <div class="shop-blog-grid shop-blog-grid--related">
                    @foreach($posts as $post)
                        <article class="shop-blog-card">
                            <div class="shop-blog-card__body">
                                <time class="shop-blog-card__date">{{ $post->published_at?->format('d.m.Y') }}</time>
                                <h3 class="shop-blog-card__title">
                                    <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                </h3>
                                <a href="{{ route('blog.show', $post) }}" class="shop-blog-card__read">
                                    {{ __('shop.read_more') }}
                                    <x-shop.icon name="chevron-right" class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
