<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use App\Services\CartPricingService;
use App\Services\Checkout\CodSmsVerification;
use App\Services\Payment\PaymentManager;
use App\Services\StoreConfig;
use App\Support\PaymentGatewayConfig;
use App\Support\Seo;
use App\Services\CheckoutCalculator;
use App\Services\CouponService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private CartPricingService $pricing,
        private CheckoutCalculator $calculator,
        private OrderService $orders,
        private StoreConfig $store,
        private CodSmsVerification $codSms,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Sepetiniz boş.']);
        }

        app(AnalyticsTracker::class)->trackCheckoutStarted($request, $this->cart);

        return $this->checkoutView();
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $code = $request->string('coupon_code');
        $result = $this->coupons->apply($code);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message'],
        );
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->coupons->remove();

        return back()->with('success', 'Kupon kaldırıldı.');
    }

    public function saveContact(Request $request): JsonResponse
    {
        if ($this->cart->isEmpty()) {
            return response()->json(['ok' => false]);
        }

        $data = $request->validate([
            'ad' => ['nullable', 'string', 'max:100'],
            'soyad' => ['nullable', 'string', 'max:100'],
            'eposta' => ['nullable', 'string', 'max:190'],
            'telefon' => ['nullable', 'string', 'max:30'],
        ]);

        app(AnalyticsTracker::class)->updateCheckoutContact($request, $this->cart, $data);

        return response()->json(['ok' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $districtsByCity = config('turkiye.cities', []);

        $data = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'eposta' => ['required', 'email'],
            'telefon' => ['required', 'string', 'max:30'],
            'il' => ['required', 'string', Rule::in(array_keys($districtsByCity))],
            'ilce' => ['required', 'string', 'max:100', Rule::in($districtsByCity[$request->input('il')] ?? [])],
            'adres' => ['required', 'string', 'max:500'],
            'posta_kodu' => ['nullable', 'string', 'max:10'],
            'kurumsal_fatura' => ['sometimes', 'boolean'],
            'firma_adi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:200'],
            'vergi_numarasi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:30'],
            'vergi_dairesi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:120'],
            'fatura_adresi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:500'],
            'kargo_yontemi' => ['required', Rule::in(array_column($this->shippingMethodsForCheckout(), 'id'))],
            'odeme_yontemi' => ['required', Rule::in($this->store->enabledPaymentIds())],
            'sozlesme' => ['accepted'],
        ]);

        $teslimat = [
            'ad' => $data['ad'],
            'soyad' => $data['soyad'],
            'eposta' => $data['eposta'],
            'telefon' => $data['telefon'],
            'il' => $data['il'],
            'ilce' => $data['ilce'],
            'adres' => $data['adres'],
            'postaKodu' => $data['posta_kodu'] ?? null,
        ];

        if ($request->boolean('kurumsal_fatura')) {
            $teslimat['kurumsalFatura'] = [
                'firmaAdi' => $data['firma_adi'],
                'vergiNumarasi' => $data['vergi_numarasi'],
                'vergiDairesi' => $data['vergi_dairesi'],
                'faturaAdresi' => $data['fatura_adresi'],
            ];
        }

        app(AnalyticsTracker::class)->updateCheckoutContact($request, $this->cart, $data);

        if ($redirect = $this->gateCodSms($request, $data)) {
            return $redirect;
        }

        try {
            $result = $this->orders->create(
                $teslimat,
                $data['kargo_yontemi'],
                $data['odeme_yontemi'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['cart' => $e->getMessage()]);
        }

        $order = $result['order'];
        app(AnalyticsTracker::class)->attachOrder($request, $order);
        session(['last_order_email' => $order->email]);

        if ($result['payment_url']) {
            return redirect($result['payment_url']);
        }

        return redirect()->route('checkout.success', ['order' => $order->order_number]);
    }

    public function success(string $order): View
    {
        $model = Order::query()->where('order_number', $order)->with('items')->firstOrFail();

        return view('shop.checkout.success', [
            'menuCategories' => Category::menu()->get(),
            'order' => $model,
            'metaTitle' => 'Sipariş onayı',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function payment(string $order, PaymentManager $payments, OrderService $orders): View|RedirectResponse
    {
        $model = Order::query()->where('order_number', $order)->firstOrFail();

        if ($model->payment_status === 'basarili') {
            return redirect()->route('checkout.success', ['order' => $model->order_number]);
        }

        if (request('durum') === 'hata') {
            $orders->markPaymentFailed($model, 'paytr_return');
            $model->refresh();
        }

        $isDemo = ! PaymentGatewayConfig::isLive();
        $retryUrl = null;
        if ($model->payment_method === 'kredi_karti' && ! $isDemo) {
            try {
                $orders->reserveStockForPaymentRetry($model);
                $odeme = $payments->baslat($model);
                if ($odeme['basarili'] && ! empty($odeme['odeme_url'])) {
                    $retryUrl = $odeme['odeme_url'];
                }
            } catch (\RuntimeException $e) {
                return view('shop.checkout.payment', [
                    'menuCategories' => Category::menu()->get(),
                    'order' => $model->fresh(),
                    'isDemo' => $isDemo,
                    'gatewayLabel' => PaymentGatewayConfig::label(),
                    'retryUrl' => null,
                    'stockError' => $e->getMessage(),
                    'metaTitle' => 'Ödeme',
                    ...Seo::noIndexMeta(),
                ]);
            }
        }

        return view('shop.checkout.payment', [
            'menuCategories' => Category::menu()->get(),
            'order' => $model,
            'isDemo' => $isDemo,
            'gatewayLabel' => PaymentGatewayConfig::label(),
            'retryUrl' => $retryUrl,
            'metaTitle' => 'Ödeme',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function completePayment(string $order): RedirectResponse
    {
        abort_unless(! PaymentGatewayConfig::isLive() && request()->boolean('demo'), 404);

        $model = Order::query()->where('order_number', $order)->firstOrFail();
        if ($model->payment_status !== 'basarili') {
            $this->orders->confirmPayment($model);
        }

        return redirect()->route('checkout.success', ['order' => $model->order_number])
            ->with('success', request()->has('demo') ? 'Ödeme alındı (demo).' : 'Ödeme alındı.');
    }

    /** @return array<int, array<string, string>> */
    private function shippingMethodsForCheckout(): array
    {
        return $this->store->shippingMethods();
    }

    private function checkoutView(): View|RedirectResponse
    {
        $paymentMethods = $this->store->paymentMethods();
        if ($paymentMethods === []) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Şu an aktif ödeme yöntemi yok. Lütfen mağaza yönetimi ile iletişime geçin.',
            ]);
        }

        $breakdown = $this->pricing->breakdown();
        $coupon = $this->coupons->findValid($this->coupons->appliedCode() ?? '');
        $shippingMethods = $this->shippingMethodsForCheckout();
        $districtsByCity = config('turkiye.cities', []);
        $defaultShipping = old('kargo_yontemi', $shippingMethods[0]['id'] ?? 'standart');
        $defaultPayment = old('odeme_yontemi', $paymentMethods[0]['id']);
        if (! in_array($defaultPayment, array_column($paymentMethods, 'id'), true)) {
            $defaultPayment = $paymentMethods[0]['id'];
        }
        $paymentQuotes = [];
        foreach ($paymentMethods as $method) {
            $quote = $this->calculator->totals(
                $breakdown['subtotal'],
                $breakdown['total_discount'],
                $defaultShipping,
                $method['id'],
                $breakdown['free_shipping'],
                $this->cart->totalDesi(),
            );
            $paymentQuotes[$method['id']] = [
                'total' => $quote['total'],
                'havale_discount' => $quote['havale_discount'],
                'cod_fee' => $quote['cod_fee'],
                'vat' => $quote['vat'],
                'shipping' => $quote['shipping'],
            ];
        }
        $totals = $this->calculator->totals(
            $breakdown['subtotal'],
            $breakdown['total_discount'],
            $defaultShipping,
            $defaultPayment,
            $breakdown['free_shipping'],
            $this->cart->totalDesi(),
        );

        return view('shop.checkout.index', [
            'lines' => $this->cart->lines(),
            'pricing' => $breakdown,
            'totals' => $totals,
            'paymentQuotes' => $paymentQuotes,
            'havalePercentLabel' => rtrim(rtrim(number_format($this->store->havaleDiscountPercent(), 2, ',', '.'), '0'), ','),
            'coupon' => $coupon,
            'cities' => array_keys($districtsByCity),
            'districtsByCity' => $districtsByCity,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'defaultPayment' => $defaultPayment,
            'defaultShipping' => $defaultShipping,
            'codSmsEnabled' => $this->codSms->enabled(),
            'codOtp' => $this->codSms->pendingForView(),
            'metaTitle' => 'Ödeme',
            ...Seo::noIndexMeta(),
        ]);
    }

    /**
     * Kapıda ödemede SMS açıksa sipariş, kod doğrulanmadan yazılmaz.
     *
     * @param  array<string, mixed>  $data
     */
    private function gateCodSms(Request $request, array $data): ?RedirectResponse
    {
        if (! $this->codSms->requiredFor((string) $data['odeme_yontemi'])) {
            session()->forget(CodSmsVerification::SESSION_KEY);

            return null;
        }

        $phone = $this->codSms->normalizePhone((string) $data['telefon']);
        if ($phone === '') {
            return back()->withInput()->withErrors([
                'telefon' => 'Kapıda ödeme için 05 ile başlayan 11 haneli cep telefonu girin.',
            ]);
        }

        $payloadHash = $this->codSms->payloadHash($data, $this->cart);

        if ($request->input('otp_action') === 'resend') {
            $result = $this->codSms->resend($phone, $payloadHash);

            return back()->withInput()->with($result['ok'] ? 'success' : 'error', $result['message']);
        }

        $code = preg_replace('/\D+/', '', (string) $request->input('otp_code', '')) ?? '';
        if (strlen($code) !== 6) {
            $result = $this->codSms->begin($phone, $payloadHash);

            return back()->withInput()->with($result['ok'] ? 'success' : 'error', $result['message']);
        }

        $check = $this->codSms->verify($code, $phone, $payloadHash);
        if ($check['ok']) {
            return null;
        }

        if (str_contains($check['message'], 'Bilgiler değişti')) {
            $result = $this->codSms->begin($phone, $payloadHash);

            return back()->withInput()->with(
                $result['ok'] ? 'success' : 'error',
                $result['ok'] ? 'Bilgiler değiştiği için yeni kod gönderildi.' : $result['message'],
            );
        }

        return back()->withInput()->withErrors(['otp_code' => $check['message']]);
    }
}
