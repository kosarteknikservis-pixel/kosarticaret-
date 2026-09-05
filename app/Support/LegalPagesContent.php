<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Mağaza yasal / bilgilendirme sayfalarının kurumsal içerik kaynağı.
 * SiteSetting + config/kosar ile dinamik firma bilgisi kullanır.
 */
final class LegalPagesContent
{
    /**
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     meta_title: string,
     *     meta_description: string,
     *     content: string,
     *     sort_order: int,
     *     published: bool
     * }>
     */
    public static function all(): array
    {
        $c = self::company();

        return [
            self::hakkimizda($c),
            self::gizlilik($c),
            self::kvkk($c),
            self::kargoVeIade($c),
            self::mesafeliSatis($c),
            self::onBilgilendirme($c),
            self::cerez($c),
            self::kullanimKosullari($c),
            self::sss($c),
            self::iletisim($c),
        ];
    }

    /** @return array<string, string|float|int> */
    public static function company(): array
    {
        $email = trim((string) SiteSetting::get('contact_email', config('kosar.contact.email')));
        if ($email === '' || str_ends_with(strtolower($email), '.cm')) {
            $email = 'info@kosarticaret.com';
        }

        $address = trim((string) SiteSetting::get('contact_address', config('kosar.contact.address')));
        if ($address !== '' && ! preg_match('/İstanbul|Istanbul|Bursa|Türkiye/iu', $address)) {
            $address .= ', İstanbul';
        }

        $phone = trim((string) SiteSetting::get('contact_phone', config('kosar.contact.phone')));
        $whatsapp = preg_replace('/\D+/', '', (string) SiteSetting::get('contact_whatsapp', config('kosar.contact.whatsapp'))) ?: '';
        $whatsappDisplay = $whatsapp !== '' ? self::formatPhone($whatsapp) : '';

        return [
            'site_name' => SiteName::get(),
            'legal_name' => (string) (SiteSetting::get('legal_name') ?: config('kosar.legal_name')),
            'brand' => (string) config('kosar.name', 'Koşar'),
            'url' => Seo::siteUrl(),
            'phone' => $phone,
            'email' => $email,
            'whatsapp' => $whatsappDisplay,
            'address' => $address !== '' ? $address : 'İstanbul, Türkiye',
            'free_shipping_min' => (float) (SiteSetting::get('free_shipping_min') ?: config('kosar.free_shipping_min', 1000)),
            'return_days' => Seo::merchantReturnDays(),
            'cod_fee' => (float) config('shipping.cod_fee', 29.90),
            'express_fee' => (float) (config('shipping.shipping_rates.hizli') ?? 149.90),
            'vat_percent' => (int) round(((float) config('shipping.vat_rate', 0.20)) * 100),
        ];
    }

    private static function formatPhone(string $digits): string
    {
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            return '0'.substr($digits, 2, 3).' '.substr($digits, 5, 3).' '.substr($digits, 8, 2).' '.substr($digits, 10, 2);
        }

