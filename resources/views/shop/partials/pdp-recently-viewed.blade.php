<section class="shop-recently-viewed shop-reveal" aria-labelledby="recently-viewed-heading"
         x-data="recentlyViewed({{ $currentProductId }})" x-show="items.length > 0" x-cloak>
    <div class="shop-recently-viewed__shell">
        <h2 id="recently-viewed-heading" class="shop-recently-viewed__title">{{ __('shop.recently_viewed') }}</h2>
        <div class="shop-recently-viewed__grid" x-ref="grid">
            <template x-for="item in items" :key="item.id">
                <a :href="item.url" class="shop-recently-viewed__card">
                    <div class="shop-recently-viewed__img-wrap">
                        <img :src="item.image" :alt="item.name" loading="lazy" decoding="async" width="240" height="240">
                    </div>
                    <p class="shop-recently-viewed__name" x-text="item.name"></p>
                    <p class="shop-recently-viewed__price" x-text="item.price"></p>
                </a>
            </template>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('recentlyViewed', (currentId) => ({
        items: [],
        init() {
            this.record(currentId);
            this.load(currentId);
        },
        record(id) {
            const product = {
                id: id,
                url: window.location.pathname,
                name: document.querySelector('h1')?.textContent?.trim() || '',
                image: document.querySelector('[data-pdp-main-image]')?.src
                    || document.querySelector('.shop-pdp-gallery img')?.src || '',
                price: document.querySelector('[data-pdp-price]')?.textContent?.trim()
                    || document.querySelector('.shop-pdp-price__current')?.textContent?.trim() || '',
                ts: Date.now()
            };
            let list = JSON.parse(localStorage.getItem('kosar_rv') || '[]');
            list = list.filter(i => i.id !== id);
            list.unshift(product);
            list = list.slice(0, 12);
            localStorage.setItem('kosar_rv', JSON.stringify(list));
        },
        load(excludeId) {
            let list = JSON.parse(localStorage.getItem('kosar_rv') || '[]');
            this.items = list.filter(i => i.id !== excludeId).slice(0, 6);
        }
    }));
});
</script>
@endpush
