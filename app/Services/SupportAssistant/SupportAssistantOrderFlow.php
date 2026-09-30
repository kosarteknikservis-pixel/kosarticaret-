<?php

namespace App\Services\SupportAssistant;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\AnalyticsTracker;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\CheckoutCalculator;
use App\Services\OrderService;
use App\Services\Sms\SmsService;
use App\Services\StoreConfig;
use App\Support\SiteName;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asistan sohbetinden sipariş: bilgiler oturumda birikir, özet müşteriye onaylatılır,
 * sipariş normal ödeme akışıyla (OrderService) oluşturulur. Kapıda ödeme ve havalede
 * Netgsm açıksa telefona doğrulama kodu gönderilir.
 */
class SupportAssistantOrderFlow
{
    public const DRAFT_KEY = 'support_chat_checkout_draft';

    public const PREFILL_KEY = 'support_chat_checkout';

    public const SUMMARY_KEY = 'support_chat_order_summary';

    public const OTP_KEY = 'support_chat_order_otp';

    private const OTP_TTL = 300;

    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_MAX_SENDS = 3;

    private const DUPLICATE_WINDOW_MINUTES = 15;

    private const FIELDS = [
        'ad' => 'Ad',
        'soyad' => 'Soyad',
        'telefon' => 'Cep telefonu',
        'eposta' => 'E-posta',
        'il' => 'İl',
        'ilce' => 'İlçe',
        'adres' => 'Açık adres',
        'odeme_yontemi' => 'Ödeme yöntemi',
    ];

    private const INVOICE_FIELDS = [
        'firma_adi' => 'Firma adı',
        'vergi_numarasi' => 'Vergi numarası',
        'vergi_dairesi' => 'Vergi dairesi',
        'fatura_adresi' => 'Fatura adresi',
    ];

    private const TEXT_FIELDS = ['ad', 'soyad', 'telefon', 'eposta', 'il', 'ilce', 'adres', 'posta_kodu', 'odeme_yontemi', 'kargo_yontemi', 'firma_adi', 'vergi_numarasi', 'vergi_dairesi', 'fatura_adresi'];

    private string $requestToken;

    /** @var array{order: Order, url: string, teslimat: array<string, string>}|null */
    private ?array $placed = null;

    private ?string $formUrl = null;

    private bool $otpSent = false;

    public function __construct(
        private StoreConfig $store,
        private CartService $cart,
        private CartPricingService $pricing,
        private CheckoutCalculator $calculator,
    ) {
        $this->requestToken = (string) Str::uuid();
    }

    /** @return array{order: Order, url: string, teslimat: array<string, string>}|null */
    public function placed(): ?array
    {
        return $this->placed;
    }

    public function formUrl(): ?string
    {
        return $this->formUrl;
    }

    public function otpSent(): bool
    {
        return $this->otpSent;
    }

    public function awaitingCode(): bool
    {
        return is_array(session(self::OTP_KEY));
    }

    public static function forgetSession(): void
    {
        session()->forget([self::DRAFT_KEY, self::PREFILL_KEY, self::SUMMARY_KEY, self::OTP_KEY]);
    }

