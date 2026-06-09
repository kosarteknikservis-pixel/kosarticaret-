@extends('layouts.admin')

@section('title', 'Tema Yönetimi')

@section('content')
    <section class="admin-dashboard-hero">
        <div>
            <p class="admin-dashboard-eyebrow">Vitrin tasarım kontrolü</p>
            <h2>Tema Yönetimi</h2>
            <p>Faz 7: Hazır tema presetleri, ana sayfa dahil bölüm şablonları ve güvenli özel CSS alanıyla vitrinin görünümünü kontrollü şekilde yönetin. İçerikler mevcut Site ayarları bölümlerinden yönetilmeye devam eder.</p>
        </div>
        <div class="admin-dashboard-hero__actions">
            <a href="{{ route('admin.settings.edit', ['tab' => 'header']) }}" class="admin-btn admin-btn-secondary">Header ayarları</a>
            <a href="{{ route('admin.settings.edit', ['tab' => 'footer']) }}" class="admin-btn admin-btn-secondary">Footer ayarları</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Mağazayı aç</a>
        </div>
    </section>

    <div class="admin-dashboard-grid admin-dashboard-grid--secondary">
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Güvenlik</p>
            <h2 class="mt-1 text-lg font-black text-slate-900">Geri dönüş var</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Tema yedekleri ayarlar ve özel CSS dahil komple görünüm snapshot'ı alır. Geri yükleme öncesi mevcut tema otomatik yedeklenir.</p>
        </section>
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Mevcut yapı</p>
            <h2 class="mt-1 text-lg font-black text-slate-900">Header/Footer korunur</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Logo, promo yazısı, footer güven kartları ve iletişim içerikleri Site ayarları içinde kalır.</p>
        </section>
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Yayın akışı</p>
            <h2 class="mt-1 text-lg font-black text-slate-900">Önizle, sonra yayınla</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Önizleme butonu ayarları canlıya almadan vitrinde geçici gösterir. Yayınla butonu kalıcı kaydeder.</p>
        </section>
    </div>

    <section class="admin-card mt-5 p-5 sm:p-7">
        <div class="admin-theme-backup-head">
            <div>
                <p class="admin-dashboard-eyebrow">Tema yedekleri</p>
                <h3>Komple Tema Yedeği</h3>
                <p>Tema ayarları, hazır preset seçimleri, bölüm şablonu değerleri ve özel CSS tek yedek içinde saklanır. En fazla {{ $themeBackupLimit }} yedek tutulur.</p>
            </div>
            <form method="post" action="{{ route('admin.theme.backup') }}" class="admin-theme-backup-create">
                @csrf
                <label class="sr-only" for="theme-backup-name">Yedek adı</label>
                <input id="theme-backup-name" name="backup_name" type="text" maxlength="80" placeholder="Yedek adı">
                <button type="submit" class="admin-btn admin-btn-primary">Yedek Al</button>
            </form>
        </div>

        <div class="admin-theme-backups">
            @forelse($themeBackups as $backup)
                <article class="admin-theme-backup">
                    <div>
                        <h4>{{ $backup['name'] }}</h4>
                        <p>{{ $backup['created_at'] }}</p>
                    </div>
                    <div class="admin-theme-backup__actions">
                        <form method="post" action="{{ route('admin.theme.backup.restore') }}" onsubmit="return confirm('Bu tema yedeği geri yüklenecek. Mevcut tema önce otomatik yedeklenecek. Devam edilsin mi?')">
                            @csrf
                            <input type="hidden" name="backup_id" value="{{ $backup['id'] }}">
                            <button type="submit" class="admin-btn admin-btn-primary">Geri Yükle</button>
                        </form>
                        <form method="post" action="{{ route('admin.theme.backup.delete') }}" onsubmit="return confirm('Bu yedek silinsin mi?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="backup_id" value="{{ $backup['id'] }}">
                            <button type="submit" class="admin-btn admin-btn-secondary">Sil</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="admin-theme-backup-empty">
                    Henüz tema yedeği yok. İlk yayına almadan önce bir yedek oluşturmanız önerilir.
                </div>
            @endforelse
        </div>
    </section>

    <section class="admin-card mt-5 p-5 sm:p-7">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="admin-dashboard-eyebrow">Tema presetleri</p>
                <h3 class="mt-1 text-xl font-black text-slate-900">Hazır Tema Seç</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Bir preset seçtiğinizde renk, header, ürün kartı, kategori grid, ürün detay ve footer ayarları topluca uygulanır. Önce önizleme yapıp sonra yayınlayabilirsiniz.</p>
            </div>
        </div>

        <div class="admin-theme-presets">
            @foreach($presets as $presetKey => $preset)
                <article class="admin-theme-preset">
                    <div class="admin-theme-preset__preview admin-theme-preset__preview--{{ $presetKey }}" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="admin-theme-preset__body">
                        <h4>{{ $preset['name'] }}</h4>
                        <p>{{ $preset['description'] }}</p>
                    </div>
                    <div class="admin-theme-preset__actions">
                        <form method="post" action="{{ route('admin.theme.preset') }}">
                            @csrf
                            <input type="hidden" name="preset" value="{{ $presetKey }}">
                            <input type="hidden" name="mode" value="preview">
                            <button type="submit" class="admin-btn admin-btn-secondary">Önizle</button>
                        </form>
                        <form method="post" action="{{ route('admin.theme.preset') }}" onsubmit="return confirm('{{ $preset['name'] }} teması canlıya uygulanacak. Devam edilsin mi?')">
                            @csrf
                            <input type="hidden" name="preset" value="{{ $presetKey }}">
                            <input type="hidden" name="mode" value="publish">
                            <button type="submit" class="admin-btn admin-btn-primary">Yayınla</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="admin-card mt-5 p-5 sm:p-7">
        <div>
            <p class="admin-dashboard-eyebrow">Bölüm şablonları</p>
            <h3 class="mt-1 text-xl font-black text-slate-900">Sadece Bir Bölümü Değiştir</h3>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Tüm temayı değiştirmeden yalnızca seçtiğiniz bölümün tasarım tipini uygulayın. Ana sayfa, Header, ürün kartı, ürün detay ve Footer ayrı ayrı yönetilebilir.</p>
        </div>

        <div class="admin-theme-section-presets">
            @foreach($sectionPresets as $sectionKey => $section)
                <section class="admin-theme-section-preset">
                    <div class="admin-theme-section-preset__head">
                        <p class="admin-dashboard-eyebrow">Bölüm</p>
                        <h4>{{ $section['label'] }}</h4>
                    </div>
                    <div class="admin-theme-section-preset__grid">
                        @foreach($section['templates'] as $templateKey => $template)
                            <article class="admin-theme-template">
                                <div>
                                    <h5>{{ $template['name'] }}</h5>
                                    <p>{{ $template['description'] }}</p>
                                </div>
                                <div class="admin-theme-template__actions">
                                    <form method="post" action="{{ route('admin.theme.section-preset') }}">
                                        @csrf
                                        <input type="hidden" name="section" value="{{ $sectionKey }}">
                                        <input type="hidden" name="template" value="{{ $templateKey }}">
                                        <input type="hidden" name="mode" value="preview">
                                        <button type="submit" class="admin-btn admin-btn-secondary">Önizle</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.theme.section-preset') }}" onsubmit="return confirm('{{ $template['name'] }} canlıya uygulanacak. Devam edilsin mi?')">
                                        @csrf
                                        <input type="hidden" name="section" value="{{ $sectionKey }}">
                                        <input type="hidden" name="template" value="{{ $templateKey }}">
                                        <input type="hidden" name="mode" value="publish">
                                        <button type="submit" class="admin-btn admin-btn-primary">Yayınla</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    <section class="admin-card mt-5 p-5 sm:p-7">
        <div class="admin-theme-code-head">
            <div>
                <p class="admin-dashboard-eyebrow">Gelişmiş görünüm</p>
                <h3>Güvenli Özel CSS</h3>
                <p>Buraya sadece CSS yazılabilir. Script, import ve tehlikeli CSS ifadeleri otomatik temizlenir. PHP/Blade kodu çalıştırılmaz.</p>
            </div>
            <span>{{ mb_strlen($customCss) }} / {{ $customCssMaxLength }}</span>
        </div>

        <form method="post" action="{{ route('admin.theme.custom-css') }}" class="admin-theme-code-form">
            @csrf
            <label class="sr-only" for="theme-custom-css">Özel CSS</label>
            <textarea
                id="theme-custom-css"
                name="custom_css"
                rows="12"
                maxlength="{{ $customCssMaxLength }}"
                spellcheck="false"
                placeholder=".shop-product-card { border-radius: 18px; }"
            >{{ old('custom_css', $customCss) }}</textarea>

            <div class="admin-theme-code-actions">
                <p>Yayınlarken mevcut CSS otomatik yedeklenir. Önizleme canlıya yazmadan session içinde çalışır.</p>
                <div>
                    <button type="submit" name="mode" value="preview" class="admin-btn admin-btn-secondary">Önizle</button>
                    <button type="submit" name="mode" value="publish" class="admin-btn admin-btn-primary">Yayınla</button>
                    <button type="submit" name="mode" value="reset" class="admin-btn admin-btn-secondary" onclick="return confirm('Özel CSS sıfırlanacak ve mevcut CSS yedeklenecek. Devam edilsin mi?')">Sıfırla</button>
                </div>
            </div>
        </form>
    </section>

    <form method="post" action="{{ route('admin.theme.update') }}" class="admin-card mt-5 p-5 sm:p-7">
        @csrf

        <div class="space-y-5">
            @foreach($groups as $groupTitle => $keys)
                <section class="admin-theme-section">
                    <div class="admin-theme-section__head">
                        <p class="admin-dashboard-eyebrow">Tema ayarları</p>
                        <h3>{{ $groupTitle }}</h3>
                    </div>
                    <div class="admin-theme-section__grid">
                        @foreach($keys as $key)
                            @continue(! isset($options[$key]))
                            <fieldset class="admin-theme-field">
                                <legend>{{ $labels[$key] ?? $key }}</legend>
                                <div class="admin-theme-options">
                                    @foreach($options[$key] as $value => $label)
                                        <label class="admin-theme-option">
                                            <input type="radio" name="{{ $key }}" value="{{ $value }}" @checked(($values[$key] ?? '') === $value)>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm leading-6 text-slate-500">
                <strong class="text-slate-900">Faz 7 kapsamı:</strong> Ana sayfa görünüm seçenekleri ve ana sayfa bölüm şablonları dahil edildi. Header/Footer içerik düzenlemeleri için yukarıdaki hızlı bağlantıları kullanın.
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" formaction="{{ route('admin.theme.preview') }}" formmethod="post" class="admin-btn admin-btn-secondary">
                    Önizle
                </button>
                <button type="submit" class="admin-btn admin-btn-primary">Yayınla</button>
            </div>
        </div>
    </form>

    <form method="post" action="{{ route('admin.theme.reset') }}" class="mt-4" onsubmit="return confirm('Tema ayarları varsayılan değerlere döndürülecek. Devam edilsin mi?')">
        @csrf
        <button type="submit" class="admin-btn admin-btn-secondary">Varsayılana dön</button>
    </form>
@endsection
