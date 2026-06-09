<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\ThemeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function edit(): View
    {
        return view('admin.theme.edit', [
            'values' => ThemeSettings::values(),
            'options' => ThemeSettings::OPTIONS,
            'groups' => ThemeSettings::GROUPS,
            'labels' => ThemeSettings::LABELS,
            'presets' => ThemeSettings::PRESETS,
            'sectionPresets' => ThemeSettings::SECTION_PRESETS,
            'customCss' => ThemeSettings::customCss(),
            'customCssMaxLength' => ThemeSettings::CUSTOM_CSS_MAX_LENGTH,
            'themeBackups' => ThemeSettings::backups(),
            'themeBackupLimit' => ThemeSettings::BACKUP_LIMIT,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $values = ThemeSettings::sanitize($this->validated($request));

        foreach ($values as $key => $value) {
            SiteSetting::set($key, $value);
        }
        Cache::forget('settings.all');
        \App\Support\PublicPageCache::forgetAll();

        return redirect()->route('admin.theme.edit')->with('success', 'Tema ayarları kaydedildi.');
    }

    public function preview(Request $request): RedirectResponse
    {
        $values = ThemeSettings::sanitize($this->validated($request));
        $currentPreview = session('preview_settings', []);
        $currentPreview = is_array($currentPreview) ? $currentPreview : [];

        session(['preview_settings' => array_merge($currentPreview, $values)]);

        return redirect()->route('home')->with('success', 'Tema önizleme modu aktif. Beğenirseniz panelden yayınlayabilirsiniz.');
    }

    public function applyPreset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preset' => ['required', 'string'],
            'mode' => ['required', 'in:preview,publish'],
        ]);

        if (! array_key_exists($data['preset'], ThemeSettings::PRESETS)) {
            return redirect()->route('admin.theme.edit')->with('error', 'Seçilen tema bulunamadı.');
        }

        $values = ThemeSettings::presetValues($data['preset']);
        $name = ThemeSettings::presetName($data['preset']);

        if ($data['mode'] === 'preview') {
            $currentPreview = session('preview_settings', []);
            $currentPreview = is_array($currentPreview) ? $currentPreview : [];

            session(['preview_settings' => array_merge($currentPreview, $values)]);

            return redirect()->route('home')->with('success', $name.' teması önizleme modunda açıldı.');
        }

        foreach ($values as $key => $value) {
            SiteSetting::set($key, $value);
        }
        Cache::forget('settings.all');

        return redirect()->route('admin.theme.edit')->with('success', $name.' teması yayınlandı.');
    }

    public function applySectionPreset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['required', 'string'],
            'template' => ['required', 'string'],
            'mode' => ['required', 'in:preview,publish'],
        ]);

        if (! isset(ThemeSettings::SECTION_PRESETS[$data['section']]['templates'][$data['template']])) {
            return redirect()->route('admin.theme.edit')->with('error', 'Seçilen bölüm şablonu bulunamadı.');
        }

        $values = ThemeSettings::sectionPresetValues($data['section'], $data['template']);
        $name = ThemeSettings::sectionPresetName($data['section'], $data['template']);

        if ($data['mode'] === 'preview') {
            $currentPreview = session('preview_settings', []);
            $currentPreview = is_array($currentPreview) ? $currentPreview : [];

            session(['preview_settings' => array_merge($currentPreview, $values)]);

            return redirect()->route('home')->with('success', $name.' önizleme modunda açıldı.');
        }

        foreach ($values as $key => $value) {
            SiteSetting::set($key, $value);
        }
        Cache::forget('settings.all');

        return redirect()->route('admin.theme.edit')->with('success', $name.' yayınlandı.');
    }

    public function customCss(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'custom_css' => ['nullable', 'string', 'max:'.ThemeSettings::CUSTOM_CSS_MAX_LENGTH],
            'mode' => ['required', 'in:preview,publish,reset'],
        ]);

        if ($data['mode'] === 'reset') {
            $current = SiteSetting::get(ThemeSettings::CUSTOM_CSS_KEY, '');
            if (filled($current)) {
                SiteSetting::set(ThemeSettings::CUSTOM_CSS_BACKUP_KEY, ThemeSettings::sanitizeCustomCss($current));
            }

            SiteSetting::set(ThemeSettings::CUSTOM_CSS_KEY, '');
            Cache::forget('settings.all');

            return redirect()->route('admin.theme.edit')->with('success', 'Özel CSS sıfırlandı. Önceki CSS yedeklendi.');
        }

        $css = ThemeSettings::sanitizeCustomCss($data['custom_css'] ?? '');

        if ($data['mode'] === 'preview') {
            $currentPreview = session('preview_settings', []);
            $currentPreview = is_array($currentPreview) ? $currentPreview : [];

            session(['preview_settings' => array_merge($currentPreview, [
                ThemeSettings::CUSTOM_CSS_KEY => $css,
            ])]);

            return redirect()->route('home')->with('success', 'Özel CSS önizleme modunda açıldı.');
        }

        $current = SiteSetting::get(ThemeSettings::CUSTOM_CSS_KEY, '');
        if (filled($current)) {
            SiteSetting::set(ThemeSettings::CUSTOM_CSS_BACKUP_KEY, ThemeSettings::sanitizeCustomCss($current));
        }

        SiteSetting::set(ThemeSettings::CUSTOM_CSS_KEY, $css);
        Cache::forget('settings.all');

        return redirect()->route('admin.theme.edit')->with('success', 'Özel CSS yayınlandı. Önceki CSS yedeklendi.');
    }

    public function createBackup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_name' => ['nullable', 'string', 'max:80'],
        ]);

        $backup = ThemeSettings::createBackup($data['backup_name'] ?? null);

        return redirect()
            ->route('admin.theme.edit')
            ->with('success', $backup['name'].' oluşturuldu.');
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_id' => ['required', 'string'],
        ]);

        if (! ThemeSettings::restoreBackup($data['backup_id'])) {
            return redirect()->route('admin.theme.edit')->with('error', 'Yedek bulunamadı veya geri yüklenemedi.');
        }

        Cache::forget('settings.all');

        return redirect()->route('admin.theme.edit')->with('success', 'Tema yedeği geri yüklendi. Geri yükleme öncesi mevcut tema otomatik yedeklendi.');
    }

    public function deleteBackup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_id' => ['required', 'string'],
        ]);

        if (! ThemeSettings::deleteBackup($data['backup_id'])) {
            return redirect()->route('admin.theme.edit')->with('error', 'Silinecek yedek bulunamadı.');
        }

        return redirect()->route('admin.theme.edit')->with('success', 'Tema yedeği silindi.');
    }

    public function reset(): RedirectResponse
    {
        foreach (ThemeSettings::DEFAULTS as $key => $value) {
            SiteSetting::set($key, $value);
        }
        Cache::forget('settings.all');

        return redirect()->route('admin.theme.edit')->with('success', 'Tema varsayılan ayarlara döndürüldü.');
    }

    /** @return array<string, string|null> */
    private function validated(Request $request): array
    {
        $rules = [];
        foreach (ThemeSettings::KEYS as $key) {
            $rules[$key] = ['nullable', 'string'];
        }

        return $request->validate($rules);
    }
}
