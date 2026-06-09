@extends('layouts.admin')

@section('title', 'Site ayarları')



@section('content')

    @php

        $tabs = [

            'general' => ['label' => 'Genel', 'desc' => 'Marka adı ve mağaza geneli'],

            'header' => ['label' => 'Header', 'desc' => 'Üst bar, logo, promo, çerez'],

            'footer' => ['label' => 'Footer', 'desc' => 'Alt bilgi, güven rozetleri, kartlar'],

            'contact' => ['label' => 'İletişim', 'desc' => 'Telefon, e-posta, iletişim sayfası'],

            'home' => ['label' => 'Ana sayfa', 'desc' => 'Bülten ve marka şeridi'],

            'maintenance' => ['label' => 'Bakım', 'desc' => 'Mağaza aç / kapa'],

            'shipping' => ['label' => 'Kargo & ödeme', 'desc' => 'Ücretler ve ödeme yöntemleri'],

            'integrations' => ['label' => 'Entegrasyonlar', 'desc' => 'OpenAI içerik üretimi'],

        ];

        $activeTab = $activeTab ?? 'general';

        $enabledCards = array_filter(explode(',', $values['footer_trust_cards'] ?? ''));

        $enabledCompliance = array_filter(explode(',', $values['footer_trust_compliance'] ?? ''));

        if ($enabledCards === []) {

            $enabledCards = config('kosar.footer.default_cards', []);

        }

        if ($enabledCompliance === []) {

            $enabledCompliance = config('kosar.footer.default_compliance', []);

        }

    @endphp



    <x-admin.page-header title="Site ayarları" subtitle="Mağaza kimliği, vitrin, iletişim, entegrasyon ve ödeme ayarları" />



    @if(session('success'))

        <p class="admin-alert-success mb-4">{{ session('success') }}</p>

    @endif

    @if(session('preview_settings'))

        <p class="admin-alert-success mb-4">Önizleme modu açık — mağazada geçici ayarlar görünür.</p>

    @endif



    <div class="admin-settings admin-settings-shell">

        <aside class="admin-settings-sidebar" aria-label="Site ayarı bölümleri">
            <div class="admin-settings-sidebar__head">
                <span class="admin-settings-sidebar__eyebrow">Ayar merkezi</span>
                <strong>{{ $tabs[$activeTab]['label'] ?? 'Genel' }}</strong>
                <p>{{ $tabs[$activeTab]['desc'] ?? 'Mağaza yapılandırması' }}</p>
            </div>

            <nav class="admin-settings-tabs" role="tablist">

            @foreach($tabs as $id => $meta)

                <a href="{{ route('admin.settings.edit', ['tab' => $id]) }}"

                   role="tab"

                   id="settings-tab-{{ $id }}"

                   class="admin-settings-tab {{ $activeTab === $id ? 'is-active' : '' }}"

                   aria-selected="{{ $activeTab === $id ? 'true' : 'false' }}"

                   aria-controls="settings-panel-{{ $id }}">

                    <span class="admin-settings-tab__icon">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>

                    <span class="admin-settings-tab__body">
                        <span class="admin-settings-tab__label">{{ $meta['label'] }}</span>

                        <span class="admin-settings-tab__hint">{{ $meta['desc'] }}</span>
                    </span>

                </a>

            @endforeach

            </nav>

            <div class="admin-settings-sidebar__foot">
                <p>İpucu: Kaydet tüm sekmelerdeki alanları birlikte gönderir. Kargo ve ödeme ayrı form olarak yönetilir.</p>
            </div>
        </aside>

        <div class="admin-settings-main">

        <div class="admin-settings-overview">
            <div class="admin-settings-overview__card">
                <span>Aktif bölüm</span>
                <strong>{{ $tabs[$activeTab]['label'] ?? 'Genel' }}</strong>
                <p>{{ $tabs[$activeTab]['desc'] ?? 'Mağaza yapılandırması' }}</p>
            </div>
            <a href="{{ route('admin.shipping-settings.edit') }}" class="admin-settings-overview__card admin-settings-overview__card--link">
                <span>Kargo & ödeme</span>
                <strong>Detay ayarları</strong>
                <p>Firma, ücret, KDV ve ödeme görünürlüğü.</p>
            </a>
            <a href="{{ route('admin.email-templates.index') }}" class="admin-settings-overview__card admin-settings-overview__card--link">
                <span>E-posta</span>
                <strong>Şablonlar</strong>
                <p>Sipariş ve kampanya mail metinleri.</p>
            </a>
        </div>



        {{-- Ana ayarlar formu --}}

        <form method="post"

              action="{{ route('admin.settings.update') }}"

              id="settings-form"

              enctype="multipart/form-data"

              class="admin-card p-6 sm:p-8 {{ $activeTab === 'shipping' ? 'hidden' : '' }}"

              data-ai-type="settings"

              @if($activeTab === 'shipping') hidden @endif>

            @csrf @method('PUT')

            <input type="hidden" name="_tab" value="{{ $activeTab }}">



            {{-- Genel --}}

            <div id="settings-panel-general" class="admin-settings-panel {{ $activeTab !== 'general' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-general">

                <p class="text-sm text-slate-600 mb-5">Mağaza kimliği ve vitrin genelinde kullanılan temel bilgiler.</p>

                <div><label class="admin-label">Site adı</label><input name="site_name" value="{{ $values['site_name'] }}" class="admin-input"></div>

                <div class="mt-4"><label class="admin-label">Ücretsiz kargo limiti (₺)</label><input name="free_shipping_min" value="{{ $values['free_shipping_min'] }}" class="admin-input max-w-xs"><p class="text-xs text-slate-500 mt-1">Standart kargo açıklamasında kullanılır. Kargo ücretleri <strong>Kargo & ödeme</strong> sekmesindedir.</p></div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="admin-checkbox font-semibold text-slate-800">
                        <input type="checkbox" name="shop_show_stock_quantity" value="1" @checked(old('shop_show_stock_quantity', $values['shop_show_stock_quantity'] ?? '0') === '1')>
                        Vitrinde stok adedi göster
                    </label>
                    <p class="text-sm text-slate-600 mt-2">Kapalıyken ürün kartı ve ürün sayfasında yalnızca «Stokta» / «Stokta yok» görünür; kaç adet kaldığı yazılmaz. Paneldeki stok alanı yine güncellenir.</p>
                </div>

                <h3 class="admin-section-title mt-8">Google</h3>
                <div><label class="admin-label">Search Console doğrulama kodu</label><input name="google_site_verification" value="{{ $values['google_site_verification'] }}" class="admin-input font-mono text-sm" placeholder="meta content değeri"></div>
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-4 md:grid md:grid-cols-2">
                        <div>
                            <label class="admin-label">HTML doğrulama dosya adı</label>
                            <input name="google_verification_file_name" value="{{ $values['google_verification_file_name'] }}" class="admin-input font-mono text-sm" placeholder="googlexxxxxxxxxxxxxxxx.html">
                        </div>
                        <div>
                            <label class="admin-label">HTML doğrulama dosya içeriği</label>
                            <input name="google_verification_file_content" value="{{ $values['google_verification_file_content'] }}" class="admin-input font-mono text-sm" placeholder="google-site-verification: googlexxxxxxxxxxxxxxxx.html">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Google Search Console HTML dosyası yönteminde kullanılır. Kaldırmak için iki alanı da boş bırakın.</p>
                    @if(!empty($values['google_verification_file_name']))
                        <a class="mt-2 inline-flex text-xs font-semibold text-teal-700 hover:text-teal-900" href="{{ url($values['google_verification_file_name']) }}" target="_blank" rel="noopener">Doğrulama dosyasını aç</a>
                    @endif
                </div>
                <div class="mt-4"><label class="admin-label">Google Analytics (GA4) ölçüm kimliği</label><input name="google_analytics_id" value="{{ $values['google_analytics_id'] }}" class="admin-input font-mono text-sm" placeholder="G-XXXXXXXXXX"></div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-bold text-slate-900">Google Merchant Center ürün feed’i</h4>
                    <p class="mt-2 text-sm text-slate-600">
                        Aktif ve stoklu ürünleriniz otomatik XML feed olarak üretilir. Google Merchant Center’da bir kez bağladıktan sonra ürün güncellemeleri panelden yapılır; feed kendiliğinden güncellenir.
                    </p>

                    <div class="mt-4">
                        <label class="admin-label">Feed URL</label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                            <input
                                id="merchant-feed-url"
                                type="text"
                                readonly
                                value="{{ $merchantFeedUrl }}"
                                class="admin-input font-mono text-xs bg-white sm:flex-1"
                                onclick="this.select()"
                            >
                            <button type="button" class="admin-btn admin-btn-secondary shrink-0" data-copy-merchant-feed>
                                Kopyala
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Şu an feed’e uygun ürün: <strong>{{ number_format($merchantFeedProductCount, 0, ',', '.') }}</strong>
                            (yayında, stokta ve görseli olan ürünler)
                        </p>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <a href="{{ $merchantFeedUrl }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary">
                            Feed’i test et
                        </a>
                        <a href="https://merchants.google.com/mc/products/sources" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">
                            Google Merchant Center’a ekle
                        </a>
                    </div>

                    <ol class="mt-4 list-decimal list-inside space-y-1 text-xs text-slate-600">
                        <li>Google Merchant Center → <strong>Veri kaynakları</strong> → <strong>Ekle</strong></li>
                        <li><strong>Dosya veya feed URL’si</strong> seçin</li>
                        <li>Feed URL alanına yukarıdaki adresi yapıştırın</li>
                        <li>Dil: Türkçe, ülke: Türkiye, güncelleme: günde 1</li>
                    </ol>
                </div>

            </div>



            {{-- Header --}}

            <div id="settings-panel-header" class="admin-settings-panel {{ $activeTab !== 'header' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-header">

                <p class="text-sm text-slate-600 mb-5">Üst şerit, logo, arama çubuğu ve çerez bildirimi. Menü linkleri için <a href="{{ route('admin.menu.index') }}" class="text-teal-700 font-semibold">Menü</a> sayfasını kullanın.</p>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">

                    <label class="admin-label">Site logosu (header & footer)</label>

                    <x-admin.image-spec key="site_logo" />

                    @if($logoUrl)

                        <div class="flex flex-wrap items-center gap-4">

                            <img src="{{ $logoUrl }}" alt="Mevcut logo" class="h-14 max-w-[200px] object-contain rounded-lg bg-white border border-slate-200 p-2">

                            <label class="admin-checkbox text-sm text-slate-600">

                                <input type="checkbox" name="remove_site_logo" value="1"> Logoyu kaldır (K harfi gösterilir)

                            </label>

                        </div>

                    @else

                        <p class="text-sm text-slate-500">Logo yok — vitrinde yeşil <strong>K</strong> kutusu görünür.</p>

                    @endif

                    <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">

                    <label class="admin-checkbox mt-2 block"><input type="checkbox" name="logo_strip_white" value="1"> Beyaz arka planı otomatik temizle (sadece tam beyaz pikseller)</label>

                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3 mt-4">
                    <label class="admin-label">Favicon (sekme ikonu)</label>
                    <x-admin.image-spec key="site_favicon" />
                    <div class="flex flex-wrap items-center gap-4">
                        <img src="{{ $faviconUrl ?? asset('favicon.svg') }}" alt="" class="h-10 w-10 rounded-lg object-contain bg-white border border-slate-200 p-1">
                        <p class="text-sm text-slate-500">Yüklemezseniz varsayılan <strong>K</strong> ikonu kullanılır.</p>
                    </div>
                    @if($faviconUrl)
                        <label class="admin-checkbox text-sm text-slate-600">
                            <input type="checkbox" name="remove_site_favicon" value="1"> Özel favicon'u kaldır
                        </label>
                    @endif
                    <input type="file" name="site_favicon" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon,.ico" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800">
                </div>

                <div class="mt-4"><label class="admin-label">Logo alt sloganı</label><input name="tagline" value="{{ $values['tagline'] }}" class="admin-input"></div>

                <div class="mt-4"><label class="admin-label">Üst promo şerit metni</label><input name="promo_text" value="{{ $values['promo_text'] }}" class="admin-input" placeholder="1000 TL üzeri kargo bedava"></div>

                <h3 class="admin-section-title mt-8">Yüzen arayüz butonları</h3>
                <label class="admin-checkbox font-semibold text-slate-800">
                    <input type="checkbox" name="scroll_top_enabled" value="1" @checked(($values['scroll_top_enabled'] ?? '1') === '1')>
                    Sağ altta «Yukarı çık» butonu göster
                </label>
                <p class="text-sm text-slate-600 mt-2">Ziyaretçi sayfada aşağı indikten sonra görünür; tıklayınca sayfanın en üstüne çıkar.</p>

                <h3 class="admin-section-title mt-8">Çerez bildirimi</h3>

                <div><label class="admin-label">Metin</label><textarea name="cookie_text" rows="2" class="admin-input">{{ $values['cookie_text'] }}</textarea></div>

                <div class="mt-4"><label class="admin-label">Kabul butonu</label><input name="cookie_accept" value="{{ $values['cookie_accept'] }}" class="admin-input max-w-xs"></div>

            </div>



            {{-- Footer --}}

            <div id="settings-panel-footer" class="admin-settings-panel {{ $activeTab !== 'footer' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-footer">

                <p class="text-sm text-slate-600 mb-5">Alt bilgi metinleri, ödeme kartları ve uyumluluk rozetleri. Ek footer linkleri <a href="{{ route('admin.menu.index') }}" class="text-teal-700 font-semibold">Menü</a>; yasal sayfalar <a href="{{ route('admin.pages.index') }}" class="text-teal-700 font-semibold">Sayfalar</a> üzerinden yönetilir.</p>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="admin-label mb-0">Footer kısa açıklama</label>
                        <x-admin.ai-btn field="site_description" label="AI" variant="ghost" />
                    </div>
                    <textarea name="site_description" rows="2" class="admin-input">{{ $values['site_description'] }}</textarea>
                </div>

                <div class="mt-4"><label class="admin-label">Telif / şirket unvanı (alt satır)</label><input name="legal_name" value="{{ $values['legal_name'] }}" class="admin-input"></div>

                <h3 class="admin-section-title mt-8">Sosyal medya</h3>
                <p class="text-sm text-slate-600 mb-4">Doldurduğunuz hesaplar footer’da marka sütununda ikon olarak görünür. Boş bırakılanlar listelenmez.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label">Instagram</label>
                        <input name="social_instagram_url" value="{{ $values['social_instagram_url'] ?? '' }}" class="admin-input" placeholder="https://instagram.com/kosar">
                    </div>
                    <div>
                        <label class="admin-label">Facebook</label>
                        <input name="social_facebook_url" value="{{ $values['social_facebook_url'] ?? '' }}" class="admin-input" placeholder="https://facebook.com/kosar">
                    </div>
                    <div>
                        <label class="admin-label">YouTube</label>
                        <input name="social_youtube_url" value="{{ $values['social_youtube_url'] ?? '' }}" class="admin-input" placeholder="https://youtube.com/@kosar">
                    </div>
                    <div>
                        <label class="admin-label">LinkedIn</label>
                        <input name="social_linkedin_url" value="{{ $values['social_linkedin_url'] ?? '' }}" class="admin-input" placeholder="https://linkedin.com/company/kosar">
                    </div>
                    <div>
                        <label class="admin-label">X (Twitter)</label>
                        <input name="social_x_url" value="{{ $values['social_x_url'] ?? '' }}" class="admin-input" placeholder="https://x.com/kosar">
                    </div>
                    <div>
                        <label class="admin-label">TikTok</label>
                        <input name="social_tiktok_url" value="{{ $values['social_tiktok_url'] ?? '' }}" class="admin-input" placeholder="https://tiktok.com/@kosar">
                    </div>
                </div>



                <h3 class="admin-section-title mt-8">Kartlar & güven rozetleri</h3>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">

                    <div>

                        <p class="admin-label mb-2">Kabul edilen kartlar</p>

                        <p class="text-xs text-slate-500 mb-3">{{ config('kosar.footer.card_image_hint') }}</p>

                        <div class="flex flex-wrap gap-3 p-3 rounded-xl bg-slate-800/90">

                            @foreach($footerCardCatalog as $key => $card)

                                @if(!str_starts_with($key, 'custom_'))

                                    <label class="relative cursor-pointer group">

                                        <input type="checkbox" name="footer_trust_cards[]" value="{{ $key }}" @checked(in_array($key, $enabledCards, true)) class="sr-only peer">

                                        <x-shop.payment-card-icon

                                            :brand="$card['brand'] ?? $key"

                                            :label="$card['label']"

                                            :image="$card['image'] ?? null"

                                            class="ring-2 ring-transparent peer-focus-visible:ring-teal-400 peer-checked:ring-teal-400 opacity-40 peer-checked:opacity-100 group-hover:opacity-90 transition-opacity"

                                        />

                                    </label>

                                @endif

                            @endforeach

                        </div>

                        <p class="text-xs text-slate-500 mt-2">Tıklayarak footer’da göster / gizle.</p>

                    </div>

                    @if(count($footerExtraCards) > 0)

                        <div>

                            <p class="admin-label mb-2">Eklenen özel kartlar</p>

                            <ul class="space-y-2">

                                @foreach($footerExtraCards as $extra)

                                    <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">

                                        <div class="flex items-center gap-2 min-w-0">

                                            @if(!empty($extra['image']))

                                                <x-shop.payment-card-icon

                                                    brand="custom"

                                                    :label="$extra['label'] ?? 'Kart'"

                                                    :image="\Illuminate\Support\Facades\Storage::disk('public')->url($extra['image'])"

                                                />

                                            @endif

                                            <span class="text-sm font-medium truncate">{{ $extra['label'] ?? $extra['key'] }}</span>

                                        </div>

                                        <button type="submit" name="remove_footer_extra_card" value="{{ $extra['key'] }}" class="text-xs font-semibold text-red-600 hover:text-red-800 shrink-0" onclick="return confirm('Bu kart kaldırılsın mı?');">Sil</button>

                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @endif

                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-4 space-y-3">

                        <p class="text-sm font-semibold text-slate-800">Yeni kart görseli ekle</p>

                        <div><label class="admin-label">Etiket</label><input name="footer_extra_card_label" class="admin-input" placeholder="Troy, BKM…"></div>

                        <div><label class="admin-label">Görsel (PNG)</label><input type="file" name="footer_extra_card_image" accept="image/png,image/webp,image/jpeg" class="admin-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-teal-800"></div>

                        <button type="submit" class="admin-btn admin-btn-secondary text-sm">Kart ekle</button>

                    </div>

                    <div>

                        <p class="admin-label mb-2">Güven & uyumluluk</p>

                        <div class="flex flex-wrap gap-3">

                            @foreach(config('kosar.footer.compliance', []) as $key => $item)

                                <label class="admin-checkbox text-sm">

                                    <input type="checkbox" name="footer_trust_compliance[]" value="{{ $key }}" @checked(in_array($key, $enabledCompliance, true))>

                                    {{ $item['label'] }}

                                </label>

                            @endforeach

                        </div>

                    </div>

                    <div>

                        <label class="admin-label">ETBİS doğrulama linki (isteğe bağlı)</label>

                        <input name="footer_etbis_url" value="{{ $values['footer_etbis_url'] ?? '' }}" class="admin-input" placeholder="https://etbis.ticaret.gov.tr/...">

                    </div>

                    <div>

                        <label class="admin-label">KVKK / gizlilik linki (isteğe bağlı)</label>

                        <input name="footer_kvkk_url" value="{{ $values['footer_kvkk_url'] ?? '' }}" class="admin-input" placeholder="Boş = gizlilik politikası sayfası">

                    </div>

                </div>

                <p class="text-sm text-slate-600 mt-4">Footer’da listelenen ödeme yöntemleri (havale, kapıda vb.) <strong>Kargo & ödeme</strong> sekmesindeki görünürlük tablosundan yönetilir.</p>

            </div>



            {{-- İletişim --}}

            <div id="settings-panel-contact" class="admin-settings-panel {{ $activeTab !== 'contact' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-contact">

                <p class="text-sm text-slate-600 mb-5">Footer iletişim alanı, WhatsApp butonu ve iletişim sayfası.</p>

                <div><label class="admin-label">Telefon</label><input name="contact_phone" value="{{ $values['contact_phone'] }}" class="admin-input"></div>

                <div class="mt-4"><label class="admin-label">E-posta</label><input name="contact_email" value="{{ $values['contact_email'] }}" class="admin-input"></div>

                <div class="mt-4"><label class="admin-label">WhatsApp (ülke kodu ile, boşluksuz)</label><input name="contact_whatsapp" value="{{ $values['contact_whatsapp'] }}" class="admin-input" placeholder="905554443000"></div>

                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 space-y-3">
                    <label class="admin-checkbox font-semibold text-slate-800">
                        <input type="checkbox" name="floating_whatsapp_enabled" value="1" @checked(($values['floating_whatsapp_enabled'] ?? '1') === '1')>
                        Sol altta yüzen WhatsApp butonu göster
                    </label>
                    <p class="text-sm text-slate-600">Butonun görünmesi için WhatsApp numarası da dolu olmalıdır.</p>
                </div>

                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 space-y-3">
                    <label class="admin-checkbox font-semibold text-slate-800">
                        <input type="checkbox" name="pdp_whatsapp_order_enabled" value="1" @checked(old('pdp_whatsapp_order_enabled', $values['pdp_whatsapp_order_enabled'] ?? '1') === '1')>
                        Ürün sayfasında «WhatsApp'tan sipariş ver» butonu
                    </label>
                    <p class="text-sm text-slate-600">Stokta olan ürünlerde sepete ekle altında görünür. Mesajda ürün adı, SKU, adet ve link yer alır.</p>
                    <div>
                        <label class="admin-label">Buton metni (opsiyonel)</label>
                        <input name="pdp_whatsapp_order_label" value="{{ old('pdp_whatsapp_order_label', $values['pdp_whatsapp_order_label'] ?? '') }}" class="admin-input" placeholder="WhatsApp'tan sipariş ver">
                    </div>
                </div>

                <div class="mt-4"><label class="admin-label">Adres</label><input name="contact_address" value="{{ $values['contact_address'] }}" class="admin-input"></div>

                <div class="mt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="admin-label mb-0">İletişim sayfası giriş metni</label>
                        <x-admin.ai-btn field="contact_page_intro" label="AI" variant="ghost" />
                    </div>
                    <textarea name="contact_page_intro" rows="2" class="admin-input">{{ $values['contact_page_intro'] }}</textarea>
                </div>

                <h3 class="admin-section-title mt-8">İletişim sayfası SEO</h3>
                <div><label class="admin-label">SEO başlık</label><input name="contact_meta_title" value="{{ $values['contact_meta_title'] }}" class="admin-input" placeholder="İletişim — Kosar"></div>
                <div class="mt-4"><label class="admin-label">SEO açıklama</label><textarea name="contact_meta_description" rows="2" class="admin-input" maxlength="320">{{ $values['contact_meta_description'] }}</textarea></div>
            </div>



            {{-- Entegrasyonlar --}}
            <div id="settings-panel-integrations" class="admin-settings-panel {{ $activeTab !== 'integrations' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-integrations">
                <p class="text-sm text-slate-600 mb-5">Bu bölüm sadece içerik, bülten ve mail gönderim servisleri içindir. Ödeme entegrasyonları (PayTR, iyzico) için sol menüden
                    <strong>Entegrasyonlar → Ödeme</strong> bölümünü kullanın:
                    <a href="{{ route('admin.integrations.payment.index') }}" class="text-teal-700 font-semibold">Entegrasyonlar → Ödeme</a>.
                </p>

                <div class="admin-settings-service-grid">
                    <section class="admin-settings-service-card">
                        <div class="admin-settings-service-card__head">
                            <div>
                                <span class="admin-settings-service-card__eyebrow">İçerik üretimi</span>
                                <h3>OpenAI</h3>
                                <p>Paneldeki AI ile yaz ve meta öneri destekleri.</p>
                            </div>
                            @if(\App\Services\OpenAiService::isConfigured())
                                <span class="admin-status-pill admin-status-pill--ok">Aktif</span>
                            @else
                                <span class="admin-status-pill admin-status-pill--warn">Eksik</span>
                            @endif
                        </div>
                        @if(\App\Services\OpenAiService::isConfigured())
                            <p class="admin-alert-success mt-4 text-sm">OpenAI bağlantısı yapılandırıldı (model: {{ $values['openai_model'] ?: 'gpt-4o-mini' }}).</p>
                        @else
                            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-4">Henüz API anahtarı yok — formlardaki «AI ile yaz» butonları devre dışı kalır. Yerel «Meta öner» her zaman çalışır.</p>
                        @endif
                        <div class="mt-4">
                            <label class="admin-label">OpenAI API anahtarı</label>
                            <input type="password" name="openai_api_key" value="" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ !empty($values['openai_api_key']) ? 'Kayıtlı — değiştirmek için yeni anahtar yazın' : 'sk-...' }}">
                        </div>
                        <div class="mt-4">
                            <label class="admin-label">Model</label>
                            <select name="openai_model" class="admin-input">
                                @foreach(['gpt-4o-mini' => 'GPT-4o mini (önerilen)', 'gpt-4o' => 'GPT-4o', 'gpt-4.1-mini' => 'GPT-4.1 mini'] as $modelId => $modelLabel)
                                    <option value="{{ $modelId }}" @selected(($values['openai_model'] ?? 'gpt-4o-mini') === $modelId)>{{ $modelLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </section>

                    <section class="admin-settings-service-card">
                        <div class="admin-settings-service-card__head">
                            <div>
                                <span class="admin-settings-service-card__eyebrow">E-bülten listesi</span>
                                <h3>Brevo</h3>
                                <p>Yeni aboneleri Brevo listenize senkronize eder.</p>
                            </div>
                            @if(app(\App\Services\BrevoNewsletterService::class)->isConfigured())
                                <span class="admin-status-pill admin-status-pill--ok">Aktif</span>
                            @else
                                <span class="admin-status-pill admin-status-pill--warn">Eksik</span>
                            @endif
                        </div>
                        @if(app(\App\Services\BrevoNewsletterService::class)->isConfigured())
                            <p class="admin-alert-success mt-4 text-sm">Brevo bağlantısı aktif. Yeni aboneler hem yerel listeye hem Brevo listesine kaydedilir.</p>
                        @else
                            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-4">Brevo henüz tam yapılandırılmadı. API anahtarı ve liste ID girilene kadar aboneler sadece paneldeki yerel listeye kaydedilir.</p>
                        @endif
                        <label class="admin-checkbox font-semibold text-slate-800 mt-4">
                            <input type="checkbox" name="brevo_enabled" value="1" @checked(($values['brevo_enabled'] ?? '0') === '1')>
                            Brevo senkronizasyonunu aç
                        </label>
                        <div class="mt-4">
                            <label class="admin-label">Brevo API anahtarı</label>
                            <input type="password" name="brevo_api_key" value="" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ !empty($values['brevo_api_key']) ? 'Kayıtlı — değiştirmek için yeni anahtar yazın' : 'xkeysib-...' }}">
                            <p class="text-xs text-slate-500 mt-1">Brevo panelinde SMTP & API → API Keys bölümünden alınır. Boş bırakırsanız kayıtlı anahtar korunur.</p>
                        </div>
                        <div class="mt-4">
                            <label class="admin-label">Brevo liste ID</label>
                            <input type="number" min="1" name="brevo_list_id" value="{{ $values['brevo_list_id'] ?? '' }}" class="admin-input" placeholder="Örn. 3">
                            <p class="text-xs text-slate-500 mt-1">Brevo Contacts → Lists ekranındaki listenin sayısal ID değeri.</p>
                        </div>
                    </section>

                    <section class="admin-settings-service-card admin-settings-service-card--wide">
                        <div class="admin-settings-service-card__head">
                            <div>
                                <span class="admin-settings-service-card__eyebrow">İşlemsel e-postalar</span>
                                <h3>SMTP e-posta gönderimi</h3>
                                <p>Sipariş onayı, sipariş durumu ve sistem bildirimlerini gönderir.</p>
                            </div>
                            @if(\App\Support\MailSettings::isConfigured())
                                <span class="admin-status-pill admin-status-pill--ok">Aktif</span>
                            @else
                                <span class="admin-status-pill admin-status-pill--warn">Eksik</span>
                            @endif
                        </div>
                        @if(\App\Support\MailSettings::isConfigured())
                            <p class="admin-alert-success mt-4 text-sm">SMTP ayarları aktif. Sipariş ve durum e-postaları bu bilgilerle gönderilir.</p>
                        @else
                            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-4">SMTP ayarları eksik veya kapalı. Sipariş e-postaları gerçek gönderim yerine varsayılan mail ayarına düşer.</p>
                        @endif
                        <label class="admin-checkbox font-semibold text-slate-800 mt-4">
                            <input type="checkbox" name="smtp_enabled" value="1" @checked(($values['smtp_enabled'] ?? '0') === '1')>
                            Sipariş e-postaları için SMTP kullan
                        </label>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><label class="admin-label">SMTP host</label><input name="smtp_host" value="{{ $values['smtp_host'] ?? '' }}" class="admin-input font-mono text-sm" placeholder="smtp.domain.com"></div>
                            <div><label class="admin-label">SMTP port</label><input type="number" min="1" max="65535" name="smtp_port" value="{{ $values['smtp_port'] ?? '587' }}" class="admin-input" placeholder="587"></div>
                            <div>
                                <label class="admin-label">Şifreleme</label>
                                <select name="smtp_encryption" class="admin-input">
                                    <option value="" @selected(($values['smtp_encryption'] ?? '') === '')>Yok / otomatik</option>
                                    <option value="tls" @selected(($values['smtp_encryption'] ?? '') === 'tls')>TLS</option>
                                    <option value="ssl" @selected(($values['smtp_encryption'] ?? '') === 'ssl')>SSL</option>
                                </select>
                            </div>
                            <div><label class="admin-label">SMTP kullanıcı adı</label><input name="smtp_username" value="{{ $values['smtp_username'] ?? '' }}" class="admin-input font-mono text-sm" autocomplete="username"></div>
                            <div><label class="admin-label">SMTP şifre</label><input type="password" name="smtp_password" value="" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ !empty($values['smtp_password']) ? 'Kayıtlı — değiştirmek için yeni şifre yazın' : 'SMTP şifresi' }}"></div>
                            <div><label class="admin-label">Gönderen e-posta</label><input type="email" name="smtp_from_address" value="{{ $values['smtp_from_address'] ?? '' }}" class="admin-input" placeholder="siparis@domain.com"></div>
                            <div><label class="admin-label">Gönderen adı</label><input name="smtp_from_name" value="{{ $values['smtp_from_name'] ?? config('app.name') }}" class="admin-input" placeholder="KOŞAR Ticaret"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Şifre alanını boş bırakırsanız kayıtlı SMTP şifresi korunur. Test e-postası göndermeden önce ayarları kaydedin.</p>
                    </section>

                    <section class="admin-settings-service-card admin-settings-service-card--wide">
                        <div class="admin-settings-service-card__head">
                            <div>
                                <span class="admin-settings-service-card__eyebrow">Muhasebe</span>
                                <h3>Paraşüt Muhasebe</h3>
                                <p>Sipariş detayından manuel taslak satış faturası oluşturur.</p>
                            </div>
                            @if(($values['parasut_enabled'] ?? '0') === '1' && !empty($values['parasut_access_token']))
                                <span class="admin-status-pill admin-status-pill--ok">Bağlı</span>
                            @elseif(($values['parasut_enabled'] ?? '0') === '1')
                                <span class="admin-status-pill admin-status-pill--warn">Ayar bekliyor</span>
                            @else
                                <span class="admin-status-pill admin-status-pill--warn">Kapalı</span>
                            @endif
                        </div>
                        <label class="admin-checkbox font-semibold text-slate-800 mt-4">
                            <input type="checkbox" name="parasut_enabled" value="1" @checked(($values['parasut_enabled'] ?? '0') === '1')>
                            Paraşüt entegrasyonunu aç
                        </label>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><label class="admin-label">Client ID</label><input name="parasut_client_id" value="{{ $values['parasut_client_id'] ?? '' }}" class="admin-input font-mono text-sm" autocomplete="off"></div>
                            <div><label class="admin-label">Client Secret</label><input type="password" name="parasut_client_secret" value="" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ !empty($values['parasut_client_secret']) ? 'Kayıtlı — değiştirmek için yeni secret yazın' : 'Client secret' }}"></div>
                            <div><label class="admin-label">Firma / Company ID</label><input name="parasut_company_id" value="{{ $values['parasut_company_id'] ?? '' }}" class="admin-input font-mono text-sm" placeholder="Paraşüt firma ID"></div>
                            <div><label class="admin-label">Paraşüt e-posta / kullanıcı adı</label><input name="parasut_username" value="{{ $values['parasut_username'] ?? '' }}" class="admin-input font-mono text-sm" autocomplete="username" placeholder="mail@firma.com"></div>
                            <div><label class="admin-label">Paraşüt şifresi</label><input type="password" name="parasut_password" value="" class="admin-input font-mono text-sm" autocomplete="new-password" placeholder="{{ !empty($values['parasut_password']) ? 'Kayıtlı — değiştirmek için yeni şifre yazın' : 'Paraşüt şifresi' }}"></div>
                            <div>
                                <label class="admin-label">Redirect URI (opsiyonel)</label>
                                <input name="parasut_redirect_uri" value="{{ $values['parasut_redirect_uri'] ?? '' }}" class="admin-input font-mono text-xs" placeholder="Boş bırakın veya Paraşüt uygulamanızdaki URI">
                                <p class="text-xs text-slate-500 mt-1">Çoğu Paraşüt password grant kurulumunda boş bırakılır. Redirect hatası alırsanız Paraşüt uygulamanızdaki değerle aynı yazın.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.integrations.parasut.connect') }}" class="admin-btn admin-btn-secondary px-5 py-2.5">Paraşüt bağlantısını test et</a>
                            @if(!empty($values['parasut_access_token']))
                                <button type="submit"
                                        form="parasut-disconnect-form"
                                        class="admin-btn admin-btn-danger px-5 py-2.5"
                                        onclick="return confirm('Paraşüt bağlantısı kaldırılsın mı?');">
                                    Bağlantıyı kaldır
                                </button>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-3">Client ID/Secret için Paraşüt destekten API bilgileri alınır. Kullanıcı adı ve şifre Paraşüt paneline giriş bilgileridir. İlk sürümde otomatik fatura kesmez; manuel taslak satış faturası oluşturur.</p>
                    </section>
                </div>
            </div>



            {{-- Ana sayfa --}}

            <div id="settings-panel-home" class="admin-settings-panel {{ $activeTab !== 'home' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-home">

                <p class="text-sm text-slate-600 mb-5">Ana sayfa blokları ve slider <a href="{{ route('admin.home-banners.builder') }}" class="text-teal-700 font-semibold">Ana sayfa düzenleyici</a> üzerinden yönetilir.</p>

                <h3 class="admin-section-title" style="margin-top:0">Markalar şeridi</h3>

                <p class="text-sm text-slate-600 -mt-2 mb-3">Logolar <a href="{{ route('admin.brands.index') }}" class="text-teal-700 font-semibold">Markalar</a> menüsünden eklenir.</p>

                <div><label class="admin-label">Bölüm başlığı</label><input name="home_brands_title" value="{{ $values['home_brands_title'] ?? __('shop.home_brands') }}" class="admin-input" placeholder="Güvenilir Markalar"></div>

                <h3 class="admin-section-title mt-8">Pompa seçim programı</h3>

                @php $pumpOn = ($values['pump_selector_enabled'] ?? '1') === '1'; @endphp

                <div class="flex items-start gap-4 p-4 rounded-xl border {{ $pumpOn ? 'border-teal-200 bg-teal-50' : 'border-slate-200 bg-slate-50' }}">
                    <label class="admin-switch flex-shrink-0 mt-0.5" aria-label="Pompa seçim programını aç/kapa">
                        <input type="checkbox" name="pump_selector_enabled" value="1" class="admin-switch__input" @checked($pumpOn)>
                        <span class="admin-switch__track" aria-hidden="true"></span>
                    </label>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $pumpOn ? 'Açık' : 'Kapalı' }} — Header şeridi, nav pill ve /pompa-secici sayfası</p>
                        <p class="text-xs text-slate-500 mt-1">Kapalıyken teaser kartı, menü linkleri ve /pompa-secici sayfası gizlenir.</p>
                        <p class="text-xs text-slate-500 mt-1">Yeni ürün/kategori eklendiğinde otomatik algılar; ek bir işlem gerekmez.</p>
                    </div>
                </div>

                <h3 class="admin-section-title mt-8">Bülten kutusu</h3>

                <label class="admin-checkbox"><input type="checkbox" name="newsletter_enabled" value="1" @checked(($values['newsletter_enabled'] ?? '1') === '1')> Ana sayfada bülten kutusu göster</label>

                <div class="mt-4"><label class="admin-label">Bülten başlığı</label><input name="newsletter_title" value="{{ $values['newsletter_title'] ?? 'Kampanyalardan haberdar olun' }}" class="admin-input"></div>

            </div>



            {{-- Bakım modu --}}

            <div id="settings-panel-maintenance" class="admin-settings-panel {{ $activeTab !== 'maintenance' ? 'hidden' : '' }}" role="tabpanel" aria-labelledby="settings-tab-maintenance">

                @php $maintOn = ($values['shop_maintenance_enabled'] ?? '0') === '1'; @endphp

                <p class="text-sm text-slate-600 mb-5">Mağaza vitrinini geçici olarak kapatın. Yönetim paneli ve ödeme bildirimleri çalışmaya devam eder; yönetici hesabıyla giriş yapanlar vitrini önizleyebilir.</p>

                <div class="admin-maint-card {{ $maintOn ? 'admin-maint-card--on' : '' }}" data-maint-card>

                    <div class="admin-maint-card__head">

                        <div class="admin-maint-card__status">

                            <span class="admin-maint-card__dot" aria-hidden="true"></span>

                            <div>

                                <p class="admin-maint-card__label">Mağaza durumu</p>

                                <p class="admin-maint-card__state" data-maint-state>{{ $maintOn ? 'Bakım modu açık' : 'Mağaza yayında' }}</p>

                            </div>

                        </div>

                        <label class="admin-switch" title="Bakım modunu aç / kapa">

                            <input type="checkbox" name="shop_maintenance_enabled" value="1" class="admin-switch__input" data-maint-toggle @checked($maintOn)>

                            <span class="admin-switch__track" aria-hidden="true"></span>

                        </label>

                    </div>

                    <p class="admin-maint-card__hint" data-maint-hint>

                        @if($maintOn)

                            Ziyaretçiler bakım sayfasını görür (HTTP 503). Değişiklikler kaydedildikten sonra geçerli olur.

                        @else

                            Açtığınızda ziyaretçiler özel bakım sayfasına yönlendirilir; panel erişimi etkilenmez.

                        @endif

                    </p>

                </div>

                <div class="mt-6 space-y-4">

                    <div>

                        <label class="admin-label">Bakım başlığı</label>

                        <input name="shop_maintenance_title" value="{{ $values['shop_maintenance_title'] ?? '' }}" class="admin-input" placeholder="Bakım çalışması">

                    </div>

                    <div>

                        <label class="admin-label">Bakım mesajı</label>

                        <textarea name="shop_maintenance_message" rows="3" class="admin-input" placeholder="Mağazamız kısa süreliğine güncelleniyor…">{{ $values['shop_maintenance_message'] ?? '' }}</textarea>

                        <p class="text-xs text-slate-500 mt-1">Boş bırakırsanız varsayılan metin kullanılır.</p>

                    </div>

                </div>

            </div>



            <div class="admin-form-actions">

                <button type="submit" form="settings-form" class="admin-btn admin-btn-primary px-8 py-2.5">Kaydet</button>

                <button type="submit" form="settings-form" formaction="{{ route('admin.preview.start') }}" class="admin-btn admin-btn-secondary border-amber-300 text-amber-800">Önizle (kaydetmeden)</button>

            </div>

        </form>

        <form id="parasut-disconnect-form" method="post" action="{{ route('admin.integrations.parasut.disconnect') }}" class="hidden" aria-hidden="true" tabindex="-1">
            @csrf @method('DELETE')
        </form>

        <form method="post"
              action="{{ route('admin.settings.smtp-test') }}"
              class="admin-settings-service-card admin-settings-service-card--test mt-4 {{ $activeTab !== 'integrations' ? 'hidden' : '' }}"
              @if($activeTab !== 'integrations') hidden @endif>
            @csrf
            <input type="hidden" name="_tab" value="integrations">
            <div class="admin-settings-service-card__head">
                <div>
                    <span class="admin-settings-service-card__eyebrow">Kontrol aracı</span>
                    <h3>SMTP test e-postası</h3>
                    <p>Kayıtlı SMTP ayarlarıyla bir test e-postası gönderir.</p>
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <input type="email" name="smtp_test_email" value="{{ auth()->user()?->email }}" required class="admin-input sm:max-w-md" placeholder="test@domain.com">
                <button type="submit" class="admin-btn admin-btn-secondary px-5 py-2.5">Test e-postası gönder</button>
            </div>
        </form>



        {{-- Kargo & ödeme formu --}}

        <form method="post"

              action="{{ route('admin.shipping-settings.update') }}"

              id="shipping-form"

              class="admin-card p-6 sm:p-8 space-y-6 {{ $activeTab !== 'shipping' ? 'hidden' : '' }}"

              @if($activeTab !== 'shipping') hidden @endif>

            @csrf @method('PUT')

            <div id="settings-panel-shipping" role="tabpanel" aria-labelledby="settings-tab-shipping">

                <p class="text-sm text-slate-600 mb-2">Ödeme sayfası, kargo ücretleri ve footer ödeme listesi.</p>

                @include('admin.settings.partials.shipping-panel')

            </div>

            <x-admin.form-footer>Kaydet</x-admin.form-footer>

        </form>



        <form method="post" action="{{ route('admin.preview.stop') }}" class="mt-3">@csrf

            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-teal-700">Önizlemeyi kapat</button>

        </form>



        <div class="mt-8 admin-card p-5 text-sm text-slate-600">

            <p class="font-bold text-slate-900 mb-2">İlgili panel sayfaları</p>

            <ul class="list-disc pl-5 space-y-1">

                <li>Ana sayfa vitrin blokları → <a href="{{ route('admin.home-banners.builder') }}" class="text-teal-700 font-medium">Ana sayfa düzenleyici</a></li>

                <li>Üst / alt menü linkleri → <a href="{{ route('admin.menu.index') }}" class="text-teal-700 font-medium">Menü</a></li>

                <li>Sözleşme sayfaları → <a href="{{ route('admin.pages.index') }}" class="text-teal-700 font-medium">Sayfalar</a></li>

                <li>Marka logoları → <a href="{{ route('admin.brands.index') }}" class="text-teal-700 font-medium">Markalar</a></li>

                <li>İletişim formu mesajları → <a href="{{ route('admin.contact-messages.index') }}" class="text-teal-700 font-medium">İletişim mesajları</a></li>

            </ul>

        </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
    (function () {
        var copyBtn = document.querySelector('[data-copy-merchant-feed]');
        var feedInput = document.getElementById('merchant-feed-url');
        if (!copyBtn || !feedInput) return;

        copyBtn.addEventListener('click', function () {
            var value = feedInput.value;
            if (!value) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(showCopied).catch(fallbackCopy);
                return;
            }

            fallbackCopy();

            function fallbackCopy() {
                feedInput.focus();
                feedInput.select();
                try { document.execCommand('copy'); } catch (e) {}
                showCopied();
            }

            function showCopied() {
                var original = copyBtn.textContent;
                copyBtn.textContent = 'Kopyalandı';
                window.setTimeout(function () {
                    copyBtn.textContent = original;
                }, 1800);
            }
        });
    })();
</script>
@endpush

