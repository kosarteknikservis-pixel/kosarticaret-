<a href="{{ $category->storefrontUrl() }}" class="shop-category-card group rounded-2xl block {{ $category->imageUrl() ? 'shop-category-card--has-media' : 'p-6' }}">
    @if($category->imageUrl())
        <div class="shop-category-card__media">
            <img
                src="{{ $category->imageUrl('category-card') }}"
                @if($srcset = $category->imageSrcset()) srcset="{{ $srcset }}" sizes="(max-width: 639px) 100vw, 24rem" @endif
                alt="{{ $category->name }}"
                loading="lazy"
                decoding="async"
                width="480"
                height="270"
                class="shop-category-card__img">
        </div>
        <div class="shop-category-card__body p-6 pt-4">
            <p class="font-bold text-slate-900 group-hover:text-brand-700">{{ $category->name }}</p>
            @if($category->description)
                <p class="mt-1.5 text-sm text-slate-500 line-clamp-2 leading-relaxed">{{ \App\Support\RichContent::excerpt($category->description, 120) }}</p>
            @endif
        </div>
    @else
        <div class="shop-category-card__icon">
            <x-shop.icon name="grid" class="w-6 h-6" />
        </div>
        <p class="mt-4 font-bold text-slate-900 group-hover:text-brand-700">{{ $category->name }}</p>
        @if($category->description)
            <p class="mt-1.5 text-sm text-slate-500 line-clamp-2 leading-relaxed">{{ \App\Support\RichContent::excerpt($category->description, 120) }}</p>
        @endif
    @endif
</a>
