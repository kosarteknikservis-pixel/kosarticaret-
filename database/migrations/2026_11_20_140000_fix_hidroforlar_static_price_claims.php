<?php

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hidroforlar kategorisindeki sabit «6.500 TL» iddialarını kaldırır.
 *
 * Kimlik: nested path hidrofor-sistemleri/hidroforlar (slug doğrulaması; yalnız id’ye güvenilmez).
 * Alanlar: meta_description + FAQ cevabı (sabit fiyat içeren madde). Ürün satış fiyatı yok.
 * down(): yalnız NEW metinler hâlâ duruyorsa PREVIOUS’a döner; sonraki düzenlemeleri ezmez.
 */
return new class extends Migration
{
    private const EXPECTED_NESTED_PATH = 'hidrofor-sistemleri/hidroforlar';

    /** Canlıdan okunan önceki meta (geri alma için sabitlenmiş). */
    private const PREVIOUS_META_DESCRIPTION = 'Hidrofor fiyatları: ev tipi paketler yaklaşık 6.500 TL’den; apartman ve grup sistemleri üst banda. Sumak, Pedrollo, Winpo, Kaysu — 280+ model.';

    private const NEW_META_DESCRIPTION = 'Hidrofor modelleri ve güncel fiyatlar: Sumak, Pedrollo, Winpo, Kaysu paketleri. Ev tipi ve apartman seçeneklerini ürün kartlarından inceleyin — 280+ model.';

    private const FAQ_QUESTION = 'Tek daire veya 1 HP hidrofor ne kadardan başlar?';

    private const PREVIOUS_FAQ_ANSWER = 'Tek daire ve 1 HP sınıfı paket hidroforlar bu katalogda yaklaşık 6.500 TL bandından başlar; tanklı veya hidromatlı oluşu ve markaya göre yükselir. Küçük konut paketleri için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> listesine bakın. Güncel tutar ürün kartındadır.';

    private const NEW_FAQ_ANSWER = 'Tek daire ve 1 HP sınıfı paket hidrofor fiyatları marka, tanklı/hidromatlı paket ve stoka göre değişir. Küçük konut paketleri için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a> listesine bakın. Güncel tutarı ürün kartından doğrulayın.';

    public function up(): void
    {
        $row = $this->resolveTargetRow();
        if ($row === null) {
            return;
        }

        $data = [];
        $oldMeta = trim((string) $row->meta_description);
        if ($oldMeta === '' || str_contains($oldMeta, '6.500')) {
            $data['meta_description'] = self::NEW_META_DESCRIPTION;
        }

        $faq = $this->decodeFaq($row->faq);
        if ($faq !== null) {
            $changed = false;
            foreach ($faq as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                $q = (string) ($item['q'] ?? '');
                $a = (string) ($item['a'] ?? '');
                if ($q === self::FAQ_QUESTION && str_contains($a, '6.500')) {
                    $item['a'] = self::NEW_FAQ_ANSWER;
                    $changed = true;
                }
            }
            unset($item);
            if ($changed) {
                $data['faq'] = json_encode(array_values($faq), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($data === []) {
            return;
        }

        DB::table('categories')->where('id', (int) $row->id)->update($data + ['updated_at' => now()]);
        app(UrlIndexingNotifier::class)->clearSitemapCache();
    }

    public function down(): void
    {
        $row = $this->resolveTargetRow();
        if ($row === null) {
            return;
        }

        $data = [];
        if (trim((string) $row->meta_description) === self::NEW_META_DESCRIPTION) {
            $data['meta_description'] = self::PREVIOUS_META_DESCRIPTION;
        }

        $faq = $this->decodeFaq($row->faq);
        if ($faq !== null) {
            $changed = false;
            foreach ($faq as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                $q = (string) ($item['q'] ?? '');
                $a = (string) ($item['a'] ?? '');
                if ($q === self::FAQ_QUESTION && $a === self::NEW_FAQ_ANSWER) {
                    $item['a'] = self::PREVIOUS_FAQ_ANSWER;
                    $changed = true;
                }
            }
            unset($item);
            if ($changed) {
                $data['faq'] = json_encode(array_values($faq), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($data === []) {
            return;
        }

        DB::table('categories')->where('id', (int) $row->id)->update($data + ['updated_at' => now()]);
        app(UrlIndexingNotifier::class)->clearSitemapCache();
    }

    /**
     * @return object{id:int, slug:string, meta_description:?string, faq:mixed, parent_slug:?string}|null
     */
    private function resolveTargetRow(): ?object
    {
        $row = DB::table('categories as c')
            ->leftJoin('categories as p', 'p.id', '=', 'c.parent_id')
            ->where('c.slug', 'hidroforlar')
            ->where('p.slug', 'hidrofor-sistemleri')
            ->select(['c.id', 'c.slug', 'c.meta_description', 'c.faq', 'p.slug as parent_slug'])
            ->first();

        if ($row === null) {
            return null;
        }

        $path = trim((string) ($row->parent_slug ?? ''), '/') . '/' . trim((string) $row->slug, '/');
        if ($path !== self::EXPECTED_NESTED_PATH) {
            return null;
        }

        return $row;
    }

    /** @return list<array<string, mixed>>|null */
    private function decodeFaq(mixed $raw): ?array
    {
        if (is_array($raw)) {
            return array_values($raw);
        }
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values($decoded) : null;
    }
};
