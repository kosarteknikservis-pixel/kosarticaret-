<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'preheader', 'title', 'body',
        'body_is_html', 'button_label', 'button_url', 'footer_note', 'settings', 'active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'active' => 'boolean',
            'body_is_html' => 'boolean',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function defaults(): array
    {
        return [
            'order_confirmation' => [
                'name' => 'Sipariş alındı',
                'subject' => 'Siparişiniz alındı — {{order_number}}',
                'preheader' => 'Siparişiniz KOŞAR Ticaret tarafından kaydedildi.',
                'title' => 'Siparişiniz alındı',
                'body' => "Merhaba {{customer_name}},\n\n{{order_number}} numaralı siparişiniz başarıyla kaydedildi. Toplam tutar: {{total}}.\n\nSiparişinizi takip sayfasından kontrol edebilirsiniz.",
                'button_label' => 'Siparişi takip et',
                'button_url' => '{{tracking_url}}',
                'footer_note' => 'Bu e-posta sipariş işleminizle ilgili otomatik olarak gönderilmiştir.',
                'settings' => ['show_items' => true, 'show_tracking' => true],
            ],
            'order_status_updated' => [
                'name' => 'Sipariş durumu güncellendi',
                'subject' => 'Sipariş durumunuz güncellendi — {{order_number}}',
                'preheader' => 'Siparişinizin güncel durumunu görüntüleyin.',
                'title' => 'Sipariş durumunuz güncellendi',
                'body' => "Merhaba {{customer_name}},\n\n{{order_number}} numaralı siparişinizin güncel durumu: {{status}}.\n\n{{tracking_text}}",
                'button_label' => 'Siparişi takip et',
                'button_url' => '{{tracking_url}}',
                'footer_note' => 'Durum ve kargo hareketleri panelden güncellendikçe bilgilendirileceksiniz.',
                'settings' => ['show_items' => true, 'show_tracking' => true],
            ],
            'payment_reminder' => [
                'name' => 'Ödeme hatırlatması',
                'subject' => 'Siparişiniz için ödeme bekleniyor — {{order_number}}',
                'preheader' => 'Siparişinizi tamamlamak için güvenli ödeme sayfasına dönebilirsiniz.',
                'title' => 'Ödemeniz henüz tamamlanmadı',
                'body' => "Merhaba {{customer_name}},\n\n{{order_number}} numaralı siparişiniz kaydedildi ancak ödeme henüz alınmadı. Toplam tutar: {{total}}.\n\nAşağıdaki butona tıklayarak PayTR güvenli ödeme sayfasından işleminizi tamamlayabilirsiniz.",
                'button_label' => 'Ödemeyi tamamla',
                'button_url' => '{{payment_url}}',
                'footer_note' => 'Ödeme tamamlandığında sipariş onay e-postası ayrıca gönderilecektir.',
                'settings' => ['show_items' => true, 'show_tracking' => false],
            ],
            'campaign_default' => [
                'name' => 'Kampanya duyurusu',
                'subject' => '{{campaign_title}}',
                'preheader' => 'KOŞAR Ticaret kampanya ve duyuruları.',
                'title' => '{{campaign_title}}',
                'body' => "Merhaba,\n\nKOŞAR Ticaret kampanya ve duyurularını sizinle paylaşmaktan mutluluk duyarız.",
                'button_label' => 'Detayları incele',
                'button_url' => config('app.url'),
                'footer_note' => 'Bu e-postayı KOŞAR Ticaret bültenine abone olduğunuz için aldınız.',
                'settings' => ['show_items' => false, 'show_tracking' => false],
            ],
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $key => $data) {
            self::query()->firstOrCreate(['key' => $key], ['key' => $key, ...$data]);
        }
    }

    public static function forKey(string $key): self
    {
        self::ensureDefaults();

        return self::query()->where('key', $key)->firstOrFail();
    }

    /** @param array<string, mixed> $params */
    public function render(string $field, array $params = []): string
    {
        $value = (string) ($this->{$field} ?? '');

        foreach ($params as $key => $param) {
            $value = str_replace('{{'.$key.'}}', (string) $param, $value);
        }

        return Str::of($value)->replaceMatches('/\{\{[a-zA-Z0-9_]+\}\}/', '')->toString();
    }
}
