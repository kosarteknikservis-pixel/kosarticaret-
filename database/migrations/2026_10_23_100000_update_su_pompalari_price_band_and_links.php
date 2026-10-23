<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONTACT = '<p>İstanbul içinde kurulum ve teknik servis desteği için <a href="/iletisim">bizimle iletişime geçebilirsiniz</a>.</p>';

    private const SP = '/kategoriler/su-pompalari';

    private const SF = '/kategoriler/su-pompalari/santrifuj-pompalar';

    public function up(): void
    {
        $id = (int) (DB::table('categories')->where('slug', 'su-pompalari')->whereNull('parent_id')->value('id') ?? 0);
        if ($id === 0) {
            return;
        }

        $sp = self::SP;
        $sf = self::SF;

        $description = <<<HTML
<h2>Su Pompası Modelleri ve Fiyatları</h2>
<p><strong>Su pompası</strong>; kuyudan, depodan, şebekeden veya göletten aldığı suyu istenen noktaya ileten ya da tesisattaki basıncı artıran makinedir. Kuyudan ev için su çekmek, apartman tesisatını beslemek, tarla sulamak veya kalorifer devresinde suyu dolaştırmak farklı pompa tipleri gerektirir.</p>
<p>Bu kategoride Sumak, Pedrollo, Winpo ve Kaysu markalarının 1.000'i aşkın su pompası modeli yer alır. Doğru tipi seçmek için önce suyun nereden alınacağı, nereye ve hangi basınçla gönderileceği belirlenmelidir.</p>
<p>Bu kategorideki pompa modellerinin güncel fiyatları yaklaşık <strong>2.770 TL</strong> ile <strong>603.550 TL</strong> arasındadır (23 Ekim 2026, stoktaki pompa listesi; basınç şalteri ve hidromat gibi aksesuarlar hariç). Fiyatı tip, motor gücü, debi, basma yüksekliği ve gövde malzemesi belirler; aşağıdaki ürün grid’indeki rakamlar sipariş anındaki listedir.</p>
<h3>Su Pompası Çeşitleri</h3>
<ul>
<li><strong>Dalgıç pompalar:</strong> Suyun içine indirilerek çalışır; derin kuyu, keson kuyu, drenaj, kirli su ve foseptik uygulamaları içindir. Bkz. <a href="{$sp}/dalgic-pompalar">dalgıç pompalar</a>.</li>
<li><strong>Santrifüj pompalar:</strong> Yüzeye kurulur; depo, gölet veya sığ kuyudan su çekip sulama, su transferi ve tesisat beslemesinde kullanılır. Emiş derinliği pratikte 6–7 metreyle sınırlıdır. Bkz. <a href="{$sf}">santrifüj pompalar</a>.</li>
<li>Santrifüj pompalar içinde işe göre kırılım: düşük–orta basma için <a href="{$sf}/tek-fanli-santrifuj-pompa">tek fanlı santrifüj</a>, daha yüksek basma için <a href="{$sf}/cift-fanli-santrifuj-pompa">çift fanlı santrifüj</a>, yüksek debili sulama/transfer için <a href="{$sf}/salyangoz-pompalar-bol-su-veren">salyangoz (bol su veren) pompalar</a>.</li>
<li><strong>Kademeli pompalar:</strong> Birden fazla çarkla yüksek basınç üretir; çok katlı bina besleme ve sanayi hatları içindir. Bkz. <a href="{$sp}/kademeli-pompalar">kademeli pompalar</a>.</li>
<li><strong>Jet pompalar:</strong> Ejektör sayesinde standart santrifüj pompaya göre daha derinden su emebilir; sığ kuyu ve bahçe sulaması için kullanılır. Bkz. <a href="{$sp}/jet-pompalar-derinden-emisli">jet pompalar</a>.</li>
<li><strong>Preferikal (sürtme fanlı) pompalar:</strong> Düşük debide yüksek basınç veren kompakt pompalardır; ev tipi küçük tesisatlarda kullanılır. Bkz. <a href="{$sp}/preferikal-pompalar-surtme-fanli">preferikal pompalar</a>.</li>
<li><strong>Sirkülasyon pompaları:</strong> Kalorifer ve yerden ısıtma gibi kapalı devrelerde suyu dolaştırır. Bkz. <a href="{$sp}/sirkulasyon-pompalari">sirkülasyon pompaları</a>.</li>
<li><strong>Özel amaçlı pompalar:</strong> Yangın, havuz, jakuzi, keson kuyu ve dizel motorlu pompalar. Bkz. <a href="{$sp}/ozel-amacli-pompalar">özel amaçlı pompalar</a>.</li>
</ul>
<p>Musluklarda sabit basınç gerekiyorsa pompa, basınç tankı ve basınç şalterinden oluşan <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemleri</a> daha uygun olabilir.</p>
<h3>Su Pompası Fiyatlarını Ne Belirler?</h3>
<p>Fiyatı başlıca pompa tipi, motor gücü, debi ve basma yüksekliği, gövde malzemesi (döküm veya paslanmaz) ve elektrik beslemesi (220 V monofaze veya 380 V trifaze) belirler. Ayrıntılar için <a href="/blog/su-pompasi-fiyatlari-2026-rehberi">su pompası maliyetini belirleyen etkenler</a> yazımıza bakabilirsiniz. Markaya göre incelemek için <a href="/marka/sumak">Sumak</a>, <a href="/marka/pedrollo">Pedrollo</a>, <a href="/marka/winpo">Winpo</a> ve <a href="/marka/kaysu">Kaysu</a> sayfalarını ziyaret edebilirsiniz.</p>
HTML;

        $buyingGuide = <<<HTML
<h3>Su Pompası Nasıl Seçilir?</h3>
<ul>
<li><strong>Su kaynağı ve derinlik:</strong> Su seviyesi pompadan 6–7 metreden daha aşağıdaysa yüzey pompası emiş yapamaz; kuyu için dalgıç pompa veya derinden emişli jet pompa gerekir.</li>
<li><strong>Debi:</strong> Aynı anda kullanılacak musluk, sulama başlığı veya hattın ihtiyacına göre m³/h cinsinden belirlenir.</li>
<li><strong>Basma yüksekliği:</strong> Suyun çıkacağı en yüksek nokta, boru sürtünme kayıpları ve kullanım noktasında istenen basınç toplanarak bulunur. 1 bar yaklaşık 10 metre su sütununa karşılık gelir.</li>
<li><strong>Suyun türü:</strong> Temiz su, kumlu kuyu suyu, kirli su ve atık su farklı çark ve gövde ister. Tuzlu, klorlu veya kimyasal sıvılarda paslanmaz modeller seçilir.</li>
<li><strong>Elektrik:</strong> Evlerde genellikle 220 V monofaze, büyük güçlerde 380 V trifaze modeller kullanılır.</li>
</ul>
<h3>Hangi İş İçin Hangi Pompa?</h3>
<ul>
<li>Sondaj kuyusundan su çekmek için <a href="{$sp}/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompa</a>.</li>
<li>Depo veya şebekeden eve basınçlı su için <a href="/kategoriler/hidrofor-sistemleri/hidroforlar">hidrofor</a>.</li>
<li>Gölet, depo veya kanaldan sulama için <a href="{$sf}">santrifüj pompa</a>.</li>
<li>Depo veya göletten orta basma ile sulama için <a href="{$sf}/tek-fanli-santrifuj-pompa">tek fanlı</a>; aynı işte daha yüksek basma için <a href="{$sf}/cift-fanli-santrifuj-pompa">çift fanlı</a>; yüksek debili açık sulama için <a href="{$sf}/salyangoz-pompalar-bol-su-veren">salyangoz pompa</a>.</li>
<li>Çok katlı binaya su basmak için <a href="{$sp}/kademeli-pompalar">kademeli pompa</a> veya <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a>.</li>
<li>Bodrum ve çukurlardaki suyu boşaltmak için <a href="{$sp}/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompa</a>.</li>
</ul>
<p>Ayrıntılı anlatım için <a href="/blog/su-pompasi-cesitleri-nasil-secilir">su pompası çeşitleri ve seçimi</a> ile <a href="/blog/pompa-debi-basma-yuksekligi-hesabi">debi ve basma yüksekliği hesabı</a> yazılarımızı okuyabilirsiniz.</p>
HTML.self::CONTACT;

        $current = DB::table('categories')->where('id', $id)->first(['description', 'buying_guide']);
        if ($current === null) {
            return;
        }

        $data = [];
        $oldDescription = trim((string) $current->description);
        if ($oldDescription !== trim($description) && str_starts_with($oldDescription, '<h2>Su Pompası Modelleri ve Fiyatları</h2>')) {
            $data['description'] = $description;
        }

        $oldGuide = trim((string) $current->buying_guide);
        if ($oldGuide !== trim($buyingGuide) && str_starts_with($oldGuide, '<h3>Su Pompası Nasıl Seçilir?</h3>')) {
            $data['buying_guide'] = $buyingGuide;
        }

        if ($data === []) {
            return;
        }

        DB::table('categories')->where('id', $id)->update($data + ['updated_at' => now()]);
        app(UrlIndexingNotifier::class)->clearSitemapCache();
    }

    public function down(): void
    {
        // İçerik güncellemesi geri alınmaz.
    }
};
