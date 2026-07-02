@php
    $reviews = $product->approvedReviews;
    $defaultRating = (int) old('rating', 5);
    $starHints = collect(range(1, 5))->mapWithKeys(
        fn (int $i) => [$i => __('shop.star_rating_hint', ['stars' => $i])]
    )->all();
@endphp

<div class="shop-pdp-reviews">
    @if($product->review_count > 0)
        <div class="shop-pdp-reviews__summary">
            @include('shop.partials.product-rating', ['rating' => $product->rating, 'count' => $product->review_count, 'size' => 'lg'])
            <p class="shop-pdp-reviews__summary-text">{{ __('shop.reviews_summary', ['count' => $product->review_count]) }}</p>
        </div>
    @endif

    <div class="shop-pdp-reviews__grid">
        <div class="shop-pdp-reviews__list">
            <h3 class="shop-pdp-reviews__heading">{{ __('shop.reviews_list_title') }}</h3>

            @forelse($reviews as $review)
                <article class="shop-pdp-review">
                    <header class="shop-pdp-review__head">
                        <div class="shop-pdp-review__author-wrap">
                            <span class="shop-pdp-review__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->author_name, 0, 1)) }}</span>
                            <div>
                                <p class="shop-pdp-review__author">{{ $review->author_name }}</p>
                                <time class="shop-pdp-review__date" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d.m.Y') }}</time>
                            </div>
                        </div>
                        <div class="shop-pdp-review__stars" aria-label="{{ $review->rating }}/5">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="shop-pdp-review__star {{ $i <= $review->rating ? 'is-filled' : '' }}" aria-hidden="true">★</span>
                            @endfor
                        </div>
                    </header>
                    @if($review->title)
                        <p class="shop-pdp-review__title">{{ $review->title }}</p>
                    @endif
                    <p class="shop-pdp-review__body">{{ $review->body }}</p>
                </article>
            @empty
                <div class="shop-pdp-reviews__empty">
                    <span class="shop-pdp-reviews__empty-icon">
                        <x-shop.icon name="star" class="w-8 h-8" />
                    </span>
                    <p class="shop-pdp-reviews__empty-title">{{ __('shop.no_reviews') }}</p>
                    <p class="shop-pdp-reviews__empty-hint">{{ __('shop.no_reviews_hint') }}</p>
                </div>
            @endforelse
        </div>

        <form method="post" action="{{ route('products.review', $product) }}" class="shop-panel shop-pdp-reviews__form space-y-4" id="pdp-review-form" data-recaptcha-form="review">
            @csrf
            <x-shop.spam-fields context="review" />
            <h3 class="shop-panel__title">{{ __('shop.write_review') }}</h3>
            <p class="shop-pdp-reviews__note">{{ __('shop.review_pending_note') }}</p>

            @if($errors->has('spam'))
                <div class="shop-pdp-reviews__errors" role="alert">
                    <p>{{ $errors->first('spam') }}</p>
                </div>
            @endif

            @if($errors->any() && ! $errors->has('spam'))
                <div class="shop-pdp-reviews__errors" role="alert">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="shop-label" for="review-author-name">{{ __('shop.name') }}</label>
                    <input id="review-author-name" name="author_name" value="{{ old('author_name', auth()->user()?->name) }}" required class="shop-input mt-1" autocomplete="name">
                </div>
                <div>
                    <label class="shop-label" for="review-email">E-posta</label>
                    <input id="review-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="shop-input mt-1" autocomplete="email">
                </div>
            </div>

            <div>
                <span class="shop-label">{{ __('shop.rating') }}</span>
                <div class="shop-star-rating mt-2" data-star-rating data-star-hints='@json($starHints)'>
                    <input type="hidden" name="rating" value="{{ $defaultRating }}" data-star-input required>
                    <div class="shop-star-rating__stars" role="radiogroup" aria-label="{{ __('shop.rating') }}">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    class="shop-star-rating__btn {{ $i <= $defaultRating ? 'is-active' : '' }}"
                                    data-star-value="{{ $i }}"
                                    role="radio"
                                    aria-checked="{{ $i === $defaultRating ? 'true' : 'false' }}"
                                    aria-label="{{ __('shop.star_rating_label', ['stars' => $i]) }}">
                                <x-shop.icon name="star" class="w-7 h-7" fill="currentColor" />
                            </button>
                        @endfor
                    </div>
                    <p class="shop-star-rating__hint" data-star-hint>{{ __('shop.star_rating_hint', ['stars' => $defaultRating]) }}</p>
                </div>
            </div>

            <div>
                <label class="shop-label" for="review-title">{{ __('shop.review_title') }}</label>
                <input id="review-title" name="title" value="{{ old('title') }}" class="shop-input mt-1">
            </div>

            <div>
                <label class="shop-label" for="review-body">{{ __('shop.review_body') }}</label>
                <textarea id="review-body" name="body" rows="5" required class="shop-input mt-1">{{ old('body') }}</textarea>
            </div>

            <x-shop.spam-recaptcha context="review" />
            <button type="submit" class="btn-primary w-full py-3 shop-btn-premium">{{ __('shop.submit_review') }}</button>
        </form>
    </div>
</div>
