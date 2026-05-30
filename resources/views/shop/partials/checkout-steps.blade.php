@props(['step' => 1])

@php
$steps = [
    1 => ['label' => __('shop.step_cart'), 'route' => route('cart.index')],
    2 => ['label' => __('shop.step_checkout'), 'route' => route('checkout.show')],
    3 => ['label' => __('shop.step_done'), 'route' => null],
];
@endphp

<nav aria-label="{{ __('shop.checkout_progress') }}" class="shop-checkout-steps shop-panel shop-checkout-steps--bar">
    <ol class="shop-checkout-steps__list">
        @foreach($steps as $num => $info)
            <li class="flex items-center gap-2 sm:gap-4">
                @if($num > 1)
                    <span class="shop-checkout-steps__connector {{ $step >= $num ? 'is-done' : '' }}" aria-hidden="true"></span>
                @endif
                <div class="flex items-center gap-2">
                    <span class="shop-checkout-steps__dot {{ $step > $num ? 'is-done' : ($step === $num ? 'is-current' : 'is-pending') }}">
                        @if($step > $num)
                            <x-shop.icon name="shield" class="w-4 h-4" />
                        @else
                            {{ $num }}
                        @endif
                    </span>
                    @if($info['route'] && $step > $num)
                        <a href="{{ $info['route'] }}" class="shop-checkout-steps__label is-done hidden sm:inline hover:underline">{{ $info['label'] }}</a>
                    @else
                        <span class="shop-checkout-steps__label hidden sm:inline {{ $step === $num ? 'is-current' : 'is-pending' }}">{{ $info['label'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