        return $digits;
    }

    private static function money(float $amount): string
    {
        return number_format($amount, $amount == floor($amount) ? 0 : 2, ',', '.').' TL';
    }

    private static function contactBlock(array $c): string
    {
        $lines = [
            '<strong>'.e($c['legal_name']).'</strong>',
            'Adres: '.e($c['address']),
            'Telefon: '.e($c['phone']),
        ];
        if ($c['whatsapp'] !== '') {
            $lines[] = 'WhatsApp: '.e($c['whatsapp']);
        }
        $lines[] = 'E-posta: <a href="mailto:'.e($c['email']).'">'.e($c['email']).'</a>';
        $lines[] = 'Web: <a href="'.e($c['url']).'">'.e(parse_url($c['url'], PHP_URL_HOST) ?: $c['url']).'</a>';

        return '<ul><li>'.implode('</li><li>', $lines).'</li></ul>';
    }

    /** @param  array<string, string|float|int>  $c */
    private static function hakkimizda(array $c): array
    {
        $name = e($c['site_name']);
        $legal = e($c['legal_name']);

        $content = <<<HTML
<p><strong>{$legal}</strong> (marka adı: {$name}), pompa, hidrofor, vantilatör ve teknik sulama/havalandırma ekipmanlarında Türkiye genelinde satış ve danışmanlık hizmeti sunan bir e-ticaret ve teknik tedarik firmasıdır.</p>

<h2>Ne yapıyoruz?</h2>
<p>Konut, tarım, ticari bina ve sanayi uygulamaları için <strong>dalgıç pompa, santrifüj pompa, kademeli pompa, hidrofor sistemleri</strong> ve <strong>endüstriyel / ev tipi vantilatör</strong> ürünlerini tek çatı altında sunuyoruz. Pedrollo, Sumak, Winpo, Ebara ve benzeri markaların modellerinde stoklu satış, hızlı sevkiyat ve ürün seçimi konusunda teknik destek sağlıyoruz.</p>

<h2>Neden {$name}?</h2>
<ul>
<li><strong>Teknik danışmanlık:</strong> Debi, basma yüksekliği, kurulum tipi ve kullanım amacına göre ürün önerisi</li>
<li><strong>Orijinal ürün:</strong> Markalı, garantili ekipman satışı</li>
<li><strong>Hızlı teslimat:</strong> Stoktan çıkan siparişlerde kısa sürede kargoya verme</li>
<li><strong>Şeffaf süreç:</strong> Kargo, iade ve mesafeli satış kurallarının açıkça paylaşılması</li>
</ul>

<h2>Hizmet kapsamı</h2>
<p>Ürün satışı, sipariş takibi, faturalandırma ve satış sonrası bilgilendirme hizmetlerimiz Türkiye genelindedir. Saha montajı her üründe standart paket kapsamında olmayabilir; gerektiğinde yetkili tesisatçı / servis yönlendirmesi yapılabilir.</p>

<h2>İletişim</h2>
<p>Sipariş, ürün seçimi ve teknik sorularınız için bize ulaşabilirsiniz:</p>
HTML;

        $content .= self::contactBlock($c);
        $content .= '<p>İletişim formu: <a href="'.e(route('contact.show')).'">'.e($c['url']).'/iletisim</a></p>';

        return [
            'slug' => 'hakkimizda',
            'title' => 'Hakkımızda',
            'meta_title' => $c['site_name'].' Hakkında | Pompa ve Hidrofor',
            'meta_description' => $c['legal_name'].' — dalgıç pompa, hidrofor ve vantilatörde teknik satış ve danışmanlık. Türkiye geneli hızlı teslimat.',
            'content' => $content,
            'sort_order' => 1,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function gizlilik(array $c): array
    {
        $legal = e($c['legal_name']);
        $email = e($c['email']);
        $kvkkUrl = e(route('pages.show', 'kvkk'));
        $cerezUrl = e(route('pages.show', 'cerez-politikasi'));

        $content = <<<HTML
<p>Bu Gizlilik Politikası, {$legal} tarafından işletilen <strong>kosarticaret.com</strong> internet sitesi üzerinden toplanan kişisel verilerin işlenmesine ilişkin genel bilgilendirmeyi içerir. Detaylı aydınlatma metni için <a href="{$kvkkUrl}">KVKK Aydınlatma Metni</a>ni inceleyiniz.</p>

<h2>Toplanan veriler</h2>
<p>Sipariş, üyelik, iletişim formu, bülten kaydı ve site kullanımı sırasında şu veriler işlenebilir:</p>
<ul>
<li>Kimlik ve iletişim bilgileri (ad, soyad, telefon, e-posta, adres)</li>
<li>Sipariş ve fatura bilgileri</li>
<li>Ödeme sürecine ilişkin işlem kayıtları (kart verileri ödeme kuruluşunda işlenir; sitemizde saklanmaz)</li>
<li>Teknik log, çerez ve cihaz verileri</li>
</ul>

<h2>İşleme amaçları</h2>
<ul>
<li>Sözleşmenin kurulması ve ifası (sipariş, teslimat, iade)</li>
<li>Müşteri destek ve teknik danışmanlık</li>
<li>Yasal yükümlülüklerin yerine getirilmesi (fatura, muhasebe)</li>
<li>Güvenlik, dolandırıcılık önleme ve site performansının iyileştirilmesi</li>
<li>Açık rızanız varsa pazarlama iletişimi</li>
</ul>

<h2>Verilerin paylaşımı</h2>
<p>Verileriniz; kargo firmaları, ödeme kuruluşları, muhasebe/e-fatura hizmetleri ve yasal zorunluluk halinde yetkili kamu kurumları ile yalnızca gerekli ölçüde paylaşılabilir. Veriler, hizmetin gerektirdiği süre ve mevzuatta öngörülen saklama süreleri boyunca muhafaza edilir.</p>

<h2>Çerezler</h2>
<p>Sitemizde zorunlu, işlevsel ve (onayınız varsa) analitik çerezler kullanılabilir. Ayrıntılar için <a href="{$cerezUrl}">Çerez Politikası</a> sayfasını ziyaret edin.</p>

<h2>Haklarınız</h2>
<p>6698 sayılı KVKK kapsamında; bilgilendirme, erişim, düzeltme, silme, itiraz ve şikâyet haklarınızı kullanabilirsiniz. Başvurularınızı <a href="mailto:{$email}">{$email}</a> adresine iletebilirsiniz.</p>

<h2>Güncelleme</h2>
<p>Bu politika gerektiğinde güncellenebilir. Güncel sürüm her zaman bu sayfada yayınlanır.</p>
HTML;

        return [
            'slug' => 'gizlilik-politikasi',
            'title' => 'Gizlilik Politikası',
            'meta_title' => 'Gizlilik Politikası | '.$c['site_name'],
            'meta_description' => $c['site_name'].' gizlilik politikası: kişisel verilerin toplanması, işlenmesi, saklanması ve haklarınız.',
            'content' => $content,
            'sort_order' => 2,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function kvkk(array $c): array
    {
        $legal = e($c['legal_name']);
        $email = e($c['email']);
        $phone = e($c['phone']);
        $address = e($c['address']);

        $content = <<<HTML
<p>6698 sayılı <strong>Kişisel Verilerin Korunması Kanunu</strong> (“KVKK”) kapsamında veri sorumlusu sıfatıyla {$legal} tarafından hazırlanan aydınlatma metnidir.</p>

<h2>1. Veri sorumlusu</h2>
<ul>
<li><strong>Unvan:</strong> {$legal}</li>
<li><strong>Adres:</strong> {$address}</li>
<li><strong>Telefon:</strong> {$phone}</li>
<li><strong>E-posta:</strong> <a href="mailto:{$email}">{$email}</a></li>
</ul>

<h2>2. İşlenen kişisel veriler</h2>
<p>Hizmetin niteliğine göre şu kategoriler işlenebilir: kimlik, iletişim, müşteri işlem, fatura/ödeme işlem kayıtları, talep/şikâyet, işlem güvenliği ve pazarlama (açık rıza varsa) verileri.</p>

<h2>3. İşleme amaçları ve hukuki sebepler</h2>
<ul>
<li>Mesafeli satış sözleşmesinin kurulması ve ifası (KVKK m.5/2-c)</li>
<li>Ürün teslimatı, iade ve müşteri destek süreçleri (KVKK m.5/2-c, f)</li>
<li>Fatura düzenleme ve mali mevzuat yükümlülükleri (KVKK m.5/2-ç)</li>
<li>Site güvenliği, log tutma ve dolandırıcılık önleme (KVKK m.5/2-f)</li>
<li>Açık rızaya dayalı kampanya / bülten iletişimi (KVKK m.5/1)</li>
</ul>

<h2>4. Aktarım</h2>
<p>Kişisel veriler; kargo ve lojistik firmaları, ödeme hizmeti sağlayıcıları, barındırma / e-posta / muhasebe hizmeti alınan iş ortakları ve talep halinde yetkili kamu kurumlarıyla, amaçla sınırlı olarak paylaşılabilir. Yurt dışına aktarım söz konusu olduğunda KVKK’nın aktarım hükümlerine uyulur.</p>

<h2>5. Saklama süresi</h2>
<p>Veriler; işleme amacının gerektirdiği süre, ilgili mevzuattaki zamanaşımı ve saklama yükümlülükleri (ör. vergi ve ticari defter süreleri) ile sınırlı olarak muhafaza edilir; süre sonunda silinir, yok edilir veya anonim hale getirilir.</p>

<h2>6. Haklarınız (KVKK m.11)</h2>
<p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, amacına uygun kullanılıp kullanılmadığını öğrenme, yurt içinde/yurt dışında aktarıldığı üçüncü kişileri bilme, düzeltilmesini veya silinmesini isteme, otomatik sistemlerle analiz sonucu aleyhinize bir sonucun çıkmasına itiraz etme ve kanuna aykırı işleme nedeniyle zararın giderilmesini talep etme haklarına sahipsiniz.</p>

<h2>7. Başvuru</h2>
<p>Başvurularınızı kimliğinizi doğrulayacak bilgilerle birlikte <a href="mailto:{$email}">{$email}</a> adresine veya yukarıdaki yazılı adrese iletebilirsiniz. Başvurular KVKK ve ilgili tebliğlerde öngörülen usule göre yanıtlanır.</p>
HTML;

        return [
            'slug' => 'kvkk',
            'title' => 'KVKK Aydınlatma Metni',
            'meta_title' => 'KVKK Aydınlatma Metni | '.$c['site_name'],
            'meta_description' => '6698 sayılı KVKK kapsamında '.$c['legal_name'].' kişisel veri aydınlatma metni: amaçlar, haklar ve başvuru.',
            'content' => $content,
            'sort_order' => 3,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function kargoVeIade(array $c): array
    {
        $free = self::money((float) $c['free_shipping_min']);
        $days = (int) $c['return_days'];
        $cod = self::money((float) $c['cod_fee']);
        $express = self::money((float) $c['express_fee']);
        $email = e($c['email']);
        $phone = e($c['phone']);
        $name = e($c['site_name']);

        $content = <<<HTML
<p>{$name} üzerinden verilen siparişlerin kargo, teslimat ve iade süreçleri bu sayfada özetlenmiştir. Mesafeli satışa ilişkin ayrıntılar için Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi de geçerlidir.</p>

<h2>Kargo ve teslimat</h2>
<ul>
<li><strong>Standart kargo:</strong> {$free} ve üzeri siparişlerde kargo ücretsizdir (kampanya dönemlerinde koşullar değişebilir).</li>
<li><strong>Teslimat süresi:</strong> Stoktaki ürünlerde sipariş genellikle 1–3 iş günü içinde kargoya verilir; teslimat bölgesine göre toplam süre yaklaşık 2–4 iş günüdür.</li>
<li><strong>Hızlı kargo:</strong> Öncelikli sevkiyat seçeneği mevcuttur (ücret: {$express}).</li>
<li><strong>Kapıda ödeme:</strong> Seçilmesi halinde ek hizmet bedeli {$cod} uygulanabilir.</li>
</ul>
<p>Kargo firması ve takip numarası siparişiniz kargoya verildiğinde e-posta / SMS ile bildirilir. Adres, telefon ve teslimat bilgilerinin doğru girilmesi müşteri sorumluluğundadır.</p>

<h2>Hasarlı / eksik teslimat</h2>
<p>Paket hasarlı geldiyse kargo görevlisi yanında tutanak tutturunuz ve aynı gün içinde bizi bilgilendiriniz. Eksik ürün veya yanlış ürün gönderiminde en kısa sürede <a href="mailto:{$email}">{$email}</a> veya {$phone} üzerinden iletişime geçin.</p>

<h2>Cayma / iade hakkı ({$days} gün)</h2>
<p>Mesafeli satış kapsamında, ürünü teslim aldığınız tarihten itibaren <strong>{$days} gün</strong> içinde herhangi bir gerekçe göstermeksizin cayma hakkınızı kullanabilirsiniz. Cayma bildirimi yazılı (e-posta) veya kalıcı veri saklayıcısı ile yapılmalıdır.</p>

<h2>İade koşulları</h2>
<ul>
<li>Ürün kullanılmamış, orijinal ambalajında, faturası / irsaliyesi ve varsa aksesuarlarıyla birlikte olmalıdır.</li>
<li>Hijyen / sağlık nedeniyle iadesi mevzuaten sınırlı ürünler, kişiye özel üretilmiş ürünler ve müşteri onayıyla açılan yazılım içeren ürünlerde cayma hakkı istisnaları uygulanabilir.</li>
<li>Teknik ekipmanlarda (pompa, hidrofor vb.) montaj / çalıştırma sonrası oluşan arızalar garanti sürecine tabidir; cayma hakkından bağımsız değerlendirilir.</li>
</ul>

<h2>İade süreci</h2>
<ol>
<li>{$email} adresine sipariş numarası ve iade talebinizi yazın.</li>
<li>Onay sonrası ürünü anlaşmalı kargo ile veya bildirilen adrese gönderin.</li>
<li>Ürün kontrolünden sonra bedel, ödeme yönteminize uygun şekilde mevzuattaki süreler içinde iade edilir.</li>
</ol>
<p>Cayma hakkının kullanılması halinde ürünün iade kargo ücreti, aksi kararlaştırılmadıkça alıcıya aittir (satıcı kusuru / ayıplı ürün halleri hariç).</p>

<h2>Garanti</h2>
<p>Ürünler üretici / ithalatçı garantisi kapsamında sunulur. Garanti süresi ve koşulları ürün sayfasında veya fatura / garanti belgesinde belirtilir. Kullanım hatası, yetkisiz müdahale veya uygun olmayan kurulum garanti dışı bırakılabilir.</p>
HTML;

        return [
            'slug' => 'kargo-ve-iade',
            'title' => 'Kargo ve İade',
            'meta_title' => 'Kargo ve İade Koşulları | '.$c['site_name'],
            'meta_description' => $free.' üzeri ücretsiz kargo, '.$days.' gün cayma hakkı. '.$c['site_name'].' kargo, teslimat ve iade politikası.',
            'content' => $content,
            'sort_order' => 4,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function mesafeliSatis(array $c): array
    {
        $legal = e($c['legal_name']);
        $address = e($c['address']);
        $phone = e($c['phone']);
        $email = e($c['email']);
        $days = (int) $c['return_days'];
        $url = e($c['url']);

        $content = <<<HTML
<p>İşbu Mesafeli Satış Sözleşmesi, {$legal} (“Satıcı”) ile site üzerinden sipariş veren kişi / kurum (“Alıcı”) arasında, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamında kurulur.</p>

<h2>1. Taraflar</h2>
<p><strong>Satıcı</strong></p>
HTML.self::contactBlock($c).<<<HTML
<p><strong>Alıcı:</strong> Sipariş formunda beyan edilen ad / unvan, adres, telefon ve e-posta bilgilerine sahip kişi veya kurumdur.</p>

<h2>2. Konu</h2>
<p>Sözleşmenin konusu; Alıcı’nın {$url} adresli internet sitesinden elektronik ortamda siparişini verdiği ürünün satışı ve teslimidir. Ürün temel nitelikleri, vergiler dâhil satış bedeli, ödeme ve teslimat bilgileri sipariş özeti ve Ön Bilgilendirme Formu’nda yer alır.</p>

<h2>3. Sözleşmenin kurulması</h2>
<p>Alıcı, siparişi onaylamadan önce Ön Bilgilendirme Formu ve işbu sözleşmeyi okuyup kabul eder. Siparişin tamamlanmasıyla sözleşme kurulmuş sayılır. Sipariş teyidi Alıcı’ya elektronik ortamda iletilir.</p>

<h2>4. Ödeme ve fatura</h2>
<p>Ödeme; kredi/banka kartı, havale/EFT veya kapıda ödeme (aktifse) yöntemleriyle yapılabilir. Fatura, Alıcı’nın bildirdiği fatura bilgilerine göre düzenlenir. Kartla ödemelerde 3D Secure güvenlik adımları uygulanabilir.</p>

<h2>5. Teslimat</h2>
<p>Ürün, stok durumuna ve seçilen kargo yöntemine göre makul sürede Alıcı’nın bildirdiği adrese gönderilir. Teslimatın gecikmesi mücbir sebep veya Alıcı kaynaklı nedenlere dayanıyorsa Satıcı sorumlu tutulamaz.</p>

<h2>6. Cayma hakkı</h2>
<p>Alıcı, ürünü teslim aldığı tarihten itibaren {$days} gün içinde cayma hakkını kullanabilir. Cayma bildirimi Satıcı’ya yazılı veya kalıcı veri saklayıcısıyla iletilir. İade koşulları “Kargo ve İade” sayfasında belirtilmiştir.</p>

<h2>7. Cayma hakkının istisnaları</h2>
<p>Yönetmelikte sayılan istisnalar (ör. tüketiciye özel üretilen mallar, çabuk bozulan mallar, sağlık/hijyen açısından iade uygun olmayan ve ambalajı açılmış ürünler vb.) saklıdır.</p>

<h2>8. Uyuşmazlık</h2>
<p>Şikâyet ve itirazlar için Satıcı iletişim kanalları kullanılır. Tüketici işlemlerinde, Alıcı’nın yerleşim yerindeki veya Satıcı’nın bulunduğu yerdeki Tüketici Hakem Heyetleri / Tüketici Mahkemeleri yetkilidir. Parasal sınırlar yürürlükteki mevzuata tabidir.</p>

<h2>9. Yürürlük</h2>
<p>Alıcı, site üzerinden siparişi onayladığında işbu sözleşmenin tüm koşullarını okuyup kabul etmiş sayılır.</p>
<p><em>Satıcı: {$legal} — {$address} — {$phone} — {$email}</em></p>
HTML;

        return [
            'slug' => 'mesafeli-satis-sozlesmesi',
            'title' => 'Mesafeli Satış Sözleşmesi',
            'meta_title' => 'Mesafeli Satış Sözleşmesi | '.$c['site_name'],
            'meta_description' => $c['legal_name'].' mesafeli satış sözleşmesi: taraflar, teslimat, ödeme ve '.$days.' gün cayma hakkı.',
            'content' => $content,
            'sort_order' => 5,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function onBilgilendirme(array $c): array
    {
        $legal = e($c['legal_name']);
        $days = (int) $c['return_days'];
        $free = self::money((float) $c['free_shipping_min']);
        $vat = (int) $c['vat_percent'];
        $kargoUrl = e(route('pages.show', 'kargo-ve-iade'));
        $mssUrl = e(route('pages.show', 'mesafeli-satis-sozlesmesi'));

        $content = <<<HTML
<p>6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, siparişinizi tamamlamadan önce aşağıdaki ön bilgilendirmeyi okuyunuz.</p>

<h2>Satıcı bilgileri</h2>
HTML.self::contactBlock($c).<<<HTML

<h2>Ürün ve bedel</h2>
<p>Sipariş konusu ürünün temel nitelikleri, vergiler dâhil toplam bedeli, varsa indirimler ve kargo ücreti sipariş sepeti / ödeme adımında gösterilir. Fiyatlara KDV (%{$vat}) dâhildir (aksi belirtilmedikçe).</p>

<h2>Ödeme yöntemleri</h2>
<ul>
<li>Kredi / banka kartı (3D Secure)</li>
<li>Havale / EFT</li>
<li>Kapıda ödeme (hizmet aktifse; ek ücret uygulanabilir)</li>
</ul>

<h2>Teslimat</h2>
<p>Teslimat, Alıcı’nın bildirdiği adrese kargo ile yapılır. Stoktan gönderimlerde kargoya verilme süresi genellikle 1–3 iş günüdür. {$free} ve üzeri siparişlerde standart kargo ücretsizdir. Ayrıntılar: <a href="{$kargoUrl}">Kargo ve İade</a>.</p>

<h2>Cayma hakkı</h2>
<p>Teslimattan itibaren <strong>{$days} gün</strong> içinde cayma hakkınız vardır. Cayma ve iade usulü Kargo ve İade sayfasında; sözleşme hükümleri <a href="{$mssUrl}">Mesafeli Satış Sözleşmesi</a>nde yer alır.</p>

<h2>Şikâyet ve uyuşmazlık</h2>
<p>Taleplerinizi satıcı iletişim kanallarından iletebilirsiniz. Tüketici şikâyetleri için Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</p>

<p><em>Bu form, {$legal} tarafından elektronik ortamda sunulmaktadır.</em></p>
HTML;

        return [
            'slug' => 'on-bilgilendirme',
            'title' => 'Ön Bilgilendirme Formu',
            'meta_title' => 'Ön Bilgilendirme Formu | '.$c['site_name'],
            'meta_description' => 'Mesafeli satış ön bilgilendirme: satıcı bilgileri, bedel, teslimat, ödeme ve '.$days.' gün cayma hakkı.',
            'content' => $content,
            'sort_order' => 6,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function cerez(array $c): array
    {
        $legal = e($c['legal_name']);
        $email = e($c['email']);
        $gizlilik = e(route('pages.show', 'gizlilik-politikasi'));

        $content = <<<HTML
<p>Bu Çerez Politikası, {$legal} tarafından işletilen kosarticaret.com sitesinde kullanılan çerezler hakkında bilgilendirme amacıyla hazırlanmıştır. Kişisel veri işleme detayları için <a href="{$gizlilik}">Gizlilik Politikası</a> ve KVKK Aydınlatma Metni geçerlidir.</p>

<h2>Çerez nedir?</h2>
<p>Çerezler; siteyi ziyaret ettiğinizde tarayıcınıza kaydedilen küçük metin dosyalarıdır. Oturum yönetimi, güvenlik, tercihlerinizin hatırlanması ve (onayınız varsa) analitik ölçüm için kullanılabilir.</p>

<h2>Kullandığımız çerez türleri</h2>
<ul>
<li><strong>Zorunlu çerezler:</strong> Sepet, oturum, güvenlik (CSRF) ve temel site işlevleri için gereklidir. Bunlar olmadan alışveriş süreci çalışmaz.</li>
<li><strong>İşlevsel çerezler:</strong> Dil, çerez tercihi gibi kullanıcı seçimlerini hatırlar.</li>
<li><strong>Analitik çerezler:</strong> Yalnızca onay verdiğinizde; site trafiği ve performansını ölçmek için kullanılabilir (ör. Google Analytics benzeri araçlar).</li>
</ul>

<h2>Çerez tercihleri</h2>
<p>İlk ziyaretinizde gösterilen çerez bildirimi üzerinden kabul veya red seçimi yapabilirsiniz. Tarayıcı ayarlarından mevcut çerezleri silebilir veya engelleyebilirsiniz; zorunlu çerezlerin engellenmesi site işlevlerini bozabilir.</p>

<h2>Üçüncü taraflar</h2>
<p>Ödeme sayfalarında ve entegre hizmetlerde (ödeme kuruluşu, kargo takip, harita vb.) ilgili üçüncü tarafların kendi çerez politikaları uygulanabilir.</p>

<h2>İletişim</h2>
<p>Çerezler hakkında sorularınız için: <a href="mailto:{$email}">{$email}</a></p>
HTML;

        return [
            'slug' => 'cerez-politikasi',
            'title' => 'Çerez Politikası',
            'meta_title' => 'Çerez Politikası | '.$c['site_name'],
            'meta_description' => $c['site_name'].' çerez politikası: zorunlu, işlevsel ve analitik çerezler, tercih yönetimi.',
            'content' => $content,
            'sort_order' => 7,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function kullanimKosullari(array $c): array
    {
        $legal = e($c['legal_name']);
        $url = e($c['url']);
        $email = e($c['email']);

        $content = <<<HTML
<p>kosarticaret.com internet sitesini (“Site”) ziyaret eden ve kullanan herkes aşağıdaki kullanım koşullarını kabul etmiş sayılır. Alışveriş işlemlerinde ayrıca Mesafeli Satış Sözleşmesi ve Ön Bilgilendirme Formu uygulanır.</p>

<h2>1. Genel</h2>
<p>Site, {$legal} tarafından işletilir. İçerikler bilgilendirme ve ürün tanıtımı amaçlıdır; teknik seçim kararları için profesyonel destek alınması önerilir.</p>

<h2>2. Hesap ve sipariş</h2>
<p>Üyelik veya sipariş sırasında verilen bilgilerin doğru ve güncel olması zorunludur. Kullanıcı, hesap güvenliğinden sorumludur. Sahte sipariş, kötüye kullanım veya dolandırıcılık şüphesinde sipariş iptal edilebilir.</p>

<h2>3. Fiyat ve stok</h2>
<p>Fiyatlar KDV dâhil gösterilir (aksi belirtilmedikçe). Stok ve fiyat hatalarında {$legal} siparişi iptal etme veya düzeltme hakkını saklı tutar; bu durumda Alıcı bilgilendirilir ve ödenen bedel iade edilir.</p>

<h2>4. Fikri mülkiyet</h2>
<p>Site tasarımı, metinler, logolar ve görseller {$legal} veya lisans verenlere aittir. İzinsiz kopyalama, çoğaltma veya ticari kullanım yasaktır.</p>

<h2>5. Sorumluluk sınırı</h2>
<p>Site içeriğinin kesintisiz ve hatasız olacağı garanti edilmez. Bağlantı verilen üçüncü taraf sitelerin içeriğinden {$legal} sorumlu değildir. Ürünlerin yanlış seçimi / hatalı montajından doğan zararlar, satıcı kusuru dışında kullanıcı / montajcı sorumluluğundadır.</p>

<h2>6. Uygulanacak hukuk</h2>
<p>İşbu koşullar Türkiye Cumhuriyeti hukukuna tabidir. Uyuşmazlıklarda Satıcı’nın bulunduğu yer mahkemeleri / tüketici uyuşmazlık mercileri yetkilidir.</p>

<h2>7. İletişim</h2>
<p>{$url} — <a href="mailto:{$email}">{$email}</a></p>
HTML;

        return [
            'slug' => 'kullanim-kosullari',
            'title' => 'Kullanım Koşulları',
            'meta_title' => 'Kullanım Koşulları | '.$c['site_name'],
            'meta_description' => $c['site_name'].' web sitesi kullanım koşulları: hesap, sipariş, fiyat, fikri mülkiyet ve sorumluluk.',
            'content' => $content,
            'sort_order' => 8,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function sss(array $c): array
    {
        $free = self::money((float) $c['free_shipping_min']);
        $days = (int) $c['return_days'];
        $email = e($c['email']);
        $phone = e($c['phone']);
        $contact = e(route('contact.show'));
        $kargo = e(route('pages.show', 'kargo-ve-iade'));

        $content = <<<HTML
<p>Sipariş, kargo, iade ve ürün seçimi hakkında en sık sorulan sorular.</p>

<h2>Sipariş ve ödeme</h2>
<p><strong>Hangi ödeme yöntemlerini kullanabilirim?</strong><br>
Kredi/banka kartı, havale/EFT ve (aktifse) kapıda ödeme seçenekleri sunulur. Kart ödemelerinde 3D Secure kullanılır.</p>
<p><strong>Faturamı nasıl alırım?</strong><br>
Siparişte girdiğiniz bilgilere göre e-fatura / e-arşiv düzenlenir. Kurumsal fatura için vergi dairesi ve vergi numarası alanlarını doldurun.</p>

<h2>Kargo</h2>
<p><strong>Kargo ücretsiz mi?</strong><br>
{$free} ve üzeri siparişlerde standart kargo ücretsizdir. Detaylar: <a href="{$kargo}">Kargo ve İade</a>.</p>
<p><strong>Siparişim ne zaman kargoya verilir?</strong><br>
Stoktaki ürünlerde genellikle 1–3 iş günü içinde kargoya verilir. Takip numarası e-posta veya SMS ile iletilir.</p>

<h2>İade</h2>
<p><strong>İade süresi nedir?</strong><br>
Teslimattan itibaren {$days} gün içinde cayma hakkınızı kullanabilirsiniz. Ürünün kullanılmamış ve ambalajında olması gerekir.</p>
<p><strong>İade talebini nasıl ileteyim?</strong><br>
Sipariş numaranızla birlikte <a href="mailto:{$email}">{$email}</a> adresine yazın veya <a href="{$contact}">iletişim formu</a>nu kullanın.</p>

<h2>Ürün ve teknik destek</h2>
<p><strong>Hangi pompayı seçeceğimi bilmiyorum.</strong><br>
Debi, basma yüksekliği, kuyu derinliği veya kullanım alanınızı paylaşın; teknik ekibimiz yönlendirir. Telefon: {$phone}</p>
<p><strong>Montaj hizmeti var mı?</strong><br>
Standart satış montaj içermeyebilir. İhtiyaca göre yetkili tesisatçı / servis yönlendirmesi yapılabilir.</p>

<h2>Hesap</h2>
<p><strong>Siparişimi nasıl takip ederim?</strong><br>
Site üzerindeki sipariş takip sayfasını veya hesabınızdaki sipariş detayını kullanabilirsiniz.</p>
HTML;

        return [
            'slug' => 'sss',
            'title' => 'Sıkça Sorulan Sorular',
            'meta_title' => 'Sıkça Sorulan Sorular (SSS) | '.$c['site_name'],
            'meta_description' => 'Sipariş, kargo, iade, ödeme ve pompa seçimi hakkında '.$c['site_name'].' SSS yanıtları.',
            'content' => $content,
            'sort_order' => 9,
            'published' => true,
        ];
    }

    /** @param  array<string, string|float|int>  $c */
    private static function iletisim(array $c): array
    {
        $form = e(route('contact.show'));

        $content = '<p>Sipariş, teknik danışmanlık ve destek talepleriniz için aşağıdaki kanallardan bize ulaşabilirsiniz.</p>'
            .self::contactBlock($c)
            .'<p><strong>İletişim formu:</strong> Mesaj bırakmak için <a href="'.$form.'">iletişim sayfasını</a> kullanın.</p>'
            .'<p>Çalışma saatleri: Hafta içi 09:00–18:00 (resmi tatiller hariç). Mesajlarınıza en kısa sürede dönüş yapılır.</p>';

        return [
            'slug' => 'iletisim',
            'title' => 'İletişim Bilgileri',
            'meta_title' => 'İletişim Bilgileri | '.$c['site_name'],
            'meta_description' => $c['legal_name'].' iletişim: telefon, e-posta, adres ve iletişim formu.',
            'content' => $content,
            'sort_order' => 10,
            'published' => true,
        ];
    }
}
