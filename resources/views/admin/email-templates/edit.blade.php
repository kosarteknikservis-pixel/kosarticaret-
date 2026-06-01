@extends('layouts.admin')
@section('title', 'E-posta şablonu')

@section('content')
    <x-admin.page-header :title="$template->name" subtitle="Mail konusu, metni ve buton alanlarını düzenleyin" />

    <form method="post" action="{{ route('admin.email-templates.update', $template) }}" class="admin-card p-6 sm:p-8 space-y-5 max-w-4xl">
        @csrf @method('PUT')
        <div>
            <label class="admin-label">Mail konusu</label>
            <input name="subject" value="{{ old('subject', $template->subject) }}" required class="admin-input">
        </div>
        <div>
            <label class="admin-label">Ön başlık</label>
            <input name="preheader" value="{{ old('preheader', $template->preheader) }}" class="admin-input">
        </div>
        <div>
            <label class="admin-label">Başlık</label>
            <input name="title" value="{{ old('title', $template->title) }}" required class="admin-input">
        </div>
        <x-admin.rich-editor
            name="body"
            label="Mail metni"
            :value="old('body', $template->body)"
            :rows="10"
            min-height="16rem"
            ai-field="email_body"
            hint="Ürün açıklamasındaki gibi kullanılır. HTML modunda h2, p, ul, li, strong, a, table, img ve güvenli inline style kullanabilirsiniz."
        />
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="admin-label">Buton metni</label><input name="button_label" value="{{ old('button_label', $template->button_label) }}" class="admin-input"></div>
            <div><label class="admin-label">Buton linki</label><input name="button_url" value="{{ old('button_url', $template->button_url) }}" class="admin-input"></div>
        </div>
        <div>
            <label class="admin-label">Alt not</label>
            <textarea name="footer_note" rows="3" class="admin-input">{{ old('footer_note', $template->footer_note) }}</textarea>
        </div>
        @php $settings = $template->settings ?? []; @endphp
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <p class="font-semibold text-slate-900">Dinamik alanlar</p>
            <div class="flex flex-wrap gap-4">
                <label class="admin-checkbox"><input type="checkbox" name="show_items" value="1" @checked($settings['show_items'] ?? false)> Ürün listesini göster</label>
                <label class="admin-checkbox"><input type="checkbox" name="show_tracking" value="1" @checked($settings['show_tracking'] ?? false)> Takip bilgisini göster</label>
            </div>
            <p class="text-xs text-slate-500">Kullanılabilir değişkenler: <code>@{{order_number}}</code>, <code>@{{customer_name}}</code>, <code>@{{status}}</code>, <code>@{{total}}</code>, <code>@{{tracking_number}}</code>, <code>@{{tracking_url}}</code>, <code>@{{site_name}}</code></p>
        </div>
        <label class="admin-checkbox"><input type="checkbox" name="active" value="1" @checked(old('active', $template->active))> Şablon aktif</label>
        <div class="admin-form-actions">
            <button class="admin-btn admin-btn-primary px-8 py-2.5">Kaydet</button>
            <a href="{{ route('admin.email-templates.preview', $template) }}" target="_blank" class="admin-btn admin-btn-secondary px-5 py-2.5">Önizle</a>
            <a href="{{ route('admin.email-templates.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Listeye dön</a>
        </div>
    </form>
@endsection
