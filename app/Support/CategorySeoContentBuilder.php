<?php

namespace App\Support;

use App\Models\Category;

final class CategorySeoContentBuilder
{
    /**
     * @return array{description: string, buying_guide: string, subtitle: string}|null
     */
    public function build(Category $category): ?array
    {
        $path = $category->nestedSlugPath();
        $facts = CategorySeoFacts::forPath($path);
        if ($facts === null) {
            return null;
        }

        $name = e((string) $category->name);
        $hook = $this->escape($facts['hook']);
        $mistake = $this->escape($facts['mistake']);
        $uses = $this->list($facts['uses']);
        $criteria = $this->list($facts['criteria']);
        $related = $this->relatedLinks($facts['related']);
        $brandNote = e($facts['brands_note'] ?? 'Koşar Ticaret, orijinal ürünleri teknik verileriyle sunar; model seçerken uygulamanıza ait debi, basınç, ölçü ve çalışma koşullarını paylaşarak teknik seçim desteği alabilirsiniz.');
        $blog = isset($facts['blog'])
            ? '<p>Daha ayrıntılı teknik arka plan için <a href="'.e($facts['blog']['href']).'">'.e($facts['blog']['label']).'</a> içeriğini de inceleyebilirsiniz.</p>'
            : '';
        $table = $this->table($facts['table'] ?? []);
        $variant = abs(crc32($path)) % 3;
        $bridge = $this->bridgeParagraph($variant);
        $scenario = $this->scenarioParagraph($variant);
        $measure = $this->measureParagraph($variant);
        $ops = $this->opsParagraph($variant);
        $pack = $this->packParagraph($variant, $related);
        $serviceNote = $path === 'hidrofor-sistemleri'
            ? '<p>Satın alma sonrası kurulum, arıza teşhisi veya saha servisi gerektiğinde İstanbul merkezli teknik servis ağımız için <a href="https://kosar.net.tr/" rel="noopener">KOŞAR Profesyonel Teknik Servis</a> sayfasını inceleyebilirsiniz; ürün seçimi ve stok için bu mağaza sayfası geçerlidir.</p>'
            : '';

        $closing = isset($facts['closing'])
            ? (string) $facts['closing']
            : '<p>'.$brandNote.' Uygun modeli kendi verilerinizle filtrelemek için <a href="/pompa-secici">Pompa Seçici</a> aracını kullanabilir veya ölçülerinizi <a href="/iletisim">iletişim sayfasından</a> iletebilirsiniz. Koşar Ticaret stokundan siparişte orijinal ürün, teknik özellik karşılaştırması ve 1.000 TL üzeri ücretsiz kargo seçenekleriyle ilerlersiniz; emin olmadığınız noktada WhatsApp veya telefon üzerinden debi, basma yüksekliği ve bağlantı ölçülerinizi paylaşmanız yeterlidir. Ürün kartındaki teknik tabloları okuyup benzer modelleri aynı çalışma noktasında kıyaslamak, yalnız isim veya beygir gücüyle karar vermekten daha güvenilir sonuç verir.</p>';

        $guideClosing = isset($facts['guide_closing'])
            ? (string) $facts['guide_closing']
            : '<p>Alternatif ürün tiplerini görmek için '.$related.' sayfalarına bakabilir; kararsız kaldığınız noktada <a href="/pompa-secici">Pompa Seçici</a> veya <a href="/iletisim">teknik destek</a> üzerinden uygulama bilgilerinizi paylaşabilirsiniz.</p>';

        $description = <<<HTML
<h2>{$name}: Kullanım Alanı ve Doğru Seçim</h2>
<p>{$hook} {$bridge}</p>
<h3>{$name} Nerelerde Kullanılır?</h3>
<ul>{$uses}</ul>
<p>{$scenario}</p>
<h3>Seçimde Kontrol Edilecek Teknik Noktalar</h3>
<ul>{$criteria}</ul>
<p>{$measure}</p>
<h3>Sık Yapılan Yanlış</h3>
<p>{$mistake} Ayrıca azami kapasite değerleri çoğunlukla uç çalışma koşullarını gösterir; sürekli işletme noktası ürün eğrisi veya teknik tablosu üzerinden doğrulanmalıdır. Bu yaklaşım gereksiz büyük ürün alımını, yüksek enerji tüketimini ve kısa arıza döngülerini önlemeye yardımcı olur.</p>
{$table}
<h3>İşletme ve Bakım Planı</h3>
<p>{$ops}</p>
<h3>İlgili Kategoriler ve Teknik Destek</h3>
<p>{$pack}</p>
{$closing}
{$serviceNote}
{$blog}
HTML;

        $guide = <<<HTML
<h3>{$name} Satın Alma Kontrol Listesi</h3>
<p>Siparişten önce uygulamanın kullanım amacını, gerekli kapasiteyi, bağlantı ölçülerini ve elektrik beslemesini doğrulayın. Ürünü yalnız fiyat veya motor gücü üzerinden değil, gerçek çalışma koşulunda sunacağı performans üzerinden karşılaştırın.</p>
<ul>{$criteria}</ul>
<p><strong>Kaçınılması gereken seçim:</strong> {$mistake}</p>
<p>Teklifleri karşılaştırırken ana ürünün yanında gereken bağlantı parçalarını, kontrol ve koruma ekipmanlarını, kablo veya boru uyumunu ve garanti şartlarını da listeleyin. Teslim edilen paketin kuruluma hazır olup olmadığını doğrulamak sonradan çıkabilecek ek maliyetleri azaltır.</p>
{$guideClosing}
HTML;

        return [
            'description' => $description,
            'buying_guide' => $guide,
            'subtitle' => $this->subtitle($category, $facts),
        ];
    }

