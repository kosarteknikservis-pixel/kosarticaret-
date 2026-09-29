<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = 'İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.';

    private const FAQ = 'Evet. İstanbul içinde kurulum ve teknik servis desteği veriyoruz; diğer bölgeler için bizimle iletişime geçebilirsiniz.';

    private const SHORT = 'İstanbul içinde teknik servis desteği veriyoruz; diğer bölgeler için bizimle iletişime geçebilirsiniz.';

    private const FORM = 'Teknik sorularınız, kurulum ve bakım ihtiyaçlarınız için <a href="/iletisim">iletişim formu</a> ile bize ulaşabilirsiniz. İstanbul içinde kurulum ve teknik servis desteği veriyoruz.';

    private const JET = 'İstanbul içinde Kaysu jet hidroforlar için kurulum, periyodik bakım ve arıza onarımı hizmeti veriyoruz. Randevu ve yedek parça talepleri için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>';

    private const PARCA = 'İstanbul içinde bakım ve teknik servis desteği veriyoruz; yedek parça talepleri için bizimle iletişime geçebilirsiniz.';

    private const CSS_PRODUCT_OUTSIDE_SCOPE = 1404;

    /** @var array<int, list<string>> */
    public array $misses = [];

    public function up(): void
    {
        $ids = DB::table('products')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where('brands.slug', 'kaysu')
            ->pluck('products.id')
            ->all();
        $ids[] = self::CSS_PRODUCT_OUTSIDE_SCOPE;

        $rows = DB::table('products')->whereIn('id', $ids)->get(['id', 'description']);

        $changed = 0;
        foreach ($rows as $row) {
            $old = (string) $row->description;
            if (trim($old) === '') {
                continue;
            }

            $new = (int) $row->id === self::CSS_PRODUCT_OUTSIDE_SCOPE
                ? $this->stripCssTail($old)
                : $this->clean((int) $row->id, $old);

            if ($new !== $old) {
                DB::table('products')->where('id', $row->id)->update([
                    'description' => $new,
                    'updated_at' => now(),
                ]);
                $changed++;
            }
        }

        if ($changed > 0) {
            app(UrlIndexingNotifier::class)->clearSitemapCache();
        }
    }

    public function down(): void
    {
        // Metin temizliği geri alınmaz.
    }

    private function clean(int $id, string $html): string
    {
        $html = preg_replace('/<h2[^>]*id="icindekiler"[^>]*>[^<]*<\/h2>\s*<ul\b[^>]*>(?:(?!<\/?ul\b|<h[1-6]\b).)*<\/ul>\s*/su', '', $html) ?? $html;
        $html = $this->stripCssTail($html);
        $html = preg_replace('/\s*&#x1f3e2;\s*Ana Sayfa\s*&#x1f6e0;(?:&#xfe0f;)?\s*Yetkili (?:Teknik )?Servis\s*$/u', '', $html) ?? $html;

        foreach ($this->blockRemovals()[$id] ?? [] as $pattern) {
            $html = $this->removeBlock($id, $html, $pattern);
        }

        $html = preg_replace('/\s+style\s*=\s*"[^"]*"/iu', '', $html) ?? $html;
        $html = preg_replace("/\s+style\s*=\s*'[^']*'/iu", '', $html) ?? $html;
        $html = preg_replace('/(?:&#x(?:1f[0-9a-f]{3}|2[67][0-9a-f]{2}|fe0f);)+\s?/iu', '', $html) ?? $html;
        $html = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]+\s?/u', '', $html) ?? $html;

        foreach ($this->swaps()[$id] ?? [] as [$from, $to]) {
            $html = $this->swap($id, $html, $from, $to);
        }

        do {
            $before = $html;
            $html = preg_replace('/<(p|h2|h3|h4|li|strong|b|em)\b[^>]*>\s*<\/\1>\s*/u', '', $html) ?? $html;
        } while ($html !== $before);

        return $html;
    }

    private function stripCssTail(string $html): string
    {
        $pos = strpos($html, '.faq-details-group');
        if ($pos === false) {
            return $html;
        }

        $tail = strip_tags(substr($html, $pos));
        if (preg_match('/[ğüşöçıİĞÜŞÖÇ]/u', $tail) || ! str_contains($tail, '{')) {
            $this->misses[0][] = 'CSS sonrası içerik var, dokunulmadı';

            return $html;
        }

        return rtrim(substr($html, 0, $pos));
    }

    private function removeBlock(int $id, string $html, string $pattern): string
    {
        $new = preg_replace($pattern, '', $html, -1, $count) ?? $html;
        if ($count === 0) {
            $this->misses[$id][] = 'blok: '.$pattern;
        }

        return $new;
    }

    private function swap(int $id, string $html, string $from, string $to): string
    {
        $pattern = '/'.str_replace(' ', '\s+', preg_quote($from, '/')).'/u';
        $new = preg_replace_callback($pattern, static fn () => $to, $html, -1, $count) ?? $html;
        if ($count === 0) {
            $this->misses[$id][] = 'cümle: '.mb_substr($from, 0, 80);
        }

        return $new;
    }

    /** @return array<int, list<string>> */
    private function blockRemovals(): array
    {
        return [
            1363 => ['/\s*<h2[^>]*display:\s*none;?[^>]*>Ürün Tanıtım Videosu<\/h2>/u'],
            1371 => ['/\s*<h2 id="diger"[^>]*>\s*İlgili Ürünler ve Kategoriler\s*<\/h2>\s*<p[^>]*>\s*HMC145-6SH\s+Paket\s+Hidrofor\s+\(8\s+Kat\s+\/\s+18\s+Daire\)\s*(?:•|&bull;)\s*Hidrofor\s+Servisi\s*<\/p>/u'],
            1379 => ['/<h2 id="dsk22-video"[^>]*>Ürün Tanıtım Videosu<\/h2>\s*Bu ürün için video içeriği yakında eklenecektir\.\s*/u'],
            1412 => ['/<h2 id="teknik-detay-gorseli"[^>]*>Teknik Detay Görseli<\/h2>\s*/u'],
        ];
    }

    /** @return array<int, list<array{0: string, 1: string}>> */
    private function swaps(): array
    {
        $jetLink = [
            '<strong>Daha fazla Kaysu jet hidrofor ve apartman hidroforu için:</strong> Tüm Jet Hidrofor Modelleri</p>',
            '<strong>Daha fazla Kaysu jet hidrofor ve apartman hidroforu için:</strong> <a href="https://kosarticaret.com/marka/kaysu" rel="noopener noreferrer">Tüm Kaysu Modelleri</a></p>',
        ];
        $motor = [
            'Sonuç: Motor %60 daha az çalışır, daha az ısınır ve elektrik faturanız düşer.',
            'Sonuç: Motor daha seyrek devreye girer, daha az ısınır ve elektrik tüketimi azalır.',
        ];
        $kosarServis = 'Evet. KOŞAR dalgıç pompası servis ekibi keşif, kurulum ve periyodik bakımda yanınızdadır.';
        $servisListe = ' • Dalgıç pompa servisi — yerinde keşif, arıza–onarım ve periyodik bakım desteği';

        return [
            1354 => [
                ['Kaysu SP750-A, pratik ve taşınabilir yapısıyla benzer güç sınıfındaki pek çok ürün arasında öne çıkar.', 'Kaysu SP750-A, pratik ve taşınabilir bir yapıya sahiptir.'],
            ],
            1355 => [
                ['Kurulum ve servis desteği için dalgıç pompası teknik servis sayfamızdan destek alabilirsiniz.', self::CONTACT],
            ],
            1356 => [
                ['Yerinde keşif, arıza tespiti ve periyodik bakım için profesyonel destek alabilirsiniz: Dalgıç Pompası Teknik Servis.', self::CONTACT],
            ],
            1357 => [
                ['Yerinde keşif, arıza tespiti ve planlı bakım için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz. Uzmanlarımız, debi düşüşü, sık devreye girme, şamandıra/panel sorunları ve tıkanma problemlerinde hızlı çözüm sunar.', self::CONTACT],
                ['Profesyonel montaj ve devreye alma için dalgıç pompası teknik servis sayfamızdan randevu oluşturabilirsiniz.', ''],
                ['Var. Yerinde keşif ve planlı bakım için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz.', self::FAQ],
            ],
            1358 => [
                ['Yerinde keşif, arıza tespiti ve periyodik bakım için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz. Uzman ekip, bıçak/çark aşınması, şamandıra/panel sorunları ve performans kayıplarında hızlı çözüm sunar.', self::CONTACT],
                ['Profesyonel montaj/ayar ve garantiye uygun devreye alma için dalgıç pompası teknik servis sayfamızdan randevu oluşturabilirsiniz.', ''],
                ['Var. dalgıç pompası teknik servis ekiplerimiz yerinde keşif, kurulum ve bakım sağlar.', self::FAQ],
                ['Proje ve toplu alımlarda doğru boyutlandırma için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz.', 'Proje ve toplu alımlarda doğru boyutlandırma için bizimle iletişime geçebilirsiniz.'],
            ],
            1359 => [
                ['Profesyonel kurulum, keşif ve periyodik bakım için su pompası teknik servis ekibimizden destek alabilirsiniz.', self::CONTACT],
                ['Proje bazlı teklif ve teknik yardım için su pompası teknik servis ekibimizle iletişime geçebilirsiniz.', 'Proje bazlı teklif ve teknik yardım için bizimle iletişime geçebilirsiniz.'],
            ],
            1360 => [
                ['Kurulum, bakım ve yerinde servis desteği için su pompası teknik servis ekibimizle iletişime geçebilirsiniz.', self::CONTACT],
                ['Profesyonel keşif ve montaj desteği için su pompası teknik servis sayfamızı ziyaret edin.', ''],
                ['Servis desteği için su pompası teknik servis sayfamızı kullanabilirsiniz.', self::SHORT],
            ],
            1361 => [
                ['iş sürekliliğini garanti altına alır.', 'iş sürekliliğine katkı sağlar.'],
                ['Kurulum ve servis desteği için dalgıç pompası teknik servis veya kısa yol için Kosar Teknik Servis sayfalarını ziyaret edebilirsiniz.', self::CONTACT],
            ],
            1362 => [
                ['Yerinde keşif, arıza tespiti ve periyodik bakım için profesyonel destek almak isterseniz Dalgıç Pompası Teknik Servis sayfamız üzerinden ekiplerimizle iletişime geçebilirsiniz.', self::CONTACT],
            ],
            1363 => [
                ['Yerinde keşif, arıza tespiti ve periyodik bakım için tek adresten profesyonel destek alabilirsiniz: Dalgıç Pompası Teknik Servis.', self::CONTACT],
            ],
            1364 => [
                ['Yerinde keşif, arıza tespiti ve düzenli bakım için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz. Uzmanlarımız; debi düşüşü, sık devreye girme, aşırı akım, şamandıra/panel arızaları ve hidrolik tıkanma problemlerini sahada çözümler.', self::CONTACT],
                ['Profesyonel montaj ve devreye alma için dalgıç pompası teknik servis sayfamızdan randevu oluşturabilirsiniz.', ''],
                ['Var. Keşif, bakım ve arızalarda dalgıç pompası teknik servis sayfasından destek alabilirsiniz.', self::FAQ],
            ],
            1365 => [
                ['Yerinde keşif, arıza tespiti ve periyodik bakımlar için dalgıç pompası teknik servis ekibimizden destek alabilirsiniz. Uzman ekipler; bıçak/çark aşınması, aşırı akım arızaları, şamandıra/panel sorunları ve hidrolik performans kayıplarını yerinde teşhis eder.', self::CONTACT],
                ['Profesyonel keşif ve montaj desteği için dalgıç pompası teknik servis sayfamızdan randevu oluşturabilirsiniz.', ''],
                ['Evet. Kurulum ve kullanım talimatlarına uygun işletimde, yerinde servis ve bakım için dalgıç pompası teknik servis ekibimiz hizmet verir.', 'Evet. Talimatlara uygun kurulum ve kullanımda garanti geçerlidir. İstanbul içinde kurulum ve teknik servis desteği veriyoruz; diğer bölgeler için bizimle iletişime geçebilirsiniz.'],
            ],
            1366 => [
                ['Profesyonel keşif, montaj ve periyodik servis için hidrofor teknik servis ekibimizden destek alabilirsiniz.', self::CONTACT],
            ],
            1367 => [
                ['Profesyonel kurulum, keşif ve periyodik bakım için su pompası teknik servis ekibimizden destek alabilirsiniz.', self::CONTACT],
            ],
            1368 => [
                ['Yetkili desteğe ihtiyaç duyarsanız dalgıç pompası teknik servis ekibimizden destek alabilirsiniz.', self::CONTACT],
                ['Servis için yukarıdaki teknik servis bağlantımızı kullanabilirsiniz.', self::SHORT],
            ],
            1369 => [
                ['Profesyonel keşif, montaj ve periyodik bakım için <a href="https://kosarticaret.com/kategoriler/hidrofor-sistemleri/hidroforlar" rel="noopener noreferrer">hidrofor</a> &amp; su pompası teknik servis ekibimizden destek alabilirsiniz.', 'İstanbul içinde <a href="https://kosarticaret.com/kategoriler/hidrofor-sistemleri/hidroforlar" rel="noopener noreferrer">hidrofor</a> ve su pompası kurulumu ile teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.'],
            ],
            1371 => [
                ['Profesyonel destek için KOŞAR’ın Hidrofor Servisi ekibi; keşif, kurulum, periyodik bakım ve arıza çözümünü uçtan uca yönetir.', self::CONTACT],
            ],
            1377 => [
                ['Yerinde destek ve arıza/kurulum talepleri için dalgıç pompası teknik servis ekibimizden yardım alabilirsiniz.', self::CONTACT],
                ['Evet. Teknik servis kanalımız üzerinden arıza kaydı ve orijinal yedek parça talepleri oluşturabilirsiniz.', self::FAQ],
            ],
            1378 => [
                ['Yetkili destek için dalgıç pompası teknik servis ekibimizden yardım alabilirsiniz.', self::CONTACT],
            ],
            1379 => [
                ['hidrofor teknik servis ekibimiz profesyonel teşhis ve onarım desteği sağlar.', 'İstanbul içinde teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.'],
                ['<p>Profesyonel kurulum/arıza teşhis desteği için hidrofor teknik servis ekibimizle iletişime geçebilirsiniz.</p>', ''],
            ],
            1386 => [
                ['Kaysu WATER BENDER 1-6 ile ortalama %30’a kadar elektrik tasarrufu elde edilir.', 'Kaysu WATER BENDER 1-6, pompanın yalnızca ihtiyaç anında çalışmasını sağlayarak gereksiz elektrik tüketimini azaltır.'],
                ['Kaysu tarafından 2 yıl resmi garanti ile sunulmaktadır.', '2 yıl üretici garantisi ile sunulmaktadır.'],
                ['Evet, Türkiye geneli teknik servis ve orijinal yedek parça desteği mevcuttur.', 'Evet. İstanbul içinde teknik servis desteği veriyoruz; orijinal yedek parça talepleri için bizimle iletişime geçebilirsiniz.'],
                ['Tüm teknik soru, kurulum ve bakım ihtiyaçlarınızda teknik servis iletişim formu ile bize ulaşabilir, Kaysu’nun uzman ekibinden destek alabilirsiniz.', self::FORM],
            ],
            1387 => [
                ['Kaysu hidrofor basınç şalterleri 2 yıl resmi garantilidir.', 'Kaysu hidrofor basınç şalterleri 2 yıl üretici garantilidir.'],
                ['Montaj, bakım ve teknik danışmanlık için teknik servis iletişim formunu doldurabilirsiniz. Kaysu uzmanları her türlü sorunuzda yanınızda.', self::FORM],
            ],
            1388 => [
                ['sistemdeki suyun her zaman yeterli basınçta kalmasını garanti eder.', 'sistemdeki suyun ayarlanan basınç aralığında kalmasını sağlar.'],
                ['2 yıl resmi Kaysu Türkiye garantilidir.', '2 yıl üretici garantilidir.'],
                ['Kurulum, bakım ve destek için teknik servis iletişim formunu doldurabilir, Kaysu uzman ekibinden yardım alabilirsiniz.', self::FORM],
            ],
            1402 => [
                ['Bronz çark, bakır motor ve kaliteli malzeme yapısı ile ortalama 10+ yıl verimli çalışabilir.', 'Bronz çark ve bakır sargılı motor yapısı sayesinde düzenli bakımla uzun yıllar kullanılabilir.'],
                ['Daha fazla teknik destek için Kosar teknik servis hizmetimizi ziyaret edebilirsiniz.', ''],
                ["Kosar hidrofor teknik servis, kurulum, bakım ve yedek parça desteği ile tüm Türkiye'de hizmet sunar. Yetkili servis üzerinden uzman desteğe ulaşabilirsiniz.", 'İstanbul içinde kurulum, bakım ve teknik servis desteği veriyoruz. Diğer bölgeler ve yedek parça talepleri için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.'],
            ],
            1406 => [
                ['Böylece pompanız yanmaktan %100 korunur.', 'Böylece pompanın susuz çalışarak zarar görmesi önlenir.'],
                ['2 yıl <a href="https://kosarticaret.com/marka/kosar" rel="noopener noreferrer">Koşar</a> Ticaret garantisi altındadır. Yerli bir marka olduğu için yedek parça sorunu yaşanmaz, servisi boldur ve parçaları çok ekonomiktir.', '2 yıl üretici garantisi altındadır. Yerli bir marka olduğu için yedek parçaya erişim kolaydır. İstanbul içinde kurulum ve teknik servis desteği veriyoruz.'],
            ],
            1407 => [
                ['Ekstra flatör takmanıza gerek kalmadan motorunuz yanmaktan %100 korunur.', 'Ekstra flatör takmanıza gerek kalmadan motor susuz çalışmaya karşı korunur.'],
                ['Motor sargıları %100 bakırdır.', 'Motor sargıları bakırdır.'],
            ],
            1409 => [
                ['sorununa kesin çözüm sunmak için geliştirilmiştir.', 'sorununu çözmek için geliştirilmiştir.'],
                ['Ayrıca, yaygın servis ağı ve orijinal yedek parça desteğiyle, kullanıcıların bakım ve onarım süreçleri kolay ve güvenli şekilde gerçekleştirilir.', 'Orijinal yedek parça desteği sayesinde bakım ve onarım süreçleri kolaylaşır.'],
                ['Orijinal yedek parça ve uzman servis desteğiyle, hidroforun uzun ömürlü performansı garanti altına alınır.', 'Orijinal yedek parça kullanımı ve düzenli bakım, hidroforun uzun ömürlü çalışmasına katkı sağlar.'],
                ['Kaysu Pompa jet hidrofor kullanıcılarına başta olmak üzere Türkiye genelinde uzman teknik servis desteği sunulmaktadır. Kurulum, periyodik bakım, arıza onarımı ve yedek parça ihtiyaçlarınız için Kaysu Hidrofor Teknik Servis sayfasından hızlıca randevu oluşturabilirsiniz. Deneyimli teknik ekipler, arıza durumunda yerinde müdahale ederek, sorunun kısa sürede çözülmesini sağlar ve cihazınızın uzun ömürlü şekilde çalışmasını garanti altına alır', self::JET],
                ['Yetkili Kaysu Hidrofor teknik servisinden yedek parça ve bakım hizmetleri için buraya tıklayın.', self::PARCA],
                $jetLink,
            ],
            1410 => [
                ['ise 4 daireli binalarda ve küçük işletmelerde su basıncı sorununun kesin çözümüdür. Güçlü teknik servis ve yaygın yedek parça desteğiyle öne çıkar.', 'ise 4 daireli binalarda ve küçük işletmelerde su basıncı sorunu için uygun bir çözümdür.'],
                ['Kaysu Pompa jet hidrofor kullanıcılarına başta olmak üzere tüm Türkiye’de uzman teknik servis desteği sunulmaktadır. Kurulum, bakım ve arıza desteği için Kaysu Hidrofor Teknik Servis sayfamızdan randevu oluşturabilirsiniz. Ayrıca Kaysu teknik servisimizin tanıtım ve montaj videosunu izleyebilirsiniz:', self::JET.'.'],
                ['Yetkili Kaysu Hidrofor teknik servisimizden tüm destek ve yedek parça temini için buraya tıklayın.', self::PARCA],
                $jetLink,
            ],
            1412 => [
                ['Daha fazla teknik bilgi veya servis desteği için Kosar Hidrofor Teknik Servis bağlantısına tıklayabilirsiniz.', 'Teknik bilgi ve İstanbul içi servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.'],
                ['teknolojisi, yüksek verim ve enerji tasarrufu odaklı yeni nesil hidrofor sistemleriyle sektörde liderdir.', 'teknolojisi, yüksek verim ve enerji tasarrufu odaklı yeni nesil hidrofor sistemleri sunar.'],
                ['Kaysu pompa mühendisliği sayesinde, suyun verimli dağılımı ve minimum elektrik tüketimi garanti altına alınmıştır.', 'Kaysu pompa mühendisliği, suyun verimli dağılımını ve düşük elektrik tüketimini hedefler.'],
                ['Kaysu hidrofor bakımı ve arıza durumunda Kosar Teknik Servis hizmetiyle profesyonel destek alabilirsiniz.', 'İstanbul içinde Kaysu hidrofor bakımı ve arıza onarımı için teknik servis desteği veriyoruz.'],
                ['ile 8 kat 18 daire hidrofor sistemlerinde %100 başarı', 'ile 8 kat 18 daireye kadar binalar için uygun kapasite'],
                ['Uzun ömür, düşük enerji, sessiz çalışma ve yaygın servis ağı ile fark yaratır.', 'Uzun ömür, düşük enerji tüketimi ve sessiz çalışma ile öne çıkar.'],
                ['Her türlü bakım ve arıza için Kosar teknik servis hizmetinizdedir.', 'İstanbul içinde bakım ve arıza onarımı için teknik servis desteği veriyoruz; diğer bölgeler için bizimle iletişime geçebilirsiniz.'],
            ],
            1413 => [
                ['Profesyonel destek için <a href="https://kosarticaret.com/marka/kosar" rel="noopener noreferrer">KOŞAR</a> dalgıç pompası teknik servis ekibi keşif, kurulum, arıza–onarım ve periyodik bakım sözleşmeleriyle yanınızdadır.', self::CONTACT],
                [$kosarServis, self::FAQ],
                [$servisListe, ''],
            ],
            1414 => [
                ['Kurulum ve servis için <a href="https://kosarticaret.com/marka/kosar" rel="noopener noreferrer">KOŞAR</a>’ın dalgıç pompası teknik servis ekibi; keşif, devreye alma, arıza–onarım ve periyodik bakım sözleşmeleriyle yanınızdadır.', self::CONTACT],
                [$kosarServis, self::FAQ],
                [$servisListe, ''],
            ],
            1415 => [
                $motor,
            ],
            1416 => [
                ['Kesin Çözüm: HKJM15H', 'Çözüm: HKJM15H'],
                ['Suya asla pas karışmaz, ailenizin sağlığı korunur.', 'Paslanmaz mil, suya pas karışmasını önler.'],
                ["Hidrofor arızalarının %70'i mil paslanmasından kaynaklanır.", 'Mil paslanması, hidroforlarda sık görülen arıza nedenlerinden biridir.'],
                ['Birçok model 24 litre tank ile satılırken, <strong>HKJM15H</strong> 50 litrelik devasa bir tanka sahiptir.', '<strong>HKJM15H</strong>, 50 litrelik tanka sahiptir.'],
                $motor,
                ['ürününden %100 verim almak için montajın doğru yapılması hayati önem taşır.', 'ürününden tam verim almak için montajın doğru yapılması önemlidir.'],
                ['<a href="https://kosarticaret.com/marka/kosar" rel="noopener noreferrer">Koşar</a> Ticaret ve Kaysu garantisi altındadır. Türkiye genelinde yaygın bir yetkili servis ağı mevcuttur.', 'üretici garantisi altındadır. İstanbul içinde kurulum ve teknik servis desteği veriyoruz.'],
            ],
        ];
    }
};
