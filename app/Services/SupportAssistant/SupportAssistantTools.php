<?php

namespace App\Services\SupportAssistant;

use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use App\Services\CatalogQuery;
use App\Services\Payment\InstallmentOptionsService;
use App\Services\PumpSelection\PumpRecommendationService;
use App\Services\StoreConfig;
use App\Support\OrderStatus;
use App\Support\ProductSpecs;
use App\Support\PumpSelectorUiConfig;
use App\Support\RichContent;
use App\Support\SupportAssistantConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class SupportAssistantTools
{
    private const MAX_ORDER_LOOKUPS = 5;

    private const MAX_CARDS = 4;

    /** @var array<string, array<string, mixed>> */
    private array $cards = [];

    private ?string $cardSource = null;

    private ?string $handoffSummary = null;

    private ?string $handoffReason = null;

    private int $orderLookups = 0;

    public const CHECKOUT_SESSION_KEY = 'support_chat_checkout';

    public const CHECKOUT_DRAFT_KEY = 'support_chat_checkout_draft';

    private const CHECKOUT_FIELDS = [
        'ad' => 'Ad',
        'soyad' => 'Soyad',
        'telefon' => 'Cep telefonu',
        'eposta' => 'E-posta',
        'il' => 'İl',
        'ilce' => 'İlçe',
        'adres' => 'Açık adres',
    ];

    /** @var array<string, string>|null */
    private ?array $checkoutPrefill = null;

    private ?int $cartCount = null;

    public function __construct(
        private StoreConfig $store,
        private InstallmentOptionsService $installments,
        private PumpRecommendationService $pumps,
    ) {}

    public function setOrderLookups(int $count): void
    {
        $this->orderLookups = $count;
    }

    public function orderLookups(): int
    {
        return $this->orderLookups;
    }

    /** @return list<array<string, mixed>> */
    public function cards(): array
    {
        return array_values($this->cards);
    }

    public function handoffRequested(): bool
    {
        return $this->handoffReason !== null;
    }

    public function handoffReason(): ?string
    {
        return $this->handoffReason;
    }

    public function handoffSummary(): ?string
    {
        return $this->handoffSummary;
    }

    /** @return array<string, string>|null */
    public function checkoutPrefill(): ?array
    {
        return $this->checkoutPrefill;
    }

    public function cartCount(): ?int
    {
        return $this->cartCount;
    }

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        $applications = array_keys(config('pump_selector.applications', []));

        return [
            $this->fn('search_products', 'Mağaza kataloğunda ürün ve kategori arar. Ürün adı, marka, model, stok kodu veya ürün türü (ör. "dalgıç pompa 1 hp", "sumak hidrofor") ile kullan. Türkçe karakterli yaz.', [
                'query' => ['type' => 'string', 'description' => 'Arama ifadesi'],
                'min_price' => ['type' => 'number', 'description' => 'En düşük fiyat (TL), isteğe bağlı'],
                'max_price' => ['type' => 'number', 'description' => 'En yüksek fiyat (TL), isteğe bağlı'],
                'only_in_stock' => ['type' => 'boolean', 'description' => 'Yalnızca stoktakiler'],
            ], ['query']),
            $this->fn('get_product_details', 'Tek ürünün güncel fiyatını, stok durumunu, teknik özelliklerini ve açıklamasını getirir.', [
                'product' => ['type' => 'string', 'description' => 'Ürün slug, stok kodu (SKU) veya tam ürün adı'],
            ], ['product']),
            $this->fn('get_store_info', 'Mağaza bilgilerini getirir: kargo, iade/değişim, ödeme yöntemleri, taksit, sık sorulan sorular. topic=iletisim: adres, konum ("yeriniz nerede"), çalışma saatleri, telefon, e-posta, WhatsApp ve firma hakkında bilgi.', [
                'topic' => ['type' => 'string', 'enum' => ['kargo', 'iade', 'odeme', 'taksit', 'iletisim', 'sss', 'genel']],
                'amount' => ['type' => 'number', 'description' => 'Taksit hesabı için sepet/ürün tutarı (TL)'],
            ], ['topic']),
            $this->fn('check_order_status', 'Sipariş durumunu sorgular. Sipariş numarası ve siparişte kullanılan e-posta birlikte zorunludur.', [
                'order_number' => ['type' => 'string'],
                'email' => ['type' => 'string'],
            ], ['order_number', 'email']),
            $this->fn('recommend_pump', 'Kullanım senaryosuna göre debi/basma ihtiyacını hesaplar ve katalogdan uygun pompa/fan önerir. Eksik bilgi dönerse kullanıcıya sor.', [
                'application' => ['type' => 'string', 'enum' => $applications, 'description' => 'hydrofor_apartment: apartman hidroforu, hydrofor_villa: müstakil ev/villa, submersible_well: derin kuyu, jet_shallow: sığ kuyu/depo emişli, drainage: drenaj/su tahliyesi, septic: foseptik, irrigation: bahçe/tarla sulama, circulation: kalorifer sirkülasyon, industrial_fan: sanayi fanı'],
                'apartments' => ['type' => 'integer', 'description' => 'Daire sayısı (1-500)'],
                'floors' => ['type' => 'integer', 'description' => 'Kat sayısı (1-40)'],
                'bathrooms' => ['type' => 'integer', 'description' => 'Banyo sayısı (1-20)'],
                'depth' => ['type' => 'integer', 'description' => 'Kuyu derinliği / su seviyesi metre (1-200)'],
                'suction_depth' => ['type' => 'integer', 'description' => 'Emiş derinliği metre (1-8)'],
                'volume_m3' => ['type' => 'integer', 'description' => 'Boşaltılacak su hacmi m³ (1-500)'],
                'drain_hours' => ['type' => 'integer', 'description' => 'Boşaltma süresi saat (1-24)'],
                'distance' => ['type' => 'integer', 'description' => 'Basma mesafesi metre (5-100)'],
                'area_m2' => ['type' => 'integer', 'description' => 'Sulama alanı m² (50-50000)'],
                'heated_area_m2' => ['type' => 'integer', 'description' => 'Isıtılan alan m² (40-2000)'],
                'space_m2' => ['type' => 'integer', 'description' => 'Fan için mekân alanı m² (10-5000)'],
                'height_m' => ['type' => 'integer', 'description' => 'Tavan yüksekliği metre (2-15)'],
                'usage' => ['type' => 'string', 'enum' => ['household', 'garden', 'agriculture', 'livestock']],
                'method' => ['type' => 'string', 'enum' => ['sprinkler', 'drip']],
                'lift' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                'environment' => ['type' => 'string', 'enum' => ['workshop', 'warehouse', 'kitchen']],
            ], ['application']),
            $this->fn('add_to_cart', 'Ürünü müşterinin sepetine ekler. Yalnızca müşteri satın almak/sepete eklemek istediğini açıkça söylediğinde kullan.', [
                'product' => ['type' => 'string', 'description' => 'Ürün slug, stok kodu (SKU) veya tam ürün adı'],
                'quantity' => ['type' => 'integer', 'description' => 'Adet (1-20), varsayılan 1'],
            ], ['product']),
            $this->fn('prepare_checkout', 'Müşterinin teslimat bilgilerini sipariş formuna kaydeder; hepsi tamam ve geçerliyse arayüz müşteriyi ödeme sayfasına yönlendirir. Sepette ürün olmalı. Müşteri teslimat bilgisi yazdığı her mesajda, eksik olsa bile, yalnızca o mesajda verdiği alanlarla çağır; önceki bilgiler sistemde saklanır. Araç eksik ve hatalı alanları döndürür. Bilgileri müşterinin yazdığı gibi aktar, uydurma.', [
                'ad' => ['type' => 'string', 'description' => 'Ad'],
                'soyad' => ['type' => 'string', 'description' => 'Soyad'],
                'telefon' => ['type' => 'string', 'description' => 'Telefon numarası'],
                'eposta' => ['type' => 'string', 'description' => 'E-posta adresi'],
                'il' => ['type' => 'string', 'description' => 'İl'],
                'ilce' => ['type' => 'string', 'description' => 'İlçe'],
                'adres' => ['type' => 'string', 'description' => 'Açık adres: mahalle, cadde/sokak, bina no, daire'],
                'posta_kodu' => ['type' => 'string', 'description' => 'Posta kodu, isteğe bağlı'],
            ], []),
            $this->fn('handoff_to_human', 'Müşteriyi WhatsApp üzerinden satış/destek ekibine aktarır. Bilgi yoksa, müşteri temsilci isterse, şikâyet, iade, hasar, toptan/proje teklifi, özel fiyat, montaj/servis gibi konularda çağır.', [
                'summary' => ['type' => 'string', 'description' => 'Temsilcinin göreceği 1-2 cümlelik Türkçe özet (müşterinin ne istediği, ilgili ürün)'],
                'reason' => ['type' => 'string', 'enum' => ['bilgi_yok', 'musteri_istegi', 'siparis_sorunu', 'teklif_toptan', 'sikayet_iade', 'diger']],
            ], ['summary', 'reason']),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args): array
    {
        try {
            return match ($name) {
                'search_products' => $this->searchProducts($args),
                'get_product_details' => $this->productDetails((string) ($args['product'] ?? '')),
                'get_store_info' => $this->storeInfo((string) ($args['topic'] ?? 'genel'), (float) ($args['amount'] ?? 0)),
                'check_order_status' => $this->orderStatus((string) ($args['order_number'] ?? ''), (string) ($args['email'] ?? '')),
                'recommend_pump' => $this->recommendPump($args),
                'handoff_to_human' => $this->handoff((string) ($args['summary'] ?? ''), (string) ($args['reason'] ?? 'diger')),
                'add_to_cart' => $this->addToCart((string) ($args['product'] ?? ''), (int) ($args['quantity'] ?? 1)),
                'prepare_checkout' => $this->prepareCheckout($args),
                default => ['hata' => 'Bilinmeyen araç.'],
            };
        } catch (Throwable $e) {
            report($e);

            return ['hata' => 'Bilgi şu an alınamadı. Kullanıcıya net bilgi veremediğini söyle ve handoff_to_human öner.'];
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchProducts(array $args): array
    {
        $query = Str::limit(trim((string) ($args['query'] ?? '')), 80, '');
        $terms = collect(preg_split('/\s+/u', mb_strtolower($query)) ?: [])
            ->map(fn ($t) => trim($t, " \t,.;:!?\"'()"))
            ->filter(fn ($t) => mb_strlen($t) >= 2)
            ->unique()
            ->take(6)
            ->values();

        if ($terms->isEmpty()) {
            return ['hata' => 'Arama ifadesi boş.'];
        }

        $base = CatalogQuery::products()->with('brand:id,name');
        if (isset($args['min_price']) && is_numeric($args['min_price'])) {
            $base->where('price', '>=', (float) $args['min_price']);
        }
        if (isset($args['max_price']) && is_numeric($args['max_price']) && (float) $args['max_price'] > 0) {
            $base->where('price', '<=', (float) $args['max_price']);
        }
        if (! empty($args['only_in_stock'])) {
            $base->where('stock', '>', 0);
        }

        $matchTerm = function (Builder $q, string $term): void {
            $like = '%'.$term.'%';
            $q->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like));
        };

        $strict = (clone $base);
        foreach ($terms as $term) {
            $strict->where(fn (Builder $q) => $matchTerm($q, $term));
        }
        $products = $this->ordered($strict->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ['%'.$query.'%']))->limit(6)->get();
        $approximate = false;

        if ($products->isEmpty()) {
            $loose = $terms->filter(fn ($t) => mb_strlen($t) >= 3);
            if ($loose->isNotEmpty()) {
                $products = $this->ordered((clone $base)->where(function (Builder $q) use ($loose, $matchTerm) {
                    foreach ($loose as $term) {
                        $q->orWhere(fn (Builder $inner) => $matchTerm($inner, $term));
                    }
                }))->limit(6)->get();
                $approximate = $products->isNotEmpty();
            }
        }

        if (! $approximate && $products->isNotEmpty()) {
            $this->startCards('search_products');
            foreach ($products->take(self::MAX_CARDS) as $product) {
                $this->addCard($product);
            }
        }

        return [
            'sorgu' => $query,
            'yaklasik_eslesme' => $approximate,
            'not' => $approximate ? 'Tam eşleşme yok; bunlar kelimelerden bazılarıyla eşleşen ürünler. Kullanıcıya birebir aradığı ürün olmayabileceğini belirt.' : null,
            'urunler' => $products->map(fn (Product $p) => $this->productSummary($p))->all(),
            'kategoriler' => $this->matchingCategories($query, $terms->all()),
        ];
    }

    private function ordered(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('featured')
            ->orderBy('name');
    }

    /**
     * @param  list<string>  $terms
     * @return list<array{ad: string, url: string}>
     */
    private function matchingCategories(string $query, array $terms): array
    {
        $categories = Category::query()->where('active', true)
            ->where('name', 'like', '%'.$query.'%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query.'%'])
            ->orderByRaw('LENGTH(name)')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        if ($categories->isEmpty()) {
            $long = array_values(array_filter($terms, fn ($t) => mb_strlen($t) >= 4));
            if ($long !== []) {
                $q = Category::query()->where('active', true);
                foreach ($long as $term) {
                    $q->where('name', 'like', '%'.$term.'%');
                }
                $categories = $q->orderBy('sort_order')->limit(3)->get();
            }
        }

        return $categories->map(fn (Category $c) => ['ad' => (string) $c->name, 'url' => $c->storefrontUrl()])->all();
    }

    /** @return array<string, mixed> */
    private function productDetails(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return ['hata' => 'Ürün belirtilmedi.'];
        }

        $product = $this->findProduct($identifier);

        if (! $product) {
            return ['bulunamadi' => true, 'not' => 'Bu ürün katalogda bulunamadı. search_products ile farklı kelimelerle ara.'];
        }

        $this->startCards('get_product_details');
        $this->addCard($product);

        $data = $this->productSummary($product);
        $data['teknik_ozellikler'] = ProductSpecs::rows(is_array($product->specs) ? $product->specs : null)
            ->take(25)
            ->map(fn (array $row) => $row[0].': '.$row[1])
            ->all();
        $data['kisa_aciklama'] = Str::limit(RichContent::plainText($product->short_description), 400, '…');
        $data['aciklama_ozeti'] = Str::limit(RichContent::plainText($product->description), 1500, '…');
        $data['kategori'] = $product->categories->first()?->name;

        $freeMin = $this->store->freeShippingMin();
        if ($freeMin > 0) {
            $data['kargo'] = (float) $product->price >= $freeMin
                ? 'Ürün tutarı ücretsiz kargo alt limitinin ('.$this->money($freeMin).') üzerinde.'
                : 'Ücretsiz kargo alt limiti '.$this->money($freeMin).'.';
        }

        return $data;
    }

    private function findProduct(string $identifier): ?Product
    {
        $query = CatalogQuery::products()->with(['brand:id,name', 'categories:id,name,slug,parent_id']);

        return (clone $query)->where('slug', $identifier)->first()
            ?? (clone $query)->where('sku', $identifier)->first()
            ?? (clone $query)->where('name', $identifier)->first()
            ?? (clone $query)->where('name', 'like', '%'.$identifier.'%')->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')->first();
    }

    /** @return array<string, mixed> */
    private function addToCart(string $identifier, int $quantity): array
    {
        $identifier = trim($identifier);
        $product = $identifier !== '' ? $this->findProduct($identifier) : null;
        if (! $product) {
            return ['eklendi' => false, 'not' => 'Ürün bulunamadı. search_products ile ürünü bulup tam adıyla tekrar dene veya müşteriye hangi ürünü istediğini sor.'];
        }

        $cart = app(CartService::class);
        $result = $cart->addProduct($product, min(20, max(1, $quantity)));
        if ($result['added'] > 0) {
            app(AnalyticsTracker::class)->trackCartAction(request(), 'cart_add', $product, $result['added']);
        }
        app(AnalyticsTracker::class)->syncCart(request(), $cart);
        $this->cartCount = $cart->count();

        return array_filter([
            'eklendi' => $result['ok'],
            'mesaj' => $result['message'],
            'urun' => $product->name,
            'sepetteki_adet' => $result['quantity'],
            'sepet_ara_toplam' => $cart->isEmpty() ? null : $this->money($cart->subtotal()),
            'not' => $result['ok']
                ? (session()->has(self::CHECKOUT_DRAFT_KEY)
                    ? 'Teslimat bilgileri daha önce alındı; tekrar isteme. Eksik yoksa prepare_checkout çağırıp müşteriyi ödemeye yönlendir, varsa yalnızca eksikleri sor.'
                    : 'Satın alma için teslimat bilgilerini tek mesajda iste: ad soyad, cep telefonu, e-posta, il, ilçe, açık adres.')
                : 'Ürün sepete eklenemedi; mesajı müşteriye aktar.',
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function prepareCheckout(array $args): array
    {
        $cart = app(CartService::class);
        if ($cart->isEmpty()) {
            return ['hazir' => false, 'not' => 'Sepet boş. Önce add_to_cart ile ürünü sepete ekle.'];
        }

        $draft = $this->mergeCheckoutDraft($args);
        $check = $this->validateCheckoutDraft($draft);

        if ($check['eksik'] !== [] || $check['hatali'] !== []) {
            return array_filter([
                'hazir' => false,
                'alinan' => $check['alinan'],
                'eksik' => $check['eksik'],
                'hatali' => $check['hatali'],
                'not' => 'Müşteriye eksik ve hatalı alanları adıyla tek tek yaz (hatalıysa nedenini de söyle). Alınan bilgileri tekrar isteme, tüm listeyi baştan sayma.',
            ], fn ($v) => $v !== []);
        }

        $prefill = $check['temiz'];
        session()->put(self::CHECKOUT_SESSION_KEY, $prefill);
        $this->checkoutPrefill = $prefill;
        $this->cartCount = $cart->count();
        app(AnalyticsTracker::class)->updateCheckoutContact(request(), $cart, [
            'ad' => $prefill['ad'], 'soyad' => $prefill['soyad'], 'eposta' => $prefill['eposta'], 'telefon' => $prefill['telefon'],
        ]);

        return [
            'hazir' => true,
            'sepet' => collect($cart->lines())->map(fn ($line) => $line['product']->name.' × '.$line['quantity'])->values()->all(),
            'sepet_ara_toplam' => $this->money($cart->subtotal()),
            'not' => 'Bilgiler ödeme formuna aktarıldı; arayüz müşteriyi ödeme sayfasına yönlendiriyor. Müşteriye orada kargo ve ödeme yöntemini kontrol edip sözleşmeyi onaylayarak ödemeyi tamamlayacağını söyle. Kişisel bilgileri tekrar yazma, URL yazma, kart bilgisi isteme.',
        ];
    }

    /**
     * Sohbet boyunca verilen teslimat bilgileri oturumda birikir; yeni değer eskisinin yerine geçer.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     */
    private function mergeCheckoutDraft(array $args): array
    {
        $incoming = [];
        foreach ([...array_keys(self::CHECKOUT_FIELDS), 'posta_kodu'] as $key) {
            $value = Str::limit(trim(strip_tags((string) ($args[$key] ?? ''))), $key === 'adres' ? 500 : 190, '');
            if ($value !== '') {
                $incoming[$key] = $value;
            }
        }
        if (! isset($incoming['soyad']) && str_contains($incoming['ad'] ?? '', ' ')) {
            $incoming['soyad'] = Str::afterLast($incoming['ad'], ' ');
            $incoming['ad'] = Str::beforeLast($incoming['ad'], ' ');
        }
        if (isset($incoming['il']) && ! isset($incoming['ilce'])) {
            $previous = (array) session(self::CHECKOUT_DRAFT_KEY, []);
            $cities = config('turkiye.cities', []);
            $city = $this->matchPlace($incoming['il'], array_keys($cities));
            if ($city === null || $this->matchPlace($previous['ilce'] ?? '', $cities[$city] ?? []) === null) {
                $incoming['ilce'] = '';
            }
        }

        $draft = array_filter([...(array) session(self::CHECKOUT_DRAFT_KEY, []), ...$incoming], fn ($v) => $v !== '');
        session()->put(self::CHECKOUT_DRAFT_KEY, $draft);

        return $draft;
    }

    /**
     * @param  array<string, string>  $draft
     * @return array{temiz: array<string, string>, alinan: list<string>, eksik: list<string>, hatali: list<string>}
     */
    private function validateCheckoutDraft(array $draft): array
    {
        $missing = [];
        $invalid = [];
        foreach (self::CHECKOUT_FIELDS as $key => $label) {
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
        if (! isset($missing['telefon']) && (strlen($phone) !== 11 || $phone[0] !== '0')) {
            $invalid['telefon'] = 'Cep telefonu 11 haneli olmalı, ör. 05xx xxx xx xx (yazılan: '.$draft['telefon'].')';
        }

        $email = mb_strtolower($draft['eposta'] ?? '');
        if (! isset($missing['eposta']) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid['eposta'] = 'E-posta adresi geçersiz (yazılan: '.$draft['eposta'].')';
        }

        $cities = config('turkiye.cities', []);
        $city = isset($missing['il']) ? null : $this->matchPlace($draft['il'], array_keys($cities));
        if (! isset($missing['il']) && $city === null) {
            $invalid['il'] = 'İl tanınmadı (yazılan: '.$draft['il'].')';
        }
        $district = $city !== null && ! isset($missing['ilce']) ? $this->matchPlace($draft['ilce'], $cities[$city] ?? []) : null;
        if ($city !== null && ! isset($missing['ilce']) && $district === null) {
            $invalid['ilce'] = $draft['ilce'].' ilçesi '.$city.' ilinde bulunamadı';
        }

        if (! isset($missing['adres']) && mb_strlen($draft['adres']) < 10) {
            $invalid['adres'] = 'Açık adres çok kısa; mahalle, cadde/sokak, bina no ve daire gerekli';
        }

        $received = array_values(array_diff_key(self::CHECKOUT_FIELDS, $missing, $invalid));

        return [
            'temiz' => array_filter([
                'ad' => $draft['ad'] ?? '',
                'soyad' => $draft['soyad'] ?? '',
                'eposta' => $email,
                'telefon' => $phone,
                'il' => $city ?? '',
                'ilce' => $district ?? '',
                'adres' => $draft['adres'] ?? '',
                'posta_kodu' => preg_replace('/\D/', '', Str::limit($draft['posta_kodu'] ?? '', 10, '')),
            ], fn ($v) => $v !== ''),
            'alinan' => $received,
            'eksik' => array_values($missing),
            'hatali' => array_values($invalid),
        ];
    }

    /**
     * Her turda modele sipariş formunun durumunu verir; geçmiş kısalsa bile baştan sormasın.
     */
    public function checkoutStatus(): ?string
    {
        $draft = (array) session(self::CHECKOUT_DRAFT_KEY, []);
        if ($draft === [] || app(CartService::class)->isEmpty()) {
            return null;
        }

        $check = $this->validateCheckoutDraft($draft);
        if ($check['eksik'] === [] && $check['hatali'] === []) {
            return 'SİPARİŞ FORMU: Teslimat bilgileri tamam ve ödeme formuna aktarıldı. Müşteri bir bilgiyi değiştirmek isterse yalnızca o alanla prepare_checkout çağır.';
        }

        return 'SİPARİŞ FORMU (devam ediyor): Alınan: '.($check['alinan'] ? implode(', ', $check['alinan']) : 'yok')
            .'. Eksik: '.($check['eksik'] ? implode(', ', $check['eksik']) : 'yok')
            .'. Hatalı: '.($check['hatali'] ? implode('; ', $check['hatali']) : 'yok')
            .'. Alınanları tekrar isteme; yalnızca eksik/hatalı olanları adıyla sor.';
    }

    /**
     * @param  list<string>  $options
     */
    private function matchPlace(string $value, array $options): ?string
    {
        $normalize = fn (string $v) => mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], trim(preg_replace('/\s+(ili|ilçesi|ilcesi)$/iu', '', $v))), 'UTF-8');
        $ascii = fn (string $v) => strtr($normalize($v), ['İ' => 'I', 'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'Ö' => 'O', 'Ç' => 'C']);
        $needle = $normalize($value);
        if ($needle === '') {
            return null;
        }

        foreach ($options as $option) {
            if ($normalize($option) === $needle) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($ascii($option) === $ascii($value)) {
                return $option;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function productSummary(Product $product): array
    {
        $showQuantity = SiteSetting::get('shop_show_stock_quantity', '0') === '1';
        $inStock = $product->stock > 0;

        return array_filter([
            'ad' => $product->name,
            'slug' => $product->slug,
            'marka' => $product->brand?->name,
            'stok_kodu' => $product->sku,
            'fiyat' => $this->money((float) $product->price),
            'indirimsiz_fiyat' => $product->hasDiscount() ? $this->money((float) $product->compare_at_price) : null,
            'stok_durumu' => $inStock ? ($showQuantity ? 'Stokta ('.$product->stock.' adet)' : 'Stokta') : 'Şu an stokta yok',
            'url' => route('products.show', $product),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Kartlar son ürün getiren aracın sonucunu gösterir; art arda ürün detayı
     * istenirse (karşılaştırma) kartlar birikir.
     */
    private function startCards(string $source): void
    {
        if (! ($source === 'get_product_details' && $this->cardSource === $source)) {
            $this->cards = [];
        }
        $this->cardSource = $source;
    }

    private function addCard(Product $product): void
    {
        $url = route('products.show', $product);
        if (isset($this->cards[$url]) || count($this->cards) >= self::MAX_CARDS) {
            return;
        }

        $this->cards[$url] = [
            'name' => $product->name,
            'slug' => $product->slug,
            'item_id' => $product->sku ?: 'KOS-'.$product->id,
            'url' => $url,
            'price' => $this->money((float) $product->price),
            'price_value' => (float) $product->price,
            'compare_price' => $product->hasDiscount() ? $this->money((float) $product->compare_at_price) : null,
            'in_stock' => $product->stock > 0,
            'brand' => $product->brand?->name,
            'image' => $product->imageUrl('product-thumb') ?? $product->imageUrl(),
        ];
    }

    /** @return array<string, mixed> */
    private function storeInfo(string $topic, float $amount): array
    {
        $pages = fn (array $slugs) => Page::query()->where('published', true)->whereIn('slug', $slugs)->get()
            ->mapWithKeys(fn (Page $p) => [$p->slug => $p])
            ->all();

        $shipping = fn () => [
            'ucretsiz_kargo_alt_limiti' => $this->store->freeShippingMin() > 0 ? $this->money($this->store->freeShippingMin()) : null,
            'kargo_secenekleri' => collect($this->store->shippingMethods())->map(fn (array $m) => array_filter([
                'ad' => $m['name'],
                'aciklama' => $m['desc'],
                'teslim_suresi' => $m['eta'],
                'ucret' => $m['fee'] > 0 ? $this->money($m['fee']) : 'Ücretsiz',
            ]))->all(),
        ];

        $payments = fn () => [
            'odeme_yontemleri' => collect($this->store->paymentMethods())->map(fn (array $m) => [
                'ad' => $m['name'] ?? $m['id'],
                'aciklama' => $m['desc'] ?? '',
            ])->all(),
            'kapida_odeme_hizmet_bedeli' => $this->store->codFee() > 0 ? $this->money($this->store->codFee()) : null,
        ];

        $pageText = function (string $slug, int $limit) use ($pages): ?array {
            $page = $pages([$slug])[$slug] ?? null;
            if (! $page) {
                return null;
            }

            return [
                'baslik' => $page->title,
                'metin' => Str::limit(RichContent::plainText($page->content), $limit, '…'),
                'url' => route('pages.show', $page->slug),
            ];
        };

        $contact = fn () => array_filter([
            'telefon' => SupportAssistantConfig::contactPhone(),
            'e_posta' => (string) SiteSetting::get('contact_email', config('kosar.contact.email')),
            'whatsapp' => SupportAssistantConfig::whatsappDigits() !== '' ? '+'.SupportAssistantConfig::whatsappDigits() : null,
            'adres' => (string) SiteSetting::get('contact_address', config('kosar.contact.address')),
            'iletisim_sayfasi' => route('contact.show'),
        ]);

        $result = match ($topic) {
            'kargo' => [
                ...$shipping(),
                'oncelik' => 'Kargo ücreti, seçenekleri ve ücretsiz kargo limiti için kargo_secenekleri ve ucretsiz_kargo_alt_limiti güncel ayardır; sayfa metniyle çelişirse bunları esas al.',
                'kargo_ve_iade_sayfasi' => $pageText('kargo-ve-iade', 2500),
            ],
            'iade' => ['kargo_ve_iade_sayfasi' => $pageText('kargo-ve-iade', 3500)],
            'odeme' => $payments(),
            'taksit' => $this->installmentInfo($amount),
            'iletisim' => [
                ...$contact(),
                'iletisim_sayfasi_metni' => $pageText('iletisim', 1200),
                'hakkimizda' => $pageText('hakkimizda', 1200),
                'not_konum' => 'Adres ve çalışma saatleri için iletisim_sayfasi_metni içindeki bilgiyi aynen ver; ilçe/şehir yazmıyorsa ekleme.',
            ],
            'sss' => ['sss_sayfasi' => $pageText('sss', 4500)],
            default => [...$shipping(), ...$payments(), 'iletisim' => $contact()],
        };

        return array_filter($result, fn ($v) => $v !== null && $v !== []) + [
            'not' => 'Burada yazmayan konu (ör. garanti süresi, teslim günü taahhüdü) için net bilgi olmadığını söyle.',
        ];
    }

    /** @return array<string, mixed> */
    private function installmentInfo(float $amount): array
    {
        if ($amount <= 0) {
            return [
                'not' => 'Taksit tablosu tutara göre hesaplanır. Kullanıcıdan ürün veya sepet tutarını iste ya da get_product_details ile ürün fiyatını al.',
                'odeme_yontemleri' => collect($this->store->paymentMethods())->pluck('name')->all(),
            ];
        }

        $table = $this->installments->forAmount($amount);
        if (empty($table['available'])) {
            return [
                'tutar' => $this->money($amount),
                'taksit_var' => false,
                'mesaj' => $table['message'] ?? 'Bu tutar için taksit bilgisi alınamadı.',
            ];
        }

        $rows = collect($table['rows'] ?? [])->take(10)->map(function (array $row) {
            $cells = array_filter($row['cells'] ?? []);
            $options = collect($cells)->map(fn (array $cell, int $count) => $count.' taksit: aylık '.$this->money((float) $cell['monthly']).', toplam '.$this->money((float) $cell['total']))->values()->all();

            return ['kart' => $row['label'], 'secenekler' => $options];
        })->all();

        return [
            'tutar' => $this->money($amount),
            'taksit_var' => true,
            'odeme_altyapisi' => $table['provider_label'] ?? null,
            'kartlara_gore_taksitler' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    private function orderStatus(string $orderNumber, string $email): array
    {
        $orderNumber = trim($orderNumber);
        $email = mb_strtolower(trim($email));

        if ($orderNumber === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['hata' => 'Sipariş numarası ve geçerli e-posta birlikte gerekli. Kullanıcıdan iste.'];
        }

        if ($this->orderLookups >= self::MAX_ORDER_LOOKUPS) {
            return ['hata' => 'Bu sohbette sorgu limiti doldu. Sipariş takip sayfasını veya WhatsApp desteğini öner.', 'siparis_takip_sayfasi' => route('tracking.show')];
        }
        $this->orderLookups++;

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('email', $email)
            ->with('items:id,order_id,product_name,quantity')
            ->first();

        if (! $order) {
            return [
                'bulunamadi' => true,
                'not' => 'Bu numara ve e-posta ile sipariş bulunamadı. Bilgileri kontrol etmesini iste; sorun sürerse handoff_to_human öner.',
            ];
        }

        $payment = match ((string) $order->payment_status) {
            'basarili' => 'Ödeme alındı',
            'basarisiz' => 'Ödeme başarısız',
            'bekliyor', 'beklemede' => 'Ödeme bekleniyor',
            default => null,
        };

        return array_filter([
            'siparis_no' => $order->order_number,
            'tarih' => $order->created_at?->format('d.m.Y'),
            'durum' => OrderStatus::label($order->status),
            'odeme' => $payment,
            'kargo_firmasi' => $order->shipping_carrier ?: null,
            'kargo_takip_no' => $order->shipping_tracking ?: null,
            'urunler' => $order->items->map(fn ($item) => $item->product_name.' × '.$item->quantity)->all(),
            'toplam' => $this->money((float) $order->total),
            'siparis_takip_sayfasi' => route('tracking.show'),
        ], fn ($v) => $v !== null && $v !== []);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function recommendPump(array $args): array
    {
        $applications = array_keys(config('pump_selector.applications', []));
        $application = (string) ($args['application'] ?? '');
        if (! in_array($application, $applications, true)) {
            return ['hata' => 'Kullanım senaryosu belirsiz. Kullanıcıya pompayı nerede kullanacağını sor.'];
        }

        $ui = PumpSelectorUiConfig::clientConfig();
        $required = $ui['fieldSets'][$application] ?? ($application === 'industrial_fan' ? ['space_m2', 'height_m', 'environment'] : []);
        $missing = array_values(array_filter($required, fn (string $field) => ! isset($args[$field]) || $args[$field] === ''));
        if ($missing !== []) {
            return [
                'eksik_bilgi' => array_map(fn (string $field) => $ui['fields'][$field]['label'] ?? $field, $missing),
                'not' => 'Öneri için bu bilgileri kullanıcıya kısa ve anlaşılır şekilde sor.',
            ];
        }

        $validator = Validator::make($args, [
            'apartments' => ['nullable', 'integer', 'min:1', 'max:500'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:40'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'depth' => ['nullable', 'integer', 'min:1', 'max:200'],
            'suction_depth' => ['nullable', 'integer', 'min:1', 'max:8'],
            'volume_m3' => ['nullable', 'integer', 'min:1', 'max:500'],
            'drain_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'distance' => ['nullable', 'integer', 'min:5', 'max:100'],
            'area_m2' => ['nullable', 'integer', 'min:50', 'max:50000'],
            'heated_area_m2' => ['nullable', 'integer', 'min:40', 'max:2000'],
            'space_m2' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'height_m' => ['nullable', 'integer', 'min:2', 'max:15'],
            'usage' => ['nullable', 'string', 'in:household,garden,agriculture,livestock'],
            'method' => ['nullable', 'string', 'in:sprinkler,drip'],
            'lift' => ['nullable', 'string', 'in:low,medium,high'],
            'environment' => ['nullable', 'string', 'in:workshop,warehouse,kitchen'],
        ]);

        if ($validator->fails()) {
            return [
                'hata' => 'Değerler hesaplama aralığının dışında: '.implode(' ', $validator->errors()->all()),
                'not' => 'Bu büyüklükte proje için teknik ekibe aktarmayı (handoff_to_human, reason=teklif_toptan) öner.',
            ];
        }

        $inputs = collect($validator->validated())->filter(fn ($v) => $v !== null)->all();
        $result = $this->pumps->recommend($application, $inputs);
        $requirements = $result['requirements'] ?? [];

        $products = collect($result['products'] ?? [])->take(5);
        $productModels = Product::query()->with('brand:id,name')->whereIn('id', $products->pluck('id'))->get()->keyBy('id');
        if ($products->isNotEmpty()) {
            $this->startCards('recommend_pump');
        }
        foreach ($products->take(self::MAX_CARDS) as $row) {
            if ($model = $productModels->get($row['id'])) {
                $this->addCard($model);
            }
        }

        return array_filter([
            'ihtiyac_ozeti' => $requirements['summary'] ?? null,
            'gereken_debi_m3h' => $requirements['flow_m3h'] ?? null,
            'gereken_basma_m' => $requirements['head_m'] ?? null,
            'onerilen_urunler' => $products->map(fn (array $p) => array_filter([
                'ad' => $p['name'],
                'marka' => $p['brand'] ?? null,
                'fiyat' => $this->money((float) $p['price']),
                'stok_durumu' => ! empty($p['in_stock']) ? 'Stokta' : 'Şu an stokta yok',
                'teknik' => $p['spec_summary'] ?? null,
                'uygunluk' => $p['match_reason'] ?? null,
                'url' => $p['url'] ?? null,
            ]))->all(),
            'siralama' => 'Ürünler en uygun seçimden başlayarak sıralıdır; bu sırayı koru.',
            'kategori_url' => $result['category_url'] ?? null,
            'pompa_secici_sayfasi' => route('pump-selector.show'),
            'not' => $products->isEmpty()
                ? 'Katalogda bu ihtiyaca uygun ürün bulunamadı; teknik ekibe aktarmayı öner.'
                : 'Bu hesap ön seçimdir; boru çapı, mesafe ve montaj koşulları sonucu değiştirebilir. Kesin seçim için teknik ekiple görüşmeyi öner.',
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @return array<string, mixed> */
    private function handoff(string $summary, string $reason): array
    {
        $this->handoffSummary = Str::limit(trim($summary), 400, '…');
        $this->handoffReason = $reason !== '' ? $reason : 'diger';

        return [
            'aktarim_hazir' => SupportAssistantConfig::whatsappUrl() !== null,
            'telefon' => SupportAssistantConfig::contactPhone() ?: null,
            'not' => 'Arayüzde "WhatsApp\'tan devam et" butonu gösterilecek. URL yazma; müşteriye bu butonla ekibe hemen ulaşabileceğini söyle.',
        ];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' ₺';
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function fn(string $name, string $description, array $properties, array $required): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ],
        ];
    }
}
