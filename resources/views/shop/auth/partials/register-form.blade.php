@props([
    'prefix' => 'register',
    'showLoginLink' => true,
    'modalSwitch' => false,
])

<form method="post" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="auth_mode" value="register">

    <div>
        <label for="{{ $prefix }}-name" class="shop-label">{{ __('shop.full_name') }}</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name') }}" required class="shop-input mt-1" autocomplete="name">
        @error('name')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-email" class="shop-label">E-posta</label>
        <input id="{{ $prefix }}-email" type="email" name="email" value="{{ old('email') }}" required class="shop-input mt-1" autocomplete="email">
        @error('email')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-password" class="shop-label">{{ __('shop.password') }}</label>
        <input id="{{ $prefix }}-password" type="password" name="password" required minlength="8" class="shop-input mt-1" autocomplete="new-password">
        @error('password')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-password-confirmation" class="shop-label">{{ __('shop.password_confirm') }}</label>
        <input id="{{ $prefix }}-password-confirmation" type="password" name="password_confirmation" required class="shop-input mt-1" autocomplete="new-password">
    </div>

    <button type="submit" class="btn-primary w-full py-3">{{ __('shop.register') }}</button>
</form>

@if($showLoginLink)
    <p class="mt-6 text-center text-sm text-slate-500">
        {{ __('shop.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800" @if($modalSwitch) data-auth-switch="login" @endif>{{ __('shop.login') }}</a>
    </p>
@endif
