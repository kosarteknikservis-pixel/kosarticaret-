@props([
    'prefix' => 'login',
    'showRegisterLink' => true,
    'modalSwitch' => false,
])

<form method="post" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="auth_mode" value="login">

    <div>
        <label for="{{ $prefix }}-email" class="shop-label">E-posta</label>
        <input id="{{ $prefix }}-email" type="email" name="email" value="{{ old('email') }}" required class="shop-input mt-1" autocomplete="email">
        @error('email')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-password" class="shop-label">{{ __('shop.password') }}</label>
        <input id="{{ $prefix }}-password" type="password" name="password" required class="shop-input mt-1" autocomplete="current-password">
        @error('password')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remember" value="1" class="rounded text-brand-700 focus:ring-brand-500/30" @checked(old('remember'))>
        {{ __('shop.remember_me') }}
    </label>

    <button type="submit" class="btn-primary w-full py-3">{{ __('shop.login') }}</button>
</form>

@if($showRegisterLink)
    <p class="mt-6 text-center text-sm text-slate-500">
        {{ __('shop.no_account') }}
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800" @if($modalSwitch) data-auth-switch="register" @endif>{{ __('shop.register') }}</a>
    </p>
@endif
