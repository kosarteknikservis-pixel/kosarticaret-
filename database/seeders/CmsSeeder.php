<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\LegalPagesContent;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (LegalPagesContent::all() as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'meta_title' => $page['meta_title'],
                    'meta_description' => $page['meta_description'],
                    'published' => $page['published'],
                    'sort_order' => $page['sort_order'],
                ]
            );
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
