@extends('layouts.admin')
@section('title', 'Yeni e-posta şablonu')

@section('content')
    <x-admin.page-header title="Yeni e-posta şablonu" subtitle="Kampanya veya özel bilgilendirme mailleri için tekrar kullanılabilir şablon oluşturun" />

    <form method="post" action="{{ route('admin.email-templates.store') }}" class="admin-card p-6 sm:p-8 space-y-5 max-w-4xl">
        @csrf
        <div>
            <label class="admin-label">Şablon adı</label>
            <input name="name" value="{{ old('name') }}" required class="admin-input" placeholder="Örn. Yaz kampanyası duyurusu">
        </div>
        <div>
            <label class="admin-label">Mail konusu</label>
            <input name="subject" value="{{ old('subject') }}" required class="admin-input" placeholder="Örn. Yaz kampanyası başladı">
        </div>
        <div>
            <label class="admin-label">Ön başlık</label>
            <input name="preheader" value="{{ old('preheader') }}" class="admin-input" placeholder="Mail kutusunda konu altında görünecek kısa metin">
        </div>
        <div>
            <label class="admin-label">Başlık</label>
            <input name="title" value="{{ old('title') }}" required class="admin-input" placeholder="Örn. Endüstriyel ürünlerde özel fırsatlar">
        </div>
        <x-admin.rich-editor
            name="body"
            label="Mail metni"
            :value="old('body')"
            :rows="10"
            min-height="16rem"
            ai-field="email_body"
            hint="Ürün açıklamasındaki editörle aynı yapı. HTML modunda başlık, paragraf, liste, link, tablo ve görsel ekleyebilirsiniz."
        />
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="admin-label">Buton metni</label><input name="button_label" value="{{ old('button_label') }}" class="admin-input" placeholder="Detayları incele"></div>
            <div><label class="admin-label">Buton linki</label><input name="button_url" value="{{ old('button_url') }}" class="admin-input" placeholder="https://kosarticaret.com/..."></div>
        </div>
        <div>
            <label class="admin-label">Alt not</label>
            <textarea name="footer_note" rows="3" class="admin-input">{{ old('footer_note') }}</textarea>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <p class="font-semibold text-slate-900">Dinamik alanlar</p>
            <div class="flex flex-wrap gap-4">
                <label class="admin-checkbox"><input type="checkbox" name="show_items" value="1"> Ürün listesini göster</label>
                <label class="admin-checkbox"><input type="checkbox" name="show_tracking" value="1"> Takip bilgisini göster</label>
            </div>
            <p class="text-xs text-slate-500">Kampanya şablonlarında genelde bu iki alan kapalı kalır. Sipariş şablonlarında ürün/takip alanları işe yarar.</p>
        </div>
        <div class="admin-form-actions">
            <button class="admin-btn admin-btn-primary px-8 py-2.5">Şablon oluştur</button>
            <a href="{{ route('admin.email-templates.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Listeye dön</a>
        </div>
    </form>
@endsection
