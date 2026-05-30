<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['slug' => 'hakkimizda', 'title' => 'Hakkımızda', 'content' => '<p>Kosar Havalandırma ve Sulama Sistemi olarak pompa, hidrofor ve sulama ekipmanlarında Türkiye genelinde hizmet veriyoruz.</p>', 'sort_order' => 1],
            ['slug' => 'gizlilik-politikasi', 'title' => 'Gizlilik Politikası', 'content' => '<p>Kişisel verileriniz KVKK kapsamında korunmaktadır.</p>', 'sort_order' => 2],
            ['slug' => 'kvkk', 'title' => 'KVKK Aydınlatma', 'content' => '<p>6698 sayılı Kanun kapsamında aydınlatma metni.</p>', 'sort_order' => 3],
            ['slug' => 'kargo-ve-iade', 'title' => 'Kargo ve İade', 'content' => '<p>1000 TL üzeri siparişlerde standart kargo ücretsizdir. 14 gün içinde iade hakkınız saklıdır.</p>', 'sort_order' => 4],
            ['slug' => 'mesafeli-satis-sozlesmesi', 'title' => 'Mesafeli Satış Sözleşmesi', 'content' => '<p>Mesafeli sözleşmeler yönetmeliği hükümleri geçerlidir.</p>', 'sort_order' => 5],
            ['slug' => 'on-bilgilendirme', 'title' => 'Ön Bilgilendirme Formu', 'content' => '<p>Satıcı: Kosar Havalandırma ve Sulama Sistemi. Teslimat süresi stok durumuna göre 1–5 iş günüdür. Cayma hakkı 14 gündür.</p>', 'sort_order' => 6],
            ['slug' => 'sss', 'title' => 'Sıkça Sorulan Sorular', 'content' => '<h2>Kargo ne zaman gelir?</h2><p>Stoktan gönderimlerde 1–3 iş günü içinde kargoya verilir.</h2><h2>İade nasıl yapılır?</h2><p>14 gün içinde iade talebi için iletişim formunu kullanın.</p>', 'sort_order' => 7],
            ['slug' => 'iletisim', 'title' => 'İletişim', 'content' => '<p>Bize ulaşın: info@kosar.com.tr — <a href="/iletisim">İletişim formu</a></p>', 'sort_order' => 8],
        ];

        foreach ($pages as $p) {
            Page::query()->updateOrCreate(['slug' => $p['slug']], $p);
        }

        $settings = [
            'site_name' => config('kosar.name'),
            'site_description' => config('kosar.description'),
            'contact_phone' => config('kosar.contact.phone'),
            'contact_email' => config('kosar.contact.email'),
            'contact_whatsapp' => config('kosar.contact.whatsapp'),
            'pdp_whatsapp_order_enabled' => '1',
            'hero_badge' => config('kosar.defaults.hero_badge'),
            'hero_title' => config('kosar.defaults.hero_title'),
            'hero_subtitle' => config('kosar.description'),
            'promo_text' => config('kosar.defaults.promo_text'),
            'free_shipping_min' => (string) config('kosar.free_shipping_min'),
            'newsletter_enabled' => '1',
            'newsletter_title' => 'Kampanyalardan haberdar olun',
            'home_brands_title' => 'Güvenilir Markalar',
            'footer_trust_cards' => implode(',', config('kosar.footer.default_cards', ['visa', 'mastercard', 'paypal', 'amex', 'visa_electron', 'maestro'])),
            'footer_trust_compliance' => implode(',', config('kosar.footer.default_compliance', [])),
            'payment_checkout_enabled' => 'kredi_karti,havale,kapida_odeme',
            'payment_footer_enabled' => 'kredi_karti,havale,kapida_odeme',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::set($key, $value);
        }
    }
}
