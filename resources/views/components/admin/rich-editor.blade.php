@props([
    'name',
    'label',
    'value' => '',
    'rows' => 10,
    'hint' => 'Düz metin: paragraflar arası boş satır. HTML: h2, p, ul, li, strong, a etiketleri.',
    'minHeight' => '12rem',
    'aiField' => null,
])

@php
    $isHtml = \App\Support\RichContent::isHtml($value);
    $mode = old($name.'_editor_mode', $isHtml ? 'html' : 'plain');
@endphp

<div class="admin-rich-editor" data-rich-editor data-min-height="{{ $minHeight }}">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <label class="admin-label mb-0">{{ $label }}</label>
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.ai-btn :field="$aiField ?? $name" :label="'AI: '.$label" variant="secondary" />
            <div class="admin-rich-editor__modes" role="tablist">
            <button type="button" class="admin-rich-editor__mode {{ $mode === 'plain' ? 'is-active' : '' }}" data-rich-mode="plain" role="tab">Düz metin</button>
            <button type="button" class="admin-rich-editor__mode {{ $mode === 'html' ? 'is-active' : '' }}" data-rich-mode="html" role="tab">HTML</button>
            </div>
        </div>
    </div>
    <p class="text-xs text-slate-500 mb-2">{{ $hint }}</p>

    <div class="admin-rich-editor__toolbar flex flex-wrap gap-1 mb-2" data-rich-toolbar>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="h2" title="Alt başlık H2">H2</button>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="h3" title="Alt başlık H3">H3</button>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="p" title="Paragraf">P</button>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="ul" title="Liste">Liste</button>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="strong" title="Kalın">B</button>
        <button type="button" class="admin-rich-editor__tool" data-rich-action="link" title="Link">Link</button>
    </div>

    <textarea
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="admin-input admin-rich-editor__area font-mono text-sm"
        data-rich-textarea
        data-seo-score-field="description"
        placeholder="Ürün veya kategori açıklaması…">{{ old($name, $value) }}</textarea>

    <details class="admin-rich-editor__preview mt-2 rounded-lg border border-slate-200 bg-slate-50">
        <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-slate-700">Önizleme</summary>
        <div class="shop-rich-content px-4 py-3 border-t border-slate-200 text-sm" data-rich-preview></div>
    </details>
</div>
