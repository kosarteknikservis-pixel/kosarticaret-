@props([
    'slug' => '',
    'source' => 'name',
    'entity' => 'products',
    'entityId' => null,
    'label' => 'Kalıcı bağlantı (slug)',
])

<div class="admin-slug-field" data-slug-field data-slug-entity="{{ $entity }}" data-slug-exclude-id="{{ $entityId }}" data-slug-source="{{ $source }}">
    <label class="admin-label flex flex-wrap items-center justify-between gap-2">
        <span>{{ $label }}</span>
        <button type="button" class="admin-ai-btn admin-ai-btn--ghost text-xs" data-slug-refresh title="Slug yeniden üret">↻ Otomatik</button>
    </label>
    <input
        type="text"
        name="slug"
        value="{{ old('slug', $slug) }}"
        class="admin-input font-mono text-sm"
        data-slug-input
        data-seo-score-field="slug"
        placeholder="otomatik-uretilir"
        autocomplete="off"
    >
    <p class="text-xs text-slate-500 mt-1">Ad veya başlık yazdıkça otomatik oluşur. Elle düzenlerseniz otomatik güncelleme durur.</p>
</div>
