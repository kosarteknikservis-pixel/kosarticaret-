@php
    $whatsapp = preg_replace('/\D/', '', \App\Models\SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp')));
    $phone = \App\Models\SiteSetting::get('contact_phone', config('kosar.contact.phone'));
@endphp

<div class="shop-home-prefooter">

    @if($recentBlogPosts->isNotEmpty())
        <section class="shop-home-blog shop-reveal" aria-labelledby="home-blog-heading">
            <div class="shop-home-blog__shell">
                <div class="shop-home-blog__head">
                    <div>
                        <p class="shop-home-blog__eyebrow">Blog</p>
                        <h2 id="home-blog-heading" class="shop-home-blog__title">{{ __('shop.home_blog_title') }}</h2>
                        <p class="shop-home-blog__sub">{{ __('shop.home_blog_sub') }}</p>
                    </div>
                    <a href="{{ route('blog.index') }}" class="shop-home-blog__all">
                        {{ __('shop.home_blog_all') }}
                        <x-shop.icon name="chevron-right" class="w-4 h-4" />
                    </a>
                </div>
                <div class="shop-home-blog__viewport">
                    <div class="shop-home-blog__track shop-reveal-group" role="list">
                    @foreach($recentBlogPosts as $post)
                        <div class="shop-home-blog__slide" role="listitem">
                        <article class="shop-home-blog-card">
                            <a href="{{ route('blog.show', $post) }}"
                               class="shop-home-blog-card__media{{ $post->imageUrl() ? '' : ' shop-home-blog-card__media--placeholder' }}"
                               tabindex="-1"
                               aria-hidden="true">
                                @if($post->imageUrl())
                                    <img
                                        src="{{ $post->imageUrl('blog-card') }}"
                                        @if($srcset = $post->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 767px) 72vw, 20rem" @endif
                                        alt="{{ $post->image_alt ?: $post->title }}"
                                        loading="lazy"
                                        decoding="async"
                                        width="960"
                                        height="540">
                                @else
                                    <span class="shop-home-blog-card__media-ph" aria-hidden="true">
                                        <x-shop.icon name="grid" class="w-8 h-8" />
                                    </span>
                                @endif
                            </a>
                            <div class="shop-home-blog-card__body">
                                @if($post->published_at)
                                    <time class="shop-home-blog-card__date" datetime="{{ $post->published_at->toDateString() }}">
                                        {{ $post->published_at->format('d.m.Y') }}
                                    </time>
                                @endif
                                <h3 class="shop-home-blog-card__title">
                                    <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                </h3>
                                <p class="shop-home-blog-card__excerpt">{{ $post->excerpt ?: ' ' }}</p>
                                <a href="{{ route('blog.show', $post) }}" class="shop-home-blog-card__read">
                                    {{ __('shop.read_more') }}
                                    <x-shop.icon name="chevron-right" class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </article>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="shop-home-support shop-reveal" aria-labelledby="home-support-heading">
        <div class="shop-home-support__inner">
            <div class="shop-home-support__copy">
                <p class="shop-home-support__eyebrow">{{ __('shop.home_support_eyebrow') }}</p>
                <h2 id="home-support-heading" class="shop-home-support__title">{{ __('shop.home_support_title') }}</h2>
                <p class="shop-home-support__desc">{{ __('shop.home_support_desc') }}</p>
            </div>
            <div class="shop-home-support__actions">
                <a href="{{ route('contact.show') }}" class="shop-home-support__btn shop-home-support__btn--primary">
                    {{ __('shop.footer_connect_cta') }}
                </a>
                @if(filled($whatsapp))
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="shop-home-support__btn shop-home-support__btn--ghost">
                        WhatsApp
                    </a>
                @elseif(filled($phone))
                    <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="shop-home-support__btn shop-home-support__btn--ghost">
                        {{ $phone }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="shop-home-trust-strip shop-reveal" aria-labelledby="trust-strip-heading">
        <div class="shop-home-trust-strip__shell">
            <p id="trust-strip-heading" class="shop-home-trust-strip__eyebrow">{{ __('shop.home_trust_strip_label') }}</p>
            <div class="shop-home-trust-strip__grid">
                <div class="shop-home-trust-strip__card">
                    <span class="shop-home-trust-strip__icon" aria-hidden="true">
                        <x-shop.icon name="shield" class="w-6 h-6" />
                    </span>
                    <p class="shop-home-trust-strip__title">{{ __('shop.home_trust_secure') }}</p>
                    <p class="shop-home-trust-strip__desc">{{ __('shop.home_trust_secure_desc') }}</p>
                </div>
                <div class="shop-home-trust-strip__card">
                    <span class="shop-home-trust-strip__icon" aria-hidden="true">
                        <x-shop.icon name="truck" class="w-6 h-6" />
                    </span>
                    <p class="shop-home-trust-strip__title">{{ __('shop.home_trust_shipping') }}</p>
                    <p class="shop-home-trust-strip__desc">{{ __('shop.home_trust_shipping_desc') }}</p>
                </div>
                <div class="shop-home-trust-strip__card">
                    <span class="shop-home-trust-strip__icon" aria-hidden="true">
                        <x-shop.icon name="phone" class="w-6 h-6" />
                    </span>
                    <p class="shop-home-trust-strip__title">{{ __('shop.home_trust_support') }}</p>
                    <p class="shop-home-trust-strip__desc">{{ __('shop.home_trust_support_desc') }}</p>
                </div>
                <div class="shop-home-trust-strip__card">
                    <span class="shop-home-trust-strip__icon" aria-hidden="true">
                        <x-shop.icon name="star" class="w-6 h-6" />
                    </span>
                    <p class="shop-home-trust-strip__title">{{ __('shop.home_trust_quality') }}</p>
                    <p class="shop-home-trust-strip__desc">{{ __('shop.home_trust_quality_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

</div>
