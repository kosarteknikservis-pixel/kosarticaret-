@extends('layouts.admin')
@section('title', 'Bülten')

@section('content')
    <x-admin.page-header title="Bülten aboneleri" :subtitle="$subscribers->total().' kayıtlı e-posta'" />

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <form method="post" action="{{ route('admin.newsletter.campaigns.store') }}" class="admin-card p-6 sm:p-8 lg:col-span-2 space-y-4">
            @csrf
            <div>
                <h2 class="font-bold text-slate-900">Yeni kampanya taslağı</h2>
                <p class="text-sm text-slate-600 mt-1">Tasarımlı kampanya maili oluşturun, önce test gönderin, sonra tüm bülten abonelerine gönderin.</p>
            </div>
            <div>
                <label class="admin-label">Şablondan başlat</label>
                <select class="admin-input" data-campaign-template-select>
                    <option value="">Boş kampanya oluştur</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }} {{ str_starts_with($template->key, 'custom_') ? '(Özel)' : '(Sistem)' }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Seçtiğiniz şablon konu, başlık, metin ve buton alanlarını otomatik doldurur.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="admin-label">Kampanya başlığı</label><input name="title" required class="admin-input" placeholder="Haziran fırsatları" data-campaign-field="title"></div>
                <div><label class="admin-label">Mail konusu</label><input name="subject" required class="admin-input" placeholder="KOŞAR Haziran kampanyaları" data-campaign-field="subject"></div>
            </div>
            <div><label class="admin-label">Ön başlık</label><input name="preheader" class="admin-input" placeholder="Endüstriyel ekipmanlarda özel avantajlar" data-campaign-field="preheader"></div>
            <x-admin.rich-editor
                name="body"
                label="Mail metni"
                :value="old('body')"
                :rows="8"
                min-height="14rem"
                ai-field="campaign_body"
                hint="Ürün açıklamasındaki editörle aynı yapı. HTML modunda başlık, paragraf, liste, link, tablo ve görsel ekleyebilirsiniz."
            />
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="admin-label">Görsel URL</label><input name="image_url" class="admin-input" placeholder="https://..."></div>
                <div><label class="admin-label">Alıcı grubu</label><select name="audience" class="admin-input"><option value="newsletter">Tüm bülten aboneleri</option></select></div>
                <div><label class="admin-label">Buton metni</label><input name="button_label" class="admin-input" placeholder="Ürünleri incele" data-campaign-field="button_label"></div>
                <div><label class="admin-label">Buton linki</label><input name="button_url" class="admin-input" placeholder="https://kosarticaret.com/..." data-campaign-field="button_url"></div>
            </div>
            <button class="admin-btn admin-btn-primary px-6 py-2.5">Taslak oluştur</button>
        </form>

        <div class="admin-card p-6 sm:p-8">
            <h2 class="font-bold text-slate-900">Gönderim notları</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc pl-5">
                <li>Toplu gönderimden önce mutlaka test maili gönderin.</li>
                <li>Alıcı listesi aktif bülten abonelerinden oluşur.</li>
                <li>Gönderim sonuçları kampanya satırında saklanır.</li>
                <li>Sipariş mailleri ayrı şablonlardan otomatik gider.</li>
            </ul>
            <a href="{{ route('admin.email-templates.index') }}" class="admin-btn admin-btn-secondary mt-5 text-sm">E-posta şablonları</a>
        </div>
    </div>

    <div class="admin-card overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 class="font-bold text-slate-900">Kampanya geçmişi</h2>
        </div>
        <table class="admin-table">
            <thead><tr><th>Kampanya</th><th>Durum</th><th>Alıcı</th><th>Başarılı</th><th>Hatalı</th><th>Test</th><th></th></tr></thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <p class="font-semibold">{{ $campaign->title }}</p>
                            <p class="text-xs text-slate-500">{{ $campaign->subject }}</p>
                        </td>
                        <td>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold">{{ $campaign->status === 'sent' ? 'Gönderildi' : 'Taslak' }}</span>
                            @if($campaign->body_is_html)<span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">HTML</span>@endif
                        </td>
                        <td>{{ $campaign->recipients_count ?: '—' }}</td>
                        <td class="text-emerald-700 font-semibold">{{ $campaign->sent_count }}</td>
                        <td class="text-red-600 font-semibold">{{ $campaign->failed_count }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.newsletter.campaigns.test', $campaign) }}" class="flex gap-2">
                                @csrf
                                <input type="email" name="test_email" value="{{ auth()->user()?->email }}" class="admin-input text-xs min-w-[180px]" required>
                                <button class="admin-btn admin-btn-secondary text-xs py-1.5">Test</button>
                            </form>
                        </td>
                        <td class="text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                            @if($campaign->status !== 'sent')
                                <a href="{{ route('admin.newsletter.campaigns.edit', $campaign) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                                <a href="{{ route('admin.newsletter.campaigns.preview', $campaign) }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-1.5">Önizle</a>
                                <form method="post" action="{{ route('admin.newsletter.campaigns.send', $campaign) }}" onsubmit="return confirm('Bu kampanya tüm aktif bülten abonelerine gönderilsin mi?');">
                                    @csrf
                                    <button class="admin-btn admin-btn-primary text-xs py-1.5">Gönder</button>
                                </form>
                            @else
                                <a href="{{ route('admin.newsletter.campaigns.preview', $campaign) }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-1.5">Önizle</a>
                                <span class="text-xs text-slate-400">{{ $campaign->sent_at?->format('d.m.Y H:i') }}</span>
                            @endif
                                <form method="post" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Bu kampanya silinsin mi? Bu işlem geri alınamaz.');">
                                    @csrf @method('DELETE')
                                    <button class="admin-btn admin-btn-danger text-xs py-1.5">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-slate-500">Henüz kampanya yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($campaigns->hasPages())<div class="p-4 border-t">{{ $campaigns->links() }}</div>@endif
    </div>

    <div class="admin-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 class="font-bold text-slate-900">Aktif aboneler</h2>
        </div>
        <table class="admin-table">
            <thead><tr><th>E-posta</th><th>Kayıt tarihi</th></tr></thead>
            <tbody>
                @forelse($subscribers as $sub)
                    <tr>
                        <td class="font-medium">{{ $sub->email }}</td>
                        <td class="text-slate-500">{{ $sub->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center py-10 text-slate-500">Henüz abone yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($subscribers->hasPages())<div class="p-4 border-t">{{ $subscribers->links() }}</div>@endif
    </div>
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