    /** @return list<string> */
    public function paymentIds(): array
    {
        return array_column($this->store->paymentMethods(), 'id');
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function update(array $args): array
    {
        if ($this->cart->isEmpty()) {
            return ['hazir' => false, 'not' => 'Sepet boş. Önce add_to_cart ile ürünü sepete ekle.'];
        }
        if ($this->awaitingCode()) {
            session()->forget(self::OTP_KEY);
        }

        $check = $this->validate($this->merge($args));
        if (! $check['tamam']) {
            session()->forget(self::SUMMARY_KEY);

            return $this->incompleteResult($check);
        }

        return $this->summaryResult($check['temiz']);
    }

    /** @return array<string, mixed> */
    public function create(bool $confirmed, string $userText): array
    {
        if ($this->cart->isEmpty()) {
            return ['hata' => 'sepet_bos', 'not' => 'Sepet boş; sipariş oluşturulamaz. Ürünü önce add_to_cart ile ekle.'];
        }
        if (! $confirmed || $this->soundsLikeRefusal($userText)) {
            return ['hata' => 'onay_gerekli', 'not' => 'Önce özeti gösterip müşteriden açık onay al.'];
        }

        $check = $this->validate((array) session(self::DRAFT_KEY, []));
        if (! $check['tamam']) {
            return $this->incompleteResult($check);
        }

        $clean = $check['temiz'];
        $summary = $this->summary($clean);
        $shown = (array) session(self::SUMMARY_KEY, []);
        if (($shown['hash'] ?? null) !== $summary['hash'] || ($shown['token'] ?? null) === $this->requestToken) {
            return [
                'hata' => 'ozet_onaylanmadi',
                ...$this->summaryResult($clean),
                'not' => 'Sipariş oluşturulmadı. Müşteri bu özeti henüz görmedi veya sepet/tutar değişti. Özeti yaz ve onay iste; onay gelince create_order çağır.',
            ];
        }

        $stockErrors = $this->cart->stockErrors();
        if ($stockErrors !== []) {
            return ['hata' => 'stok', 'mesaj' => $stockErrors[0], 'not' => 'Stok sorununu müşteriye aktar; başka ürün veya adet öner.'];
        }

        if ($duplicate = $this->duplicateGuard($clean, $summary['totals']['total'])) {
            return $duplicate;
        }

        if ($this->needsCode($clean['odeme_yontemi'])) {
            return $this->sendCode($clean, $summary['hash'], false);
        }

        return $this->place($clean);
    }

    /** @return array<string, mixed> */
    public function verifyCode(string $code): array
    {
        $pending = session(self::OTP_KEY);
        if (! is_array($pending)) {
            return ['hata' => 'bekleyen_kod_yok', 'not' => 'Doğrulama bekleyen sipariş yok.'];
        }
        if ((int) $pending['expires_at'] < time()) {
            return ['hata' => 'kod_suresi_doldu', 'not' => 'Kodun süresi doldu; müşteri isterse resend_order_code ile yeni kod gönder.'];
        }
        if ((int) $pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            session()->forget(self::OTP_KEY);

            return ['hata' => 'deneme_siniri', 'not' => 'Çok fazla hatalı deneme; işlem durduruldu. handoff_to_human ile ekibe aktar.'];
        }

        $digits = preg_replace('/\D/', '', $code);
        if (! hash_equals((string) $pending['hash'], hash('sha256', $digits))) {
            session()->put(self::OTP_KEY.'.attempts', (int) $pending['attempts'] + 1);

            return ['hata' => 'kod_hatali', 'not' => 'Kod hatalı; müşteriden telefonuna gelen 6 haneli kodu tekrar yazmasını iste.'];
        }

        $check = $this->validate((array) session(self::DRAFT_KEY, []));
        if (! $check['tamam'] || $this->summary($check['temiz'])['hash'] !== $pending['summary_hash']) {
            session()->forget(self::OTP_KEY);

            return ['hata' => 'sepet_degisti', 'not' => 'Kod gönderildikten sonra sepet veya bilgiler değişti. update_order_details ile güncel özeti gösterip tekrar onay al.'];
        }

        session()->forget(self::OTP_KEY);

        return $this->place($check['temiz']);
    }

    /** @return array<string, mixed> */
    public function resendCode(): array
    {
        $pending = session(self::OTP_KEY);
        if (! is_array($pending)) {
            return ['hata' => 'bekleyen_kod_yok', 'not' => 'Doğrulama bekleyen sipariş yok.'];
        }
        if (time() - (int) $pending['sent_at'] < 60) {
            return ['hata' => 'cok_hizli', 'not' => 'Yeni kod için 60 saniye beklenmeli.'];
        }

        $check = $this->validate((array) session(self::DRAFT_KEY, []));
        if (! $check['tamam']) {
            return $this->incompleteResult($check);
        }

        return $this->sendCode($check['temiz'], (string) $pending['summary_hash'], true);
    }

    /**
     * Her turda modele sipariş bilgilerinin durumunu verir; geçmiş kısalsa bile baştan sormasın.
     */
    public function status(): ?string
    {
        if ($this->awaitingCode()) {
            return 'SİPARİŞ: Müşterinin telefonuna doğrulama kodu gönderildi. Kod yazarsa verify_order_code çağır; gelmediyse resend_order_code.';
        }

        $draft = (array) session(self::DRAFT_KEY, []);
        if ($draft === [] || $this->cart->isEmpty()) {
            return null;
        }

        $check = $this->validate($draft);
        if ($check['tamam']) {
            return session()->has(self::SUMMARY_KEY)
                ? 'SİPARİŞ: Bilgiler tamam, özet müşteriye gösterildi. Müşteri açıkça onaylarsa create_order(customer_confirmed=true) çağır; değişiklik isterse yalnızca o alanla update_order_details çağır.'
                : 'SİPARİŞ: Bilgiler tamam. update_order_details çağırıp özeti göster ve onay iste.';
        }

        return 'SİPARİŞ (bilgi toplanıyor): Alınan: '.($check['alinan'] ? implode(', ', $check['alinan']) : 'yok')
            .'. Eksik: '.($check['eksik'] ? implode(', ', $check['eksik']) : 'yok')
            .'. Hatalı: '.($check['hatali'] ? implode('; ', $check['hatali']) : 'yok')
            .'. Alınanları tekrar isteme; yalnızca eksik/hatalı olanları adıyla sor.';
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     */
    private function merge(array $args): array
    {
        $previous = (array) session(self::DRAFT_KEY, []);
        $incoming = [];
        foreach (self::TEXT_FIELDS as $key) {
            $value = Str::limit(trim(strip_tags((string) ($args[$key] ?? ''))), in_array($key, ['adres', 'fatura_adresi'], true) ? 500 : 190, '');
            if ($value !== '') {
                $incoming[$key] = $value;
            }
        }
        if (array_key_exists('kurumsal_fatura', $args)) {
            $incoming['kurumsal_fatura'] = filter_var($args['kurumsal_fatura'], FILTER_VALIDATE_BOOL) ? '1' : '';
        }
        if (! isset($incoming['soyad']) && str_contains($incoming['ad'] ?? '', ' ')) {
            $incoming['soyad'] = Str::afterLast($incoming['ad'], ' ');
            $incoming['ad'] = Str::beforeLast($incoming['ad'], ' ');
        }
        if (isset($incoming['il']) && ! isset($incoming['ilce'])) {
            $cities = config('turkiye.cities', []);
            $city = $this->matchPlace($incoming['il'], array_keys($cities));
            if ($city === null || $this->matchPlace($previous['ilce'] ?? '', $cities[$city] ?? []) === null) {
                $incoming['ilce'] = '';
            }
        }

        $draft = array_filter([...$previous, ...$incoming], fn ($v) => $v !== '');
        session()->put(self::DRAFT_KEY, $draft);

        return $draft;
    }

    /**
     * @param  array<string, string>  $draft
     * @return array{tamam: bool, temiz: array<string, string>, alinan: list<string>, eksik: list<string>, hatali: list<string>}
     */
    private function validate(array $draft): array
    {
        $paymentIds = $this->paymentIds();
        if (($draft['odeme_yontemi'] ?? '') === '' && count($paymentIds) === 1) {
            $draft['odeme_yontemi'] = $paymentIds[0];
        }

        $fields = self::FIELDS;
        $invoice = ($draft['kurumsal_fatura'] ?? '') === '1';
        if ($invoice) {
            $fields += self::INVOICE_FIELDS;
        }

        $missing = [];
        $invalid = [];
        foreach ($fields as $key => $label) {
            if (($draft[$key] ?? '') === '') {
                $missing[$key] = $label;
            }
        }

        $phoneDigits = preg_replace('/\D/', '', $draft['telefon'] ?? '');
        $phone = match (true) {
            strlen($phoneDigits) === 10 => '0'.$phoneDigits,
            strlen($phoneDigits) === 12 && str_starts_with($phoneDigits, '90') => '0'.substr($phoneDigits, 2),
            default => $phoneDigits,
        };
        if (! isset($missing['telefon']) && ! preg_match('/^05\d{9}$/', $phone)) {
            $invalid['telefon'] = 'Cep telefonu 05 ile başlayan 11 hane olmalı, ör. 0532 123 45 67 (yazılan: '.$draft['telefon'].')';
        }

        $email = mb_strtolower($draft['eposta'] ?? '');
        if (! isset($missing['eposta']) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid['eposta'] = 'E-posta adresi geçersiz (yazılan: '.$draft['eposta'].')';
        }

        $cities = config('turkiye.cities', []);
        $city = isset($missing['il']) ? null : $this->matchPlace($draft['il'], array_keys($cities));
        if (! isset($missing['il']) && $city === null) {
            $invalid['il'] = 'İl tanınmadı (yazılan: '.$draft['il'].')'.$this->suggestionText($draft['il'], array_keys($cities));
        }
        $district = $city !== null && ! isset($missing['ilce']) ? $this->matchPlace($draft['ilce'], $cities[$city] ?? []) : null;
        if ($city !== null && ! isset($missing['ilce']) && $district === null) {
            $invalid['ilce'] = $draft['ilce'].' ilçesi '.$city.' ilinde bulunamadı'.$this->suggestionText($draft['ilce'], $cities[$city] ?? []);
        }

        if (! isset($missing['adres']) && mb_strlen($draft['adres']) < 10) {
            $invalid['adres'] = 'Açık adres çok kısa; mahalle, cadde/sokak, bina no ve daire gerekli';
        }

        if (! isset($missing['odeme_yontemi']) && ! in_array($draft['odeme_yontemi'], $paymentIds, true)) {
            $invalid['odeme_yontemi'] = 'Bu ödeme yöntemi şu an kullanılamıyor';
        }

        $shippingIds = array_column($this->store->shippingMethods(), 'id');
        $shipping = in_array($draft['kargo_yontemi'] ?? '', $shippingIds, true)
            ? $draft['kargo_yontemi']
            : (in_array('standart', $shippingIds, true) ? 'standart' : ($shippingIds[0] ?? ''));

        $received = array_values(array_diff_key($fields, $missing, $invalid));

        return [
            'tamam' => $missing === [] && $invalid === [] && $shipping !== '',
            'temiz' => array_filter([
                'ad' => $draft['ad'] ?? '',
                'soyad' => $draft['soyad'] ?? '',
                'eposta' => $email,
                'telefon' => $phone,
                'il' => $city ?? '',
                'ilce' => $district ?? '',
                'adres' => $draft['adres'] ?? '',
                'posta_kodu' => preg_replace('/\D/', '', Str::limit($draft['posta_kodu'] ?? '', 10, '')),
                'odeme_yontemi' => $draft['odeme_yontemi'] ?? '',
                'kargo_yontemi' => $shipping,
                'kurumsal_fatura' => $invoice ? '1' : '',
                'firma_adi' => $invoice ? ($draft['firma_adi'] ?? '') : '',
                'vergi_numarasi' => $invoice ? ($draft['vergi_numarasi'] ?? '') : '',
                'vergi_dairesi' => $invoice ? ($draft['vergi_dairesi'] ?? '') : '',
                'fatura_adresi' => $invoice ? ($draft['fatura_adresi'] ?? '') : '',
            ], fn ($v) => $v !== ''),
            'alinan' => $received,
            'eksik' => array_values($missing),
            'hatali' => array_values($invalid),
        ];
    }

    /**
     * @param  array{alinan: list<string>, eksik: list<string>, hatali: list<string>}  $check
     * @return array<string, mixed>
     */
    private function incompleteResult(array $check): array
    {
        return array_filter([
            'hazir' => false,
            'alinan' => $check['alinan'],
            'eksik' => $check['eksik'],
            'hatali' => $check['hatali'],
            'odeme_secenekleri' => in_array(self::FIELDS['odeme_yontemi'], $check['eksik'], true) ? $this->paymentOptions() : [],
            'not' => 'Müşteriye eksik ve hatalı alanları adıyla madde madde yaz (hatalıysa nedenini de söyle; öneri varsa sun). Ödeme yöntemi eksikse seçenekleri adıyla say. Alınan bilgileri tekrar isteme, listeyi baştan sayma.',
        ], fn ($v) => $v !== []);
    }

    /**
     * @param  array<string, string>  $clean
     * @return array<string, mixed>
     */
    private function summaryResult(array $clean): array
    {
        $summary = $this->summary($clean);
        session()->put(self::SUMMARY_KEY, ['hash' => $summary['hash'], 'token' => $this->requestToken]);
        session()->put(self::PREFILL_KEY, array_intersect_key($clean, array_flip(['ad', 'soyad', 'eposta', 'telefon', 'il', 'ilce', 'adres', 'posta_kodu'])));
        app(AnalyticsTracker::class)->updateCheckoutContact(request(), $this->cart, array_intersect_key($clean, array_flip(['ad', 'soyad', 'eposta', 'telefon'])));

        $totals = $summary['totals'];
        $shipping = collect($this->store->shippingMethods())->firstWhere('id', $clean['kargo_yontemi']);
        $otherShipping = collect($this->store->shippingMethods())
            ->where('id', '!=', $clean['kargo_yontemi'])
            ->map(fn (array $m) => ['id' => $m['id'], 'ad' => $m['name'], 'sure' => $m['eta'] ?: null])
            ->values()->all();

        return array_filter([
            'hazir' => true,
            'ozet' => array_filter([
                'urunler' => collect($this->cart->lines())
                    ->map(fn (array $line) => $line['product']->name.' × '.$line['quantity'].': '.$this->money((float) $line['line_total']))
                    ->values()->all(),
                'ara_toplam' => $this->money($totals['subtotal'] + $totals['discount']),
                'indirim' => $totals['discount'] > 0 ? '-'.$this->money($totals['discount']) : null,
                'kargo' => ($shipping['name'] ?? $clean['kargo_yontemi']).(($shipping['eta'] ?? '') !== '' ? ' ('.$shipping['eta'].')' : '').': '.($totals['shipping'] > 0 ? $this->money($totals['shipping']) : 'Ücretsiz'),
                'kapida_odeme_ucreti' => $totals['cod_fee'] > 0 ? $this->money($totals['cod_fee']) : null,
                'kdv' => $totals['vat'] > 0 ? $this->money($totals['vat']) : null,
                'toplam' => $this->money($totals['total']),
                'odeme_yontemi' => $this->paymentName($clean['odeme_yontemi']),
                'teslimat' => $clean['ad'].' '.$clean['soyad'].', '.$clean['telefon'].', '.$clean['adres'].', '.$clean['ilce'].' / '.$clean['il'],
                'e_posta' => $clean['eposta'],
                'kurumsal_fatura' => isset($clean['kurumsal_fatura']) ? $clean['firma_adi'].', VN '.$clean['vergi_numarasi'].', '.$clean['vergi_dairesi'] : null,
            ], fn ($v) => $v !== null),
            'sozlesmeler' => [
                'on_bilgilendirme' => route('pages.show', 'on-bilgilendirme'),
                'mesafeli_satis' => route('pages.show', 'mesafeli-satis-sozlesmesi'),
            ],
            'diger_kargo_secenekleri' => $otherShipping,
            'not' => 'Siparişi OLUŞTURMADAN önce bu özeti madde madde yaz (ürünler, kargo, varsa kapıda ödeme ücreti ve KDV, toplam, ödeme yöntemi, teslimat). Onaylayınca Ön Bilgilendirme Formu ve Mesafeli Satış Sözleşmesi\'ni kabul etmiş olacağını söyleyip iki linki düz URL olarak ver ve "Bilgiler doğruysa siparişi onaylıyor musunuz?" diye sor. Açık onay gelirse create_order(customer_confirmed=true) çağır.',
        ], fn ($v) => $v !== []);
    }

    /**
     * @param  array<string, string>  $clean
     * @return array{hash: string, totals: array{subtotal: float, discount: float, shipping: float, cod_fee: float, vat: float, total: float}}
     */
    private function summary(array $clean): array
    {
        $pricing = $this->pricing->breakdown();
        $totals = $this->calculator->totals(
            $pricing['subtotal'],
            $pricing['total_discount'],
            $clean['kargo_yontemi'],
            $clean['odeme_yontemi'],
            $pricing['free_shipping'],
        );
        $lines = collect($this->cart->lines())
            ->map(fn (array $line) => [$line['product']->id, $line['quantity'], (float) $line['line_total']])
            ->values()->all();

        return [
            'hash' => hash('sha256', json_encode([$lines, $totals, $clean])),
            'totals' => $totals,
        ];
    }

    /**
     * @param  array<string, string>  $clean
     * @return array<string, mixed>|null
     */
    private function duplicateGuard(array $clean, float $total): ?array
    {
        $tail = substr($clean['telefon'], -10);
        $recent = Order::query()
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->latest('id')
            ->limit(50)
            ->get()
            ->first(fn (Order $order) => substr(preg_replace('/\D/', '', (string) $order->phone), -10) === $tail
                && abs((float) $order->total - $total) < 0.01);

        if (! $recent) {
            return null;
        }

        if ($recent->isPendingPayment()) {
            $this->placed = ['order' => $recent, 'url' => $recent->paymentPageUrl(), 'teslimat' => $clean];

            return [
                'durum' => 'odeme_bekleyen_siparis_var',
                'siparis_no' => $recent->order_number,
                'toplam' => $this->money((float) $recent->total),
                'not' => 'Bu sipariş birkaç dakika önce oluşturulmuş ve ödemesi bekleniyor; yeni sipariş açılmadı. Müşteriye sipariş numarasını ver ve güvenli ödeme sayfasına yönlendirildiğini söyle.',
            ];
        }

        return [
            'hata' => 'mukerrer_siparis',
            'siparis_no' => $recent->order_number,
            'not' => 'Aynı telefon ve tutarla birkaç dakika önce sipariş alınmış; ikinci sipariş açılmadı. Müşteriye bunu söyle; ek sipariş istiyorsa handoff_to_human ile ekibe aktar.',
        ];
    }

    private function needsCode(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['kapida_odeme', 'havale'], true)
            && app(SmsService::class)->isEnabled()
            && SiteSetting::get('sms_provider', config('carriers.sms.provider', 'log')) === 'netgsm';
    }

    /**
     * @param  array<string, string>  $clean
     * @return array<string, mixed>
     */
    private function sendCode(array $clean, string $summaryHash, bool $isResend): array
    {
        $pending = (array) session(self::OTP_KEY, []);
        $sends = $isResend ? (int) ($pending['sends'] ?? 0) : 0;
        if ($sends >= self::OTP_MAX_SENDS) {
            return ['hata' => 'gonderim_siniri', 'not' => 'Kod gönderim sınırı doldu; handoff_to_human ile ekibe aktar.'];
        }

        $code = (string) random_int(100000, 999999);
        $sent = app(SmsService::class)->send(
            $clean['telefon'],
            SiteName::get().' siparis dogrulama kodunuz: '.$code.'. Kod 5 dakika gecerlidir.',
        );
        if (! $sent['ok']) {
            return ['hata' => 'sms_gonderilemedi', 'not' => 'Doğrulama SMS\'i gönderilemedi. Müşteriye kredi kartıyla ödemeyi önerebilir ya da handoff_to_human ile ekibe aktarabilirsin.'];
        }

        session()->put(self::OTP_KEY, [
            'hash' => hash('sha256', $code),
            'summary_hash' => $summaryHash,
            'expires_at' => time() + self::OTP_TTL,
            'sent_at' => time(),
            'sends' => $sends + 1,
            'attempts' => 0,
        ]);
        $this->otpSent = true;

        return [
            'durum' => 'sms_kodu_gonderildi',
            'telefon' => substr($clean['telefon'], 0, 4).' *** ** '.substr($clean['telefon'], -2),
            'not' => 'Müşteriden telefonuna gelen 6 haneli kodu bu sohbete yazmasını iste. Kod 5 dakika geçerli.',
        ];
    }

    /**
     * @param  array<string, string>  $clean
     * @return array<string, mixed>
     */
    private function place(array $clean): array
    {
        $teslimat = [
            'ad' => $clean['ad'],
            'soyad' => $clean['soyad'],
            'eposta' => $clean['eposta'],
            'telefon' => $clean['telefon'],
            'il' => $clean['il'],
            'ilce' => $clean['ilce'],
            'adres' => $clean['adres'],
            'postaKodu' => $clean['posta_kodu'] ?? null,
        ];
        if (isset($clean['kurumsal_fatura'])) {
            $teslimat['kurumsalFatura'] = [
                'firmaAdi' => $clean['firma_adi'],
                'vergiNumarasi' => $clean['vergi_numarasi'],
                'vergiDairesi' => $clean['vergi_dairesi'],
                'faturaAdresi' => $clean['fatura_adresi'],
            ];
        }

        try {
            $result = app(OrderService::class)->create($teslimat, $clean['kargo_yontemi'], $clean['odeme_yontemi']);
        } catch (Throwable $e) {
            if (! $e instanceof \RuntimeException) {
                report($e);
            }
            $this->formUrl = session()->has(self::PREFILL_KEY) ? route('support-chat.checkout') : null;

            return [
                'hata' => 'siparis_olusturulamadi',
                'mesaj' => $e instanceof \RuntimeException ? $e->getMessage() : null,
                'not' => 'Sipariş oluşturulamadı. Müşteriye kısaca söyle; bilgileri doldurulmuş ödeme formundan tamamlayabileceğini (arayüz buton gösterir) veya ekibe aktarabileceğini belirt.',
            ];
        }

        $order = $result['order'];
        app(AnalyticsTracker::class)->attachOrder(request(), $order);
        session(['last_order_email' => $order->email]);
        self::forgetSession();

        $card = $result['payment_url'] !== null;
        $this->placed = [
            'order' => $order,
            'url' => $result['payment_url'] ?? route('checkout.success', ['order' => $order->order_number]),
            'teslimat' => $clean,
        ];

        return array_filter([
            'durum' => $card ? 'odeme_sayfasina_yonlendiriliyor' : 'siparis_olusturuldu',
            'siparis_no' => $order->order_number,
            'toplam' => $this->money((float) $order->total),
            'odeme_yontemi' => $this->paymentName($clean['odeme_yontemi']),
            'havale_notu' => $clean['odeme_yontemi'] === 'havale' ? __('shop.bank_transfer_note') : null,
            'not' => $card
                ? 'Müşteriye sipariş numarasını ver; birkaç saniye içinde güvenli ödeme sayfasına yönlendirileceğini ve kart bilgisini yalnızca orada gireceğini söyle. Ödeme tamamlanınca sipariş onaylanır.'
                : 'Müşteriye siparişinin alındığını, sipariş numarasını ve toplam tutarı söyle; onay e-postası gönderildiğini belirt ve teşekkür et.',
        ], fn ($v) => $v !== null);
    }

    /** @return list<array{id: string, ad: string, aciklama: string}> */
    private function paymentOptions(): array
    {
        return collect($this->store->paymentMethods())
            ->map(fn (array $m) => ['id' => $m['id'], 'ad' => $m['name'], 'aciklama' => $m['desc']])
            ->values()->all();
    }

    private function paymentName(string $id): string
    {
        return collect($this->store->paymentMethods())->firstWhere('id', $id)['name'] ?? $id;
    }

    private function soundsLikeRefusal(string $text): bool
    {
        $text = mb_strtolower($text);

        return (bool) preg_match('/\b(hayır|hayir|istemiyorum|vazgeç|vazgec|iptal|bekle|değiştir|degistir|düzelt|duzelt|yanlış|yanlis)/u', $text)
            && ! preg_match('/\b(evet|onaylıyorum|onayliyorum|onay)/u', $text);
    }

    /**
     * @param  list<string>  $options
     */
    private function suggestionText(string $value, array $options): string
    {
        $needle = $this->ascii($value);
        if ($needle === '') {
            return '';
        }

        $scored = collect($options)
            ->map(fn (string $option) => [$option, levenshtein($needle, $this->ascii($option))])
            ->filter(fn (array $row) => $row[1] <= 3)
            ->sortBy(1)
            ->take(3)
            ->pluck(0)
            ->all();

        return $scored === [] ? '' : '; bunu mu kastettiniz: '.implode(', ', $scored);
    }

    private function ascii(string $value): string
    {
        return strtr($this->normalizePlace($value), ['İ' => 'I', 'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'Ö' => 'O', 'Ç' => 'C']);
    }

    private function normalizePlace(string $value): string
    {
        return mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], trim(preg_replace('/\s+(ili|ilçesi|ilcesi)$/iu', '', $value))), 'UTF-8');
    }

    /**
     * @param  list<string>  $options
     */
    private function matchPlace(string $value, array $options): ?string
    {
        $needle = $this->normalizePlace($value);
        if ($needle === '') {
            return null;
        }

        foreach ($options as $option) {
            if ($this->normalizePlace($option) === $needle) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($this->ascii($option) === $this->ascii($value)) {
                return $option;
            }
        }

        return null;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' ₺';
    }
}
