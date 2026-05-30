@props([
    'type' => 'product',
    'data' => [],
])

@php
    $result = \App\Support\SeoScore::analyze($type, $data);
@endphp

<div
    class="admin-seo-score admin-seo-score--{{ $result['grade_class'] }}"
    data-seo-score
    data-seo-type="{{ $type }}"
    data-seo-initial="{{ json_encode($result, JSON_UNESCAPED_UNICODE) }}"
>
    <div class="admin-seo-score__head">
        <div class="admin-seo-score__ring" data-seo-score-value>{{ $result['score'] }}</div>
        <div>
            <p class="admin-seo-score__title">SEO skoru</p>
            <p class="admin-seo-score__grade" data-seo-score-grade>{{ $result['grade'] }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Kaydetmeden önce alanları doldurdukça güncellenir.</p>
        </div>
    </div>
    <ul class="admin-seo-score__checks" data-seo-score-checks>
        @foreach($result['checks'] as $check)
            <li class="admin-seo-score__check admin-seo-score__check--{{ $check['status'] }}" data-check-id="{{ $check['id'] }}">
                <span class="admin-seo-score__check-icon" aria-hidden="true"></span>
                <span class="min-w-0">
                    <span class="admin-seo-score__check-label">{{ $check['label'] }}</span>
                    <span class="admin-seo-score__check-msg">{{ $check['message'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</div>
