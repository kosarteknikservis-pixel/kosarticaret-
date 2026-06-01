@extends('layouts.admin')
@section('title', 'Kampanya düzenle')

@section('content')
    <x-admin.page-header :title="'Kampanya düzenle: '.$campaign->title" subtitle="Taslak kampanyayı test etmeden veya göndermeden önce düzenleyin">
        <x-slot:actions>
            <a href="{{ route('admin.newsletter.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Bültene dön</a>
            <a href="{{ route('admin.newsletter.campaigns.preview', $campaign) }}" target="_blank" class="admin-btn admin-btn-secondary px-5 py-2.5">Önizle</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if($campaign->status === 'sent')
        <div class="admin-card p-5 mb-6 border-amber-200 bg-amber-50 text-amber-800">
            Bu kampanya gönderilmiş olduğu için düzenlenemez. İçeriği tekrar kullanmak için yeni kampanya oluşturun.
        </div>
    @endif

    <form method="post" action="{{ route('admin.newsletter.campaigns.update', $campaign) }}" class="admin-card p-6 sm:p-8 space-y-5 max-w-4xl">
        @csrf @method('PUT')
        <div>
            <label class="admin-label">Şablondan yeniden doldur</label>
            <select class="admin-input" data-campaign-template-select @disabled($campaign->status === 'sent')>
                <option value="">Mevcut içeriği koru</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }} {{ str_starts_with($template->key, 'custom_') ? '(Özel)' : '(Sistem)' }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="admin-label">Kampanya başlığı</label><input name="title" value="{{ old('title', $campaign->title) }}" required class="admin-input" data-campaign-field="title" @disabled($campaign->status === 'sent')></div>
            <div><label class="admin-label">Mail konusu</label><input name="subject" value="{{ old('subject', $campaign->subject) }}" required class="admin-input" data-campaign-field="subject" @disabled($campaign->status === 'sent')></div>
        </div>
        <div><label class="admin-label">Ön başlık</label><input name="preheader" value="{{ old('preheader', $campaign->preheader) }}" class="admin-input" data-campaign-field="preheader" @disabled($campaign->status === 'sent')></div>
        <x-admin.rich-editor
            name="body"
            label="Mail metni"
            :value="old('body', $campaign->body)"
            :rows="10"
            min-height="16rem"
            ai-field="campaign_body"
            hint="Ürün açıklamasındaki editörle aynı yapı. HTML modunda başlık, paragraf, liste, link, tablo ve görsel ekleyebilirsiniz."
        />
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="admin-label">Görsel URL</label><input name="image_url" value="{{ old('image_url', $campaign->image_url) }}" class="admin-input" @disabled($campaign->status === 'sent')></div>
            <div><label class="admin-label">Alıcı grubu</label><select name="audience" class="admin-input" @disabled($campaign->status === 'sent')><option value="newsletter" @selected(old('audience', $campaign->audience) === 'newsletter')>Tüm bülten aboneleri</option></select></div>
            <div><label class="admin-label">Buton metni</label><input name="button_label" value="{{ old('button_label', $campaign->button_label) }}" class="admin-input" data-campaign-field="button_label" @disabled($campaign->status === 'sent')></div>
            <div><label class="admin-label">Buton linki</label><input name="button_url" value="{{ old('button_url', $campaign->button_url) }}" class="admin-input" data-campaign-field="button_url" @disabled($campaign->status === 'sent')></div>
        </div>
        <div class="admin-form-actions">
            @if($campaign->status !== 'sent')
                <button class="admin-btn admin-btn-primary px-8 py-2.5">Kaydet</button>
            @endif
            <a href="{{ route('admin.newsletter.index') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Listeye dön</a>
        </div>
    </form>
@endsection

@push('scripts')
    @php
        $emailCampaignTemplates = $templates->mapWithKeys(function ($template) {
            return [
                $template->id => [
                    'title' => $template->title,
                    'subject' => $template->subject,
                    'preheader' => $template->preheader,
                    'body' => $template->body,
                    'button_label' => $template->button_label,
                    'button_url' => $template->button_url,
                ],
            ];
        });
    @endphp
    <script>
        window.emailCampaignTemplates = @js($emailCampaignTemplates);

        document.querySelector('[data-campaign-template-select]')?.addEventListener('change', function () {
            const template = window.emailCampaignTemplates?.[this.value];
            if (!template) return;

            Object.entries(template).forEach(([field, value]) => {
                const input = document.querySelector(`[data-campaign-field="${field}"], [name="${field}"]`);
                if (!input || value === null) return;
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    </script>
@endpush
