@extends('layouts.admin')

@section('title', $category->exists ? 'Kategori düzenle' : 'Yeni kategori')



@section('content')

    <x-admin.page-header :title="$category->exists ? 'Kategori düzenle' : 'Yeni kategori'" />

    @php

        $seoScoreData = [

            'name' => old('name', $category->name),

            'slug' => old('slug', $category->slug),

            'meta_title' => old('meta_title', $category->meta_title),

            'meta_description' => old('meta_description', $category->meta_description),

            'description' => old('description', $category->description),

            'has_image' => (bool) $category->imageUrl(),

        ];

    @endphp

    <form method="post"

          action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"

          enctype="multipart/form-data"

          class="admin-form-with-seo lg:grid lg:grid-cols-[1fr_min(18rem,28%)] lg:gap-8 max-w-4xl"

          data-seo-has-image="{{ $category->imageUrl() ? '1' : '0' }}"

          data-ai-type="category" data-ai-entity="categories" data-ai-id="{{ $category->id }}">

        @csrf @if($category->exists) @method('PUT') @endif

        <div class="admin-card p-6 sm:p-8 space-y-4">

            <div><label class="admin-label">Ad</label><input name="name" value="{{ old('name', $category->name) }}" required class="admin-input" data-seo-score-field="name"></div>

            <x-admin.slug-field :slug="old('slug', $category->slug)" entity="categories" :entity-id="$category->id" />

            <div><label class="admin-label">Üst kategori</label>

                <select name="parent_id" class="admin-input"><option value="">— Kök —</option>

                    @foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id)==$p->id)>{{ $p->name }}</option>@endforeach

                </select>

            </div>

            <x-admin.rich-editor

                name="description"

                label="Kategori açıklaması"

                :value="old('description', $category->description)"

                hint="Kategori sayfasında H1 altında gösterilir. En az 120 kelime önerilir."

            />

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">

                <label class="admin-label">Kategori görseli (vitrin kartları)</label>

                <x-admin.image-spec key="category" />

                @if($category->imageUrl())

                    <img src="{{ $category->imageUrl() }}" alt="" class="h-28 w-full max-w-xs object-cover rounded-lg border border-slate-200">

                    <label class="admin-checkbox text-sm"><input type="checkbox" name="remove_image" value="1"> Görseli kaldır</label>

                @endif

                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">

            </div>

            <div><label class="admin-label">Sıra</label><input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="admin-input max-w-xs"></div>

            <div class="flex flex-wrap gap-4">

                <label class="admin-checkbox"><input type="checkbox" name="featured" value="1" @checked(old('featured', $category->featured))> Öne çıkan</label>

                <label class="admin-checkbox"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu', $category->show_in_menu ?? true))> Menüde göster</label>

                <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $category->active ?? true))> Aktif</label>

            </div>

            {{-- ── FAQ REPEATER ── --}}
            <h3 class="admin-section-title mt-2">Sık Sorulan Sorular (SSS)</h3>
            <p class="text-xs text-slate-500 -mt-1 mb-2">Kategori sayfasında görünür SSS bloğu ve Google FAQPage zengin sonuç şeması olarak kullanılır. Her soru/cevap çifti ayrı bir satır oluşturur.</p>

            <div id="faq-repeater" class="space-y-3">
                @php $faqItems = old('faq', $category->faq ?? []); @endphp
                @forelse($faqItems as $fi => $faq)
                <div class="faq-row rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Soru {{ $fi + 1 }}</span>
                        <button type="button" class="faq-remove text-xs text-red-500 hover:text-red-700 font-semibold">Kaldır</button>
                    </div>
                    <input
                        type="text"
                        name="faq[{{ $fi }}][q]"
                        value="{{ $faq['q'] ?? '' }}"
                        placeholder="Soru metni…"
                        class="admin-input"
                    >
                    <textarea
                        name="faq[{{ $fi }}][a]"
                        rows="3"
                        placeholder="Cevap metni… (HTML desteklenir: <strong>, <a>)"
                        class="admin-input"
                    >{{ $faq['a'] ?? '' }}</textarea>
                </div>
                @empty
                <p class="text-sm text-slate-400 italic" id="faq-empty-note">Henüz soru eklenmedi.</p>
                @endforelse
            </div>

            <button
                type="button"
                id="faq-add-btn"
                class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-900 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                Yeni Soru Ekle
            </button>

            <script>
            (function () {
                const repeater = document.getElementById('faq-repeater');
                const addBtn   = document.getElementById('faq-add-btn');
                if (!repeater || !addBtn) return;

                function makeRow(index) {
                    const div = document.createElement('div');
                    div.className = 'faq-row rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2';
                    div.innerHTML = `
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Soru ${index + 1}</span>
                            <button type="button" class="faq-remove text-xs text-red-500 hover:text-red-700 font-semibold">Kaldır</button>
                        </div>
                        <input type="text" name="faq[${index}][q]" placeholder="Soru metni…" class="admin-input">
                        <textarea name="faq[${index}][a]" rows="3" placeholder="Cevap metni…" class="admin-input"></textarea>
                    `;
                    return div;
                }

                function reindex() {
                    repeater.querySelectorAll('.faq-row').forEach(function (row, i) {
                        row.querySelector('input[type=text]').name = `faq[${i}][q]`;
                        row.querySelector('textarea').name = `faq[${i}][a]`;
                        const label = row.querySelector('span');
                        if (label) label.textContent = `Soru ${i + 1}`;
                    });
                    const note = document.getElementById('faq-empty-note');
                    if (note) note.remove();
                }

                addBtn.addEventListener('click', function () {
                    const count = repeater.querySelectorAll('.faq-row').length;
                    repeater.appendChild(makeRow(count));
                    reindex();
                });

                repeater.addEventListener('click', function (e) {
                    const btn = e.target.closest('.faq-remove');
                    if (!btn) return;
                    btn.closest('.faq-row').remove();
                    reindex();
                });
            })();
            </script>

            <h3 class="admin-section-title">SEO</h3>

            <x-admin.seo-fields :meta-title="old('meta_title', $category->meta_title)" :meta-description="old('meta_description', $category->meta_description)" />

            <x-admin.form-footer :delete-action="$category->exists ? route('admin.categories.destroy', $category) : null" />

        </div>

        <div class="admin-form-with-seo__side mt-6 lg:mt-0">

            <x-admin.seo-score type="category" :data="$seoScoreData" />

        </div>

    </form>

@endsection


