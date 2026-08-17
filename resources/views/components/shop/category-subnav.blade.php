@props([
    'categories' => collect(),
    'label' => null,
    'heading' => false,
])

@if($categories->isNotEmpty())
<nav class="shop-category-subnav shop-reveal" aria-label="{{ $label ?: __('shop.subcategories') }}">
    @if($heading && filled($label))
        <p class="shop-category-subnav__heading">{{ $label }}</p>
    @endif
    <ul class="shop-category-subnav__list">
        @foreach($categories as $child)
            <li>
                <a href="{{ $child->storefrontUrl() }}" class="shop-category-subnav__link">
                    {{ $child->name }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@endif