    /** @param list<string> $items */
    private function list(array $items): string
    {
        return implode('', array_map(
            fn (string $item): string => '<li>'.$this->escape($item).'</li>',
            $items
        ));
    }

    /** @param list<array{href: string, label: string}> $items */
    private function relatedLinks(array $items): string
    {
        $links = array_map(
            static fn (array $item): string => '<a href="'.e($item['href']).'">'.e($item['label']).'</a>',
            $items
        );

        if (count($links) === 1) {
            return $links[0];
        }

        $last = array_pop($links);

        return implode(', ', $links).' ve '.$last;
    }

    /** @param list<array{0: string, 1: string, 2: string}> $rows */
    private function table(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr><td>'.$this->escape($row[0]).'</td><td>'.$this->escape($row[1]).'</td><td>'.$this->escape($row[2]).'</td></tr>';
        }

        return '<h3>Kısa Karşılaştırma</h3><table><thead><tr><th>Ürün tipi</th><th>Uygun kullanım</th><th>Ayırt edici nokta</th></tr></thead><tbody>'.$body.'</tbody></table>';
    }

    /** @param array<string, mixed> $facts */
    private function subtitle(Category $category, array $facts): string
    {
        return trim((string) $category->name).': '.$facts['hook'].' Teknik özellikleri karşılaştırın ve uygulamanıza uygun ürünü seçin.';
    }

    private function bridgeParagraph(int $variant): string
    {
        return match ($variant) {
            1 => 'Aynı kategori içindeki modeller kapasite, malzeme, bağlantı ve çalışma karakteri bakımından birebir eşdeğer değildir. Önce uygulamanın sınırlarını netleştirin; etiket üzerindeki tepe değer yerine gerçek çalışma noktasına göre karşılaştırın. Bu yaklaşım hem yanlış ürün iadesini azaltır hem de sahada beklenen performansın tutmasını kolaylaştırır.',
            2 => 'Stoktaki ürünler benzer görünse de hidrolik veya hava karakteri, gövde dayanımı ve montaj biçimi farkı sonucu sahada belirgin şekilde değiştirir. Seçimi isim benzerliğine değil, ölçülen ihtiyaca dayandırın; teknik tablodaki eğri değerleri fiyat listesinden daha anlamlıdır.',
            default => 'Bu kategorideki ürünler aynı temel ihtiyaca hizmet etse de kapasite, malzeme, bağlantı ve çalışma karakteri bakımından birbirinin doğrudan karşılığı değildir. Sağlıklı karşılaştırma için önce uygulamanın sınırlarını belirleyin, ardından katalog tepe değerleri yerine sürekli çalışma noktasını esas alın.',
        };
    }

    private function scenarioParagraph(int $variant): string
    {
        return match ($variant) {
            1 => 'Kaynak ile hedef arasındaki dikey kot, yatay hat, bağlantı elemanları, çalışma süresi ve ortam koşulları sonucu birlikte belirler. Katalogda yüksek görünen tek bir rakam, sistemin tamamına uygunluk demek değildir; sürekli çalışmada enerji, koruma ve bakım erişimi fiyat kadar kritiktir. Özellikle uzun hatlı veya sık aç-kapanan sistemlerde bu fark erken ortaya çıkar.',
            2 => 'Uygulama tipi netleşince hat kayıplarını, ortamın sıcaklık/toz/nem yükünü ve beklenen çalışma rejimini not edin. Yanlış varsayımlarla büyütülen ürün çoğu zaman daha pahalı, daha gürültülü ve daha sık arızalanan bir çözüme dönüşür. Doğru boyutlandırma hem konforu hem elektrik faturasını etkiler.',
            default => 'Kullanım senaryosu netleştiğinde kaynak ile hedef arasındaki koşulları not edin. Dikey kot, yatay hat, bağlantı elemanları, çalışma süresi ve ortam özellikleri ürünün sahadaki sonucunu birlikte etkiler. Özellikle sürekli çalışacak ekipmanda enerji verimi, motor koruması ve bakım erişimi ilk satın alma bedeli kadar önem taşır.',
        };
    }

    private function measureParagraph(int $variant): string
    {
        return match ($variant) {
            1 => 'Mümkünse değerleri ölçerek hazırlayın. Belirsizlik varsa kapasiteyi rastgele büyütmek yerine hangi parametrenin eksik olduğunu yazın. Boru/kanal daraltmak, gereksiz dirsek kullanmak veya elektrik beslemesini doğrulamamak doğru ürünü bile verimsiz çalıştırır. Değişimde eski etiket ve bağlantı ölçüleri yeni seçimin referansıdır.',
            2 => 'Değişim projelerinde yalnız model adına bakmayın; eski etiket değerleri, bağlantı ölçüleri ve yaşanan şikâyet (yetersiz debi, sık aç-kapa, gürültü) yeni seçimin sınırlarını çizer. Ölçü yoksa önce kritik parametreyi netleştirin; tahminle büyütmek yerine belirsizliği şeffaf tutmak daha güvenlidir.',
            default => 'Bu bilgiler mümkünse ölçülerek hazırlanmalıdır. Tahmini değerlerle seçim yapılacaksa kapasiteyi rastgele büyütmek yerine belirsizliğin hangi parametrede olduğunu belirleyin. Mevcut ürün değiştiriliyorsa eski etiket değerlerine, bağlantı ölçülerine ve sistemde yaşanan soruna da bakılmalıdır; böylece aynı hatayı tekrarlamazsınız.',
        };
    }

    private function opsParagraph(int $variant): string
    {
        return match ($variant) {
            1 => 'Kurulumda servis boşluğu bırakın, giriş hattını temiz tutun ve elektrik korumasını üretici değerlerine göre ayarlayın. Sezonluk ekipmanı beklemeye almadan önce temizleyin; sürekli sistemde ses, titreşim, sızıntı ve kapasite kaybını izleyin. İlk çalıştırmayı kılavuza göre yapın; garanti şartları çoğu zaman doğru kurulumla bağlantılıdır.',
            2 => 'Bakım planı satın alma kararının parçası olmalıdır. Filtre, conta, çark ve rulman erişimi; yedek parça bulunabilirliği ve garanti şartları uzun vadeli maliyeti etkiler. Anormal gürültü veya sıcaklık artışı görüldüğünde ürünü zorlamadan önce bağlantı ve beslemeyi kontrol edin; erken müdahale büyük arızayı önler.',
            default => 'Satın alma kararına bakım koşullarını da ekleyin. Ürünün çevresinde servis boşluğu bırakılması, giriş hattının temiz tutulması ve elektrik korumasının üretici değerlerine göre ayarlanması kararlı çalışmayı destekler. Sezonluk kullanılan ekipmanı uzun bekleme öncesinde temizlemek; sürekli çalışan sistemde ses, titreşim, sızıntı ve kapasite değişimini düzenli izlemek erken arıza belirtilerini fark etmeyi kolaylaştırır.',
        };
    }

    private function packParagraph(int $variant, string $related): string
    {
        return match ($variant) {
            1 => $related.' sayfalarını aynı uygulama için yan yana değerlendirin. Teklifte aksesuar, kontrol, elektrik ve koruma elemanlarının dahil olup olmadığını kontrol ederek çalışmaya hazır sistem maliyetini görün; eksik paket sonradan hem süre hem maliyet kaybettirir.',
            2 => 'İlgili seçenekler: '.$related.'. Karar verirken yalnız ana ürün fiyatına değil, bağlantı parçaları, pano/koruma ve montaj hazırlığına da bakın; eksik paket sonradan ek maliyet çıkarır. Marka farkını da aynı çalışma noktasında kıyaslamak daha adil sonuç verir.',
            default => $related.' seçeneklerini kullanım koşulunuza göre karşılaştırabilirsiniz. Ürünler arasında karar verirken aksesuar, kontrol ekipmanı, elektrik bağlantısı ve koruma elemanlarının pakete dahil olup olmadığını da kontrol edin. Böylece yalnız cihazı değil, çalışmaya hazır sistemin toplam maliyetini değerlendirmiş olursunuz.',
        };
    }

    private function escape(string $value): string
    {
        return e($value);
    }
}
