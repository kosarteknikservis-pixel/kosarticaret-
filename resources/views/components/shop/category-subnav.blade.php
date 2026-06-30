@props(['categories' => collect()])

@if($categories->isNotEmpty())
<nav class="shop-category-subnav shop-reveal" aria-label="Alt kategoriler">
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
