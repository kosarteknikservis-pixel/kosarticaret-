@props(['items' => [], 'title' => 'Sık Sorulan Sorular'])

@if(!empty($items))

@php
    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'FAQPage',
        'mainEntity' => array_map(fn($item) => [
            '@type' => 'Question',
            'name'  => $item['q'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($item['a'] ?? ''),
            ],
        ], $items),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp

<script type="application/ld+json">{!! $faqSchema !!}</script>

<section class="shop-faq shop-reveal" aria-label="{{ $title }}">
    <div class="shop-faq__header">
        <span class="shop-faq__badge">SSS</span>
        <h2 class="shop-faq__title">{{ $title }}</h2>
        <p class="shop-faq__sub">Müşterilerimizin en çok sorduğu soruların yanıtları</p>
    </div>

    <div class="shop-faq__list" itemscope itemtype="https://schema.org/FAQPage">
        @foreach($items as $index => $item)
        <div
            class="shop-faq__item"
            itemscope
            itemprop="mainEntity"
            itemtype="https://schema.org/Question"
        >
            <button
                type="button"
                class="shop-faq__q"
                aria-expanded="false"
                aria-controls="faq-answer-{{ $index }}"
                data-faq-trigger
            >
                <span class="shop-faq__q-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="shop-faq__q-text" itemprop="name">{{ $item['q'] ?? '' }}</span>
                <span class="shop-faq__chevron" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </button>
            <div
                class="shop-faq__a"
                id="faq-answer-{{ $index }}"
                role="region"
                aria-hidden="true"
                itemscope
                itemprop="acceptedAnswer"
                itemtype="https://schema.org/Answer"
            >
                <div class="shop-faq__a-inner" itemprop="text">
                    {!! $item['a'] ?? '' !!}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

@endif
