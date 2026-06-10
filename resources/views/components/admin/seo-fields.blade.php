@props([
    'metaTitle' => '',
    'metaDescription' => '',
    'hint' => 'Boş bırakırsanız vitrin sayfa başlığı ve açıklamadan otomatik üretilir.',
])

<div class="admin-seo-fields space-y-4" data-admin-seo-fields>
    <div class="flex flex-wrap gap-2 items-center">
        <button type="button" class="admin-ai-btn admin-ai-btn--primary" data-meta-suggest data-meta-use-ai="0">Meta öner</button>
        <button type="button" class="admin-ai-btn admin-ai-btn--secondary" data-meta-suggest data-meta-use-ai="1"
                @disabled(!\App\Services\OpenAiService::isConfigured()) title="OpenAI API anahtarı gerekir">OpenAI meta</button>
        <span class="text-xs text-slate-500">{{ $hint }}</span>
    </div>
    <div>
        <label class="admin-label flex justify-between gap-2">
            <span>SEO başlık</span>
            <span class="font-normal text-slate-400" data-seo-count="title">0 / 60</span>
        </label>
        <input type="text" name="meta_title" value="{{ old('meta_title', $metaTitle) }}" maxlength="70"
               class="admin-input" data-seo-field="title" placeholder="Google sonuç başlığı">
        <p class="text-xs text-slate-500 mt-1">İdeal: 50–60 karakter. Site adı ({{ \App\Support\SiteName::get() }}) vitrinde otomatik eklenir; buraya yalnızca ürün başlığını yazın.</p>
    </div>
    <div>
        <label class="admin-label flex justify-between gap-2">
            <span>SEO açıklama</span>
            <span class="font-normal text-slate-400" data-seo-count="description">0 / 160</span>
        </label>
        <textarea name="meta_description" rows="3" maxlength="320" class="admin-input"
                  data-seo-field="description" data-seo-score-field="meta_description" placeholder="Arama sonucu özeti">{{ old('meta_description', $metaDescription) }}</textarea>
        <p class="text-xs text-slate-500 mt-1">İdeal: 140–160 karakter; anahtar kelimeyi doğal cümlede kullanın.</p>
    </div>
    {{ $slot }}
</div>
