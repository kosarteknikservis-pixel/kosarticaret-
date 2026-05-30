<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $settings = $request->validate([
            'site_name' => ['nullable', 'string'],
            'site_description' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_whatsapp' => ['nullable', 'string'],
            'contact_address' => ['nullable', 'string'],
            'contact_page_intro' => ['nullable', 'string'],
            'hero_badge' => ['nullable', 'string'],
            'hero_title' => ['nullable', 'string'],
            'hero_subtitle' => ['nullable', 'string'],
            'promo_text' => ['nullable', 'string'],
            'free_shipping_min' => ['nullable', 'numeric'],
            'newsletter_enabled' => ['sometimes', 'boolean'],
            'newsletter_title' => ['nullable', 'string'],
            'tagline' => ['nullable', 'string'],
            'trust_secure' => ['nullable', 'string'],
            'trust_shipping' => ['nullable', 'string'],
            'trust_returns' => ['nullable', 'string'],
            'trust_support' => ['nullable', 'string'],
            'cookie_text' => ['nullable', 'string'],
            'cookie_accept' => ['nullable', 'string'],
            'legal_name' => ['nullable', 'string'],
            'footer_payment_badges' => ['nullable', 'string'],
        ]);

        $settings['newsletter_enabled'] = $request->boolean('newsletter_enabled') ? '1' : '0';
        session(['preview_settings' => array_filter($settings, fn ($v) => $v !== null && $v !== '')]);

        return redirect()->route('home')->with('success', 'Önizleme modu aktif — kaydetmeden vitrinde görürsünüz.');
    }

    public function stop(): RedirectResponse
    {
        session()->forget('preview_settings');

        return redirect()->route('admin.settings.edit')->with('success', 'Önizleme modu kapatıldı.');
    }
}
