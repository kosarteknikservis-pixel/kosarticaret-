<div class="shop-cart-quote mt-8 pt-6 border-t border-slate-200">
    <h3 class="text-sm font-bold text-slate-900">{{ __('shop.quote_request') }}</h3>
    <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ __('shop.quote_request_sub') }}</p>

    <form method="post" action="{{ route('cart.quote') }}" class="mt-4 space-y-3">
        @csrf
        <x-shop.spam-fields context="quote" />
        @if($errors->has('spam'))
            <p class="text-sm text-red-600">{{ $errors->first('spam') }}</p>
        @endif
        <div>
            <label class="shop-label" for="quote-name">{{ __('shop.full_name') }}</label>
            <input id="quote-name" name="name" type="text" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}" class="shop-input">
        </div>
        <div>
            <label class="shop-label" for="quote-email">E-posta</label>
            <input id="quote-email" name="email" type="email" required maxlength="190" value="{{ old('email', auth()->user()?->email) }}" class="shop-input">
        </div>
        <div>
            <label class="shop-label" for="quote-phone">{{ __('shop.phone') }}</label>
            <input id="quote-phone" name="phone" type="tel" maxlength="40" value="{{ old('phone') }}" class="shop-input">
        </div>
        <div>
            <label class="shop-label" for="quote-company">{{ __('shop.quote_company') }}</label>
            <input id="quote-company" name="company" type="text" maxlength="190" value="{{ old('company') }}" class="shop-input">
        </div>
        <div>
            <label class="shop-label" for="quote-tax">{{ __('shop.quote_tax_no') }}</label>
            <input id="quote-tax" name="tax_no" type="text" maxlength="32" value="{{ old('tax_no') }}" class="shop-input">
        </div>
        <div>
            <label class="shop-label" for="quote-note">{{ __('shop.quote_note') }}</label>
            <textarea id="quote-note" name="note" rows="3" maxlength="5000" class="shop-input resize-y min-h-[5rem]">{{ old('note') }}</textarea>
        </div>
        <button type="submit" class="btn-outline w-full py-3 text-center">{{ __('shop.quote_submit') }}</button>
    </form>
</div>
