@props(['items' => [], 'title' => 'Sık Sorulan Sorular'])

@php
    $validItems = array_values(array_filter($items, function (array $item): bool {
        $question = trim(strip_tags((string) ($item['q'] ?? '')));
        $answer = trim(strip_tags((string) ($item['a'] ?? '')));

        return $question !== '' && $answer !== '';
    }));
@endphp

@if(!empty($validItems))
@php
    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static function (array $item): array {
            $question = trim(strip_tags((string) ($item['q'] ?? '')));
            $answer = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($item['a'] ?? ''))));

            return [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }, $validItems),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<script type="application/ld+json">{!! $faqSchema !!}</script>

<section class="shop-faq shop-reveal" aria-label="{{ $title }}">
    <div class="shop-faq__header">
        <span class="shop-faq__badge">SSS</span>
        <h2 class="shop-faq__title">{{ $title }}</h2>
        <p class="shop-faq__sub">Müşterilerimizin en çok sorduğu soruların yanıtları</p>
    </div>

    <div class="shop-faq__list">
        @foreach($validItems as $index => $item)
            <article class="shop-faq__item">
                <h3 class="shop-faq__q">
                    <span class="shop-faq__q-num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="shop-faq__q-text">{{ $item['q'] ?? '' }}</span>
                </h3>
                <div class="shop-faq__a">
                    <div class="shop-faq__a-inner">
                        {!! $item['a'] ?? '' !!}
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>

@endif
