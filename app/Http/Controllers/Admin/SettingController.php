<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\FooterPaymentCards;
use App\Support\ImageVariant;
use App\Support\LogoImageProcessor;
use App\Support\MailSettings;
use App\Support\PaymentMethodSettings;
use App\Services\StoreConfig;
use App\Support\SiteFavicon;
use App\Support\SiteLogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public const TABS = ['general', 'header', 'footer', 'contact', 'home', 'maintenance', 'shipping', 'integrations'];

    private const SHIPPING_KEYS = [
        'cod_fee', 'vat_rate', 'checkout_add_vat',
        'shipping_rate_standart', 'shipping_rate_hizli',
        'ship_standart_name', 'ship_standart_desc', 'ship_standart_eta',
        'ship_hizli_name', 'ship_hizli_desc', 'ship_hizli_eta',
        'pay_kredi_karti_name', 'pay_kredi_karti_desc',
        'pay_havale_name', 'pay_havale_desc',
        'pay_kapida_odeme_name', 'pay_kapida_odeme_desc',
    ];

    private const KEYS = [
        'site_name', 'site_description', 'contact_phone', 'contact_email', 'contact_whatsapp', 'contact_address',
        'floating_whatsapp_enabled', 'scroll_top_enabled',
        'pdp_whatsapp_order_enabled', 'pdp_whatsapp_order_label',
        'shop_show_stock_quantity',
        'contact_page_intro', 'contact_meta_title', 'contact_meta_description',
        'google_site_verification', 'google_verification_file_name', 'google_verification_file_content', 'google_analytics_id',
        'home_brands_title', 'promo_text', 'free_shipping_min',
        'newsletter_enabled', 'newsletter_title',
        'tagline', 'trust_secure', 'trust_shipping', 'trust_returns', 'trust_support',
        'cookie_text', 'cookie_accept',
        'legal_name',
        'footer_trust_cards', 'footer_trust_compliance', 'footer_etbis_url', 'footer_kvkk_url',
        'social_instagram_url', 'social_facebook_url', 'social_youtube_url',
        'social_linkedin_url', 'social_x_url', 'social_tiktok_url',
        'openai_api_key', 'openai_model',
        'brevo_enabled', 'brevo_api_key', 'brevo_list_id',
        'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        'smtp_from_address', 'smtp_from_name',
        'parasut_enabled', 'parasut_client_id', 'parasut_client_secret', 'parasut_company_id',
        'parasut_username', 'parasut_password', 'parasut_redirect_uri',
        'parasut_access_token', 'parasut_refresh_token', 'parasut_token_expires_at',
        'shop_maintenance_enabled', 'shop_maintenance_title', 'shop_maintenance_message',
        'pump_selector_enabled',
    ];

    private const LANG_DEFAULTS = [
        'tagline' => 'shop.tagline',
        'trust_secure' => 'shop.trust_secure',
        'trust_shipping' => 'shop.trust_shipping',
        'trust_returns' => 'shop.trust_returns',
        'trust_support' => 'shop.trust_support',
        'cookie_text' => 'shop.cookie_text',
        'cookie_accept' => 'shop.cookie_accept',
    ];

    public function edit(): View
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $default = isset(self::LANG_DEFAULTS[$key])
                ? __(self::LANG_DEFAULTS[$key])
                : (config("kosar.defaults.{$key}")
                    ?? config("kosar.contact.{$key}")
                    ?? config("kosar.{$key}")
                    ?? '');
            if ($key === 'legal_name' && $default === '') {
                $default = (string) config('kosar.legal_name');
            }
            if ($key === 'footer_trust_cards' && $default === '') {
                $default = implode(',', config('kosar.footer.default_cards', []));
            }
            if ($key === 'footer_trust_compliance' && $default === '') {
                $default = implode(',', config('kosar.footer.default_compliance', []));
            }
            if (in_array($key, ['pdp_whatsapp_order_enabled', 'floating_whatsapp_enabled', 'scroll_top_enabled'], true) && $default === '') {
                $default = '1';
            }
            $values[$key] = SiteSetting::get($key, $default);
        }

        $tab = request('tab', 'general');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'general';
        }

        return view('admin.settings.edit', [
            'values' => $values,
            'logoUrl' => SiteLogo::url(),
            'faviconUrl' => SiteFavicon::customUrl(),
            'footerExtraCards' => FooterPaymentCards::extraStored(),
            'footerCardCatalog' => FooterPaymentCards::catalog(),
            'activeTab' => $tab,
            'shippingValues' => $this->shippingValues(),
            'shippingMethods' => app(StoreConfig::class)->storedShippingMethods(),
            'paymentMethods' => config('shipping.payment_methods'),
            'paymentEnabled' => PaymentMethodSettings::enabledForAdmin(),
            'merchantFeedUrl' => route('merchant.feed'),
            'merchantFeedProductCount' => Product::query()
                ->active()
                ->where('stock', '>', 0)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string'],
            'site_description' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_whatsapp' => ['nullable', 'string'],
            'floating_whatsapp_enabled' => ['sometimes', 'boolean'],
            'scroll_top_enabled' => ['sometimes', 'boolean'],
            'pdp_whatsapp_order_enabled' => ['sometimes', 'boolean'],
            'pdp_whatsapp_order_label' => ['nullable', 'string', 'max:80'],
            'shop_show_stock_quantity' => ['sometimes', 'boolean'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'contact_page_intro' => ['nullable', 'string', 'max:1000'],
            'contact_meta_title' => ['nullable', 'string', 'max:120'],
            'contact_meta_description' => ['nullable', 'string', 'max:320'],
            'google_site_verification' => ['nullable', 'string', 'max:120'],
            'google_verification_file_name' => ['nullable', 'string', 'max:120', 'regex:/^google[a-zA-Z0-9_-]+\.html$/'],
            'google_verification_file_content' => ['nullable', 'string', 'max:255'],
            'google_analytics_id' => ['nullable', 'string', 'max:32', 'regex:/^(G-[A-Z0-9]+)?$/'],
            'home_brands_title' => ['nullable', 'string', 'max:120'],
            'promo_text' => ['nullable', 'string'],
            'free_shipping_min' => ['nullable', 'numeric'],
            'newsletter_enabled' => ['sometimes', 'boolean'],
            'newsletter_title' => ['nullable', 'string'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'trust_secure' => ['nullable', 'string', 'max:120'],
            'trust_shipping' => ['nullable', 'string', 'max:120'],
            'trust_returns' => ['nullable', 'string', 'max:120'],
            'trust_support' => ['nullable', 'string', 'max:120'],
            'cookie_text' => ['nullable', 'string', 'max:500'],
            'cookie_accept' => ['nullable', 'string', 'max:60'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'footer_trust_cards' => ['nullable', 'array'],
            'footer_trust_cards.*' => ['string', 'max:32'],
            'footer_trust_compliance' => ['nullable', 'array'],
            'footer_trust_compliance.*' => ['string', 'max:32'],
            'footer_etbis_url' => ['nullable', 'url', 'max:500'],
            'footer_kvkk_url' => ['nullable', 'url', 'max:500'],
            'social_instagram_url' => ['nullable', 'string', 'max:500'],
            'social_facebook_url' => ['nullable', 'string', 'max:500'],
            'social_youtube_url' => ['nullable', 'string', 'max:500'],
            'social_linkedin_url' => ['nullable', 'string', 'max:500'],
            'social_x_url' => ['nullable', 'string', 'max:500'],
            'social_tiktok_url' => ['nullable', 'string', 'max:500'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_model' => ['nullable', 'string', 'max:64'],
            'brevo_enabled' => ['sometimes', 'boolean'],
            'brevo_api_key' => ['nullable', 'string', 'max:255'],
            'brevo_list_id' => ['nullable', 'integer', 'min:1'],
            'smtp_enabled' => ['sometimes', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
            'parasut_enabled' => ['sometimes', 'boolean'],
            'parasut_client_id' => ['nullable', 'string', 'max:255'],
            'parasut_client_secret' => ['nullable', 'string', 'max:255'],
            'parasut_company_id' => ['nullable', 'string', 'max:64'],
            'parasut_username' => ['nullable', 'string', 'max:255'],
            'parasut_password' => ['nullable', 'string', 'max:255'],
            'parasut_redirect_uri' => ['nullable', 'string', 'max:255'],
            'pump_selector_enabled' => ['sometimes', 'boolean'],
            'footer_extra_card_label' => ['nullable', 'string', 'max:80'],
            'footer_extra_card_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:1024'],
            'remove_footer_extra_card' => ['nullable', 'string', 'max:64'],
            'site_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'site_favicon' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,ico', 'max:512'],
        ]);

        if ($request->filled('remove_footer_extra_card')) {
            FooterPaymentCards::removeExtra((string) $request->input('remove_footer_extra_card'));

            return $this->redirectToTab($request, 'footer', 'Kart görseli kaldırıldı.');
        }

        if ($request->hasFile('footer_extra_card_image')) {
            FooterPaymentCards::addExtra(
                (string) $request->input('footer_extra_card_label', 'Kart'),
                $request->file('footer_extra_card_image')
            );

            return $this->redirectToTab($request, 'footer', 'Kart görseli eklendi.');
        }

        $data['newsletter_enabled'] = $request->boolean('newsletter_enabled') ? '1' : '0';
        $data['pdp_whatsapp_order_enabled'] = $request->boolean('pdp_whatsapp_order_enabled') ? '1' : '0';
        $data['floating_whatsapp_enabled'] = $request->boolean('floating_whatsapp_enabled') ? '1' : '0';
        $data['scroll_top_enabled'] = $request->boolean('scroll_top_enabled') ? '1' : '0';
        $data['shop_show_stock_quantity'] = $request->boolean('shop_show_stock_quantity') ? '1' : '0';
        $data['shop_maintenance_enabled'] = $request->boolean('shop_maintenance_enabled') ? '1' : '0';
        $data['brevo_enabled'] = $request->boolean('brevo_enabled') ? '1' : '0';
        $data['smtp_enabled'] = $request->boolean('smtp_enabled') ? '1' : '0';
        $data['parasut_enabled'] = $request->boolean('parasut_enabled') ? '1' : '0';
        $data['pump_selector_enabled'] = $request->boolean('pump_selector_enabled') ? '1' : '0';
        $selectedCards = $request->input('footer_trust_cards', []);
        $extraKeys = array_column(FooterPaymentCards::extraStored(), 'key');
        $data['footer_trust_cards'] = implode(',', array_values(array_unique(array_merge($selectedCards, $extraKeys))));
        $data['footer_trust_compliance'] = implode(',', $request->input('footer_trust_compliance', []));

        foreach (\App\Support\SocialMediaLinks::settingKeys() as $socialKey) {
            if (! array_key_exists($socialKey, $data)) {
                continue;
            }
            $data[$socialKey] = trim((string) $data[$socialKey]);
        }

        if ($request->boolean('remove_site_logo')) {
            SiteLogo::deleteStored();
        } elseif ($request->hasFile('site_logo')) {
            $old = SiteSetting::get('site_logo');
            if ($old && ! str_starts_with($old, 'http')) {
                ImageVariant::delete($old);
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('site_logo')->store('branding', 'public');
            if ($request->boolean('logo_strip_white')) {
                LogoImageProcessor::stripLightBackground(storage_path('app/public/'.$path));
            }
            ImageVariant::generate($path, ImageVariant::presetsFor('site-logo'));
            SiteSetting::set('site_logo', $path);
            Cache::forget('setting.site_logo');
        }

        unset($data['site_logo']);

        if ($request->boolean('remove_site_favicon')) {
            SiteFavicon::deleteStored();
        } elseif ($request->hasFile('site_favicon')) {
            $old = SiteSetting::get('site_favicon');
            if ($old && ! str_starts_with($old, 'http')) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('site_favicon')->store('branding', 'public');
            SiteSetting::set('site_favicon', $path);
            Cache::forget('setting.site_favicon');
        }

        unset($data['site_favicon']);

        if (! $request->filled('openai_api_key')) {
            unset($data['openai_api_key']);
        }

        if (! $request->filled('brevo_api_key')) {
            unset($data['brevo_api_key']);
        }

        if (! $request->filled('smtp_password')) {
            unset($data['smtp_password']);
        }

        if (! $request->filled('parasut_client_secret')) {
            unset($data['parasut_client_secret']);
        }

        if (! $request->filled('parasut_password')) {
            unset($data['parasut_password']);
        }

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value !== null ? (string) $value : null);
        }
        Cache::forget('settings.all');
        \App\Support\PublicPageCache::forgetAll();

        return $this->redirectToTab($request, null, 'Ayarlar kaydedildi.');
    }

    private function redirectToTab(Request $request, ?string $fallbackTab, string $message): RedirectResponse
    {
        $tab = $request->input('_tab', $fallbackTab ?? 'general');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'general';
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => $tab])
            ->with('success', $message);
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_test_email' => ['required', 'email', 'max:255'],
        ]);

        if (! MailSettings::isConfigured()) {
            return $this->redirectToTab($request, 'integrations', 'SMTP ayarları eksik. Önce SMTP ayarlarını kaydedin.');
        }

        try {
            MailSettings::apply();
            Mail::raw('KOŞAR SMTP test e-postası başarıyla gönderildi.', function ($message) use ($data) {
                $message->to($data['smtp_test_email'])->subject('KOŞAR SMTP Test');
            });
        } catch (\Throwable $e) {
            return $this->redirectToTab($request, 'integrations', 'SMTP test e-postası gönderilemedi: '.$e->getMessage());
        }

        return $this->redirectToTab($request, 'integrations', 'SMTP test e-postası gönderildi.');
    }

    /** @return array<string, string> */
    private function shippingValues(): array
    {
        $controller = app(ShippingSettingsController::class);
        $values = [];
        foreach (self::SHIPPING_KEYS as $key) {
            $values[$key] = SiteSetting::get($key, $controller->defaultFor($key));
        }

        return $values;
    }
}
