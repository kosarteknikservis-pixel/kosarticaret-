<div class="shop-compare-bar" data-compare-bar hidden aria-live="polite">
    <div class="shop-compare-bar__inner">
        <p class="shop-compare-bar__text">
            <span data-compare-count>0</span> {{ __('shop.compare_bar_suffix') }}
        </p>
        <div class="shop-compare-bar__actions">
            <a href="{{ route('compare.index') }}" class="shop-compare-bar__btn shop-compare-bar__btn--primary">{{ __('shop.compare_view') }}</a>
            <button type="button" class="shop-compare-bar__btn shop-compare-bar__btn--ghost" data-compare-clear>{{ __('shop.compare_clear') }}</button>
        </div>
    </div>
</div>
